<?php

namespace App\Http\Controllers\Admin\Cloudflare;

use App\Http\Controllers\Controller;
use App\Models\CloudflareAccount;
use App\Models\CloudflareDomain;
use App\Models\CloudflareDnsRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class CloudflareAccountController extends Controller
{
    public function __construct()
    {
        // Temporarily commented out for testing
        // $this->middleware('admin');
    }
    
    /**
     * Display all Cloudflare accounts
     */
    public function index()
    {
        $accounts = CloudflareAccount::with(['domains' => function($query) {
            $query->select('id', 'cloudflare_account_id', 'domain_name', 'status', 'dns_records_count', 'last_synced_at');
        }])->orderBy('is_default', 'desc')->orderBy('name')->get();
        
        return view('admin.cloudflare.accounts.index', compact('accounts'));
    }
    
    /**
     * Show form to create new account
     */
    public function create()
    {
        return view('admin.cloudflare.accounts.create');
    }
    
    /**
     * Store new Cloudflare account
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:cloudflare_accounts,email',
            'api_key' => 'required|string',
            'api_token' => 'nullable|string',
            'is_default' => 'boolean',
            'notes' => 'nullable|string'
        ]);
        
        try {
            DB::beginTransaction();
            
            $account = CloudflareAccount::create([
                'name' => $request->name,
                'email' => $request->email,
                'api_key' => $request->api_key,
                'api_token' => $request->api_token,
                'is_active' => true,
                'is_default' => $request->is_default ?: false,
                'notes' => $request->notes
            ]);
            
            // If this is set as default, unset others
            if ($request->is_default) {
                CloudflareAccount::where('id', '!=', $account->id)
                    ->update(['is_default' => false]);
            }
            
            // Test the connection and sync domains
            try {
                $syncedCount = $this->syncAccountDomains($account->id);
                $successMessage = "Cloudflare account added successfully! Synced {$syncedCount} domains.";
            } catch (Exception $syncError) {
                // Account was created but sync failed - this is ok, they can sync later
                Log::warning('Account created but sync failed', [
                    'account_id' => $account->id,
                    'error' => $syncError->getMessage()
                ]);
                $successMessage = 'Cloudflare account added successfully! However, initial sync failed: ' . $syncError->getMessage() . '. You can try syncing again from the accounts page.';
            }
            
            DB::commit();
            
            return redirect()->route('admin.cloudflare.accounts.index')
                ->with('success', $successMessage);
                
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating Cloudflare account: ' . $e->getMessage());
            
            // Provide more specific error messages
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Invalid request headers') !== false) {
                $errorMessage = 'Invalid API credentials. Please check your email and API key.';
            } elseif (strpos($errorMessage, 'Authentication failed') !== false) {
                $errorMessage = 'Authentication failed. Please verify your Cloudflare email and API key.';
            } elseif (strpos($errorMessage, 'rate limit') !== false) {
                $errorMessage = 'Cloudflare API rate limit exceeded. Please try again later.';
            }
            
            return back()->with('error', 'Error creating account: ' . $errorMessage)->withInput();
        }
    }
    
    /**
     * Show account details
     */
    public function show(CloudflareAccount $account)
    {
        $account->load(['domains.dnsRecords']);
        return view('admin.cloudflare.accounts.show', compact('account'));
    }
    
    /**
     * Show edit form
     */
    public function edit(CloudflareAccount $account)
    {
        return view('admin.cloudflare.accounts.edit', compact('account'));
    }
    
    /**
     * Update account
     */
    public function update(Request $request, CloudflareAccount $account)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:cloudflare_accounts,email,' . $account->id,
            'api_key' => 'nullable|string',
            'api_token' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string'
        ]);
        
        try {
            DB::beginTransaction();
            
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'is_active' => $request->is_active ?: false,
                'is_default' => $request->is_default ?: false,
                'notes' => $request->notes
            ];
            
            // Only update API credentials if provided
            if ($request->api_key) {
                $updateData['api_key'] = $request->api_key;
            }
            if ($request->api_token) {
                $updateData['api_token'] = $request->api_token;
            }
            
            $account->update($updateData);
            
            // Handle default account logic
            if ($request->is_default) {
                CloudflareAccount::where('id', '!=', $account->id)
                    ->update(['is_default' => false]);
            }
            
            DB::commit();
            
            return redirect()->route('admin.cloudflare.accounts.index')
                ->with('success', 'Account updated successfully!');
                
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }
    
    /**
     * Delete account
     */
    public function destroy(CloudflareAccount $account)
    {
        try {
            $account->delete();
            return redirect()->route('admin.cloudflare.accounts.index')
                ->with('success', 'Account deleted successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Error deleting account: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync domains for an account
     */
    public function syncDomains(CloudflareAccount $account)
    {
        try {
            $this->syncAccountDomains($account->id);
            return response()->json([
                'success' => true,
                'message' => 'Domains synced successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Add new domain to Cloudflare
     */
    public function addDomain(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:cloudflare_accounts,id',
            'domain_name' => 'required|string|max:255'
        ]);
        
        try {
            $account = CloudflareAccount::findOrFail($request->account_id);
            $result = $this->createDomainInCloudflare($account, $request->domain_name);
            
            if ($result['success']) {
                // Sync domains to get the new domain
                $this->syncAccountDomains($account->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Domain added to Cloudflare successfully!',
                    'domain' => $result['domain']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Test connection to Cloudflare API
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'api_key' => 'required|string'
        ]);
        
        try {
            // Create a temporary account object for testing
            $testAccount = new CloudflareAccount([
                'email' => $request->email,
                'api_key' => $request->api_key
            ]);
            
            // Try to get zones (this will test the connection)
            $zones = $this->getCloudflareZones($testAccount);
            
            return response()->json([
                'success' => true,
                'message' => 'Connection successful! Found ' . count($zones) . ' domains.',
                'domains_count' => count($zones)
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Test existing account connection
     */
    public function testAccountConnection(CloudflareAccount $account)
    {
        try {
            $zones = $this->getCloudflareZones($account);
            
            return response()->json([
                'success' => true,
                'message' => 'Connection successful! Found ' . count($zones) . ' domains.',
                'domains_count' => count($zones)
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Sync all accounts
     */
    public function syncAllAccounts()
    {
        try {
            $accounts = CloudflareAccount::active()->get();
            $syncedCount = 0;
            
            foreach ($accounts as $account) {
                $this->syncAccountDomains($account->id);
                $syncedCount++;
            }
            
            return response()->json([
                'success' => true,
                'message' => "Synced {$syncedCount} accounts successfully!"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Private method to sync domains for an account
     */
    private function syncAccountDomains($accountId)
    {
        $account = CloudflareAccount::findOrFail($accountId);
        
        // Get zones from Cloudflare
        $zones = $this->getCloudflareZones($account);
        
        $syncedDomains = 0;
        
        foreach ($zones as $zone) {
            // Validate zone data has required fields
            if (!isset($zone['id']) || !isset($zone['name'])) {
                Log::warning('Invalid zone data received from Cloudflare', ['zone' => $zone, 'account_id' => $account->id]);
                continue;
            }
            
            $domain = CloudflareDomain::updateOrCreate(
                [
                    'cloudflare_account_id' => $account->id,
                    'zone_id' => $zone['id']
                ],
                [
                    'domain_name' => $zone['name'],
                    'status' => $zone['status'] ?? 'unknown',
                    'nameservers' => isset($zone['name_servers']) ? $zone['name_servers'] : [],
                    'plan_name' => isset($zone['plan']['name']) ? $zone['plan']['name'] : 'free',
                    'plan_id' => isset($zone['plan']['id']) ? $zone['plan']['id'] : null,
                    'created_on_cloudflare' => $zone['created_on'] ?? null,
                    'modified_on_cloudflare' => $zone['modified_on'] ?? null,
                    'last_synced_at' => now(),
                    'sync_status' => 'synced',
                    'is_active' => true
                ]
            );
            
            // Sync DNS records for this domain
            $this->syncDomainDnsRecords($domain);
            $syncedDomains++;
        }
        
        // Update account stats
        $account->update([
            'total_domains' => $syncedDomains,
            'last_synced_at' => now()
        ]);
        
        return $syncedDomains;
    }
    
    /**
     * Private method to sync DNS records for a domain
     */
    private function syncDomainDnsRecords(CloudflareDomain $domain)
    {
        $account = $domain->cloudflareAccount;
        $dnsRecords = $this->getCloudfareDnsRecords($account, $domain->zone_id);
        
        $syncedRecords = 0;
        
        foreach ($dnsRecords as $record) {
            CloudflareDnsRecord::updateOrCreate(
                [
                    'cloudflare_domain_id' => $domain->id,
                    'cloudflare_record_id' => $record['id']
                ],
                [
                    'zone_id' => $record['zone_id'],
                    'type' => $record['type'],
                    'name' => $record['name'],
                    'content' => $record['content'],
                    'ttl' => $record['ttl'],
                    'priority' => $record['priority'] ?? null,
                    'proxied' => $record['proxied'] ?? false,
                    'locked' => $record['locked'] ?? false,
                    'created_on_cloudflare' => $record['created_on'] ?? null,
                    'modified_on_cloudflare' => $record['modified_on'] ?? null,
                    'last_synced_at' => now(),
                    'sync_status' => 'synced'
                ]
            );
            $syncedRecords++;
        }
        
        // Update domain DNS records count
        $domain->update(['dns_records_count' => $syncedRecords]);
        
        return $syncedRecords;
    }
    
    /**
     * Get zones from Cloudflare API
     */
    private function getCloudflareZones(CloudflareAccount $account)
    {
        try {
            $response = $this->makeCloudflareRequest($account, 'zones');
            
            if (!isset($response['success']) || !$response['success']) {
                $errorMsg = 'API request failed';
                if (isset($response['errors']) && is_array($response['errors']) && count($response['errors']) > 0) {
                    $errorMsg = $response['errors'][0]['message'] ?? $errorMsg;
                }
                throw new Exception($errorMsg);
            }
            
            return $response['result'] ?? [];
            
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare zones', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get DNS records from Cloudflare API
     */
    private function getCloudfareDnsRecords(CloudflareAccount $account, $zoneId)
    {
        $response = $this->makeCloudflareRequest($account, "zones/{$zoneId}/dns_records");
        return $response['result'] ?? [];
    }
    
    /**
     * Create domain in Cloudflare
     */
    private function createDomainInCloudflare(CloudflareAccount $account, $domainName)
    {
        try {
            $data = ['name' => $domainName];
            $response = $this->makeCloudflareRequest($account, 'zones', 'POST', $data);
            
            return [
                'success' => true,
                'domain' => $response['result']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Make API request to Cloudflare
     */
    private function makeCloudflareRequest(CloudflareAccount $account, $endpoint, $method = 'GET', $data = null)
    {
        $baseUrl = 'https://api.cloudflare.com/client/v4/';
        $url = $baseUrl . $endpoint;
        
        $headers = [
            'X-Auth-Email' => $account->email,
            'X-Auth-Key' => $account->api_key,
            'Content-Type' => 'application/json'
        ];
        
        $request = Http::withHeaders($headers)->timeout(30);
        
        try {
            if ($method === 'GET') {
                $response = $request->get($url);
            } elseif ($method === 'POST') {
                $response = $request->post($url, $data);
            } elseif ($method === 'PUT') {
                $response = $request->put($url, $data);
            } elseif ($method === 'DELETE') {
                $response = $request->delete($url);
            }
            
            $responseData = $response->json();
            
            if (!$response->successful()) {
                $errorMsg = isset($responseData['errors'][0]['message']) 
                    ? $responseData['errors'][0]['message'] 
                    : 'HTTP Error: ' . $response->status();
                throw new Exception($errorMsg);
            }
            
            return $responseData;
            
        } catch (Exception $e) {
            Log::error('Cloudflare API Error: ' . $e->getMessage(), [
                'account_id' => $account->id,
                'endpoint' => $endpoint,
                'method' => $method
            ]);
            throw $e;
        }
    }
}