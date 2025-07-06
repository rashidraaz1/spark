<?php

namespace App\Http\Controllers\Admin\Cloudflare;

use App\Http\Controllers\Controller;
use App\Models\CloudflareAccount;
use App\Models\CloudflareDomain;
use App\Models\CloudflareDnsRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CloudflareController extends Controller
{
    private $baseUrl = 'https://api.cloudflare.com/client/v4/';
    
    public function __construct()
    {
        $this->middleware('admin');
    }
    
    /**
     * Display Cloudflare DNS management dashboard
     */
    public function index()
    {
        $accounts = CloudflareAccount::active()->with('domains')->get();
        $totalDomains = CloudflareDomain::count();
        $totalRecords = CloudflareDnsRecord::count();
        
        return view('admin.cloudflare.index', compact('accounts', 'totalDomains', 'totalRecords'));
    }
    
    /**
     * Show nameservers for a domain
     */
    public function nameservers(Request $request)
    {
        $accounts = CloudflareAccount::active()->get();
        $domain = $request->input('domain');
        $accountId = $request->input('account_id');
        
        if (!$domain || !$accountId) {
            return view('admin.cloudflare.nameservers', compact('accounts'));
        }
        
        try {
            $account = CloudflareAccount::findOrFail($accountId);
            $nameservers = $this->getNameservers($account, $domain);
            return view('admin.cloudflare.nameservers', compact('nameservers', 'domain', 'accounts', 'accountId'));
        } catch (Exception $e) {
            return view('admin.cloudflare.nameservers', [
                'error' => $e->getMessage(),
                'domain' => $domain,
                'accounts' => $accounts,
                'accountId' => $accountId
            ]);
        }
    }
    
    /**
     * Show DNS records management
     */
    public function dnsRecords(Request $request)
    {
        $accounts = CloudflareAccount::active()->get();
        $domain = $request->input('domain');
        $accountId = $request->input('account_id');
        $type = $request->input('type');
        
        if (!$domain || !$accountId) {
            return view('admin.cloudflare.dns-records', compact('accounts'));
        }
        
        try {
            $account = CloudflareAccount::findOrFail($accountId);
            $records = $this->getDnsRecords($account, $domain, $type);
            return view('admin.cloudflare.dns-records', compact('records', 'domain', 'type', 'accounts', 'accountId'));
        } catch (Exception $e) {
            return view('admin.cloudflare.dns-records', [
                'error' => $e->getMessage(),
                'domain' => $domain,
                'accounts' => $accounts,
                'accountId' => $accountId
            ]);
        }
    }
    
    /**
     * Show form to add DNS record
     */
    public function addRecordForm(Request $request)
    {
        $accounts = CloudflareAccount::active()->get();
        $domain = $request->input('domain');
        $accountId = $request->input('account_id');
        
        return view('admin.cloudflare.add-record', compact('domain', 'accounts', 'accountId'));
    }
    
    /**
     * Add DNS record
     */
    public function addRecord(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:cloudflare_accounts,id',
            'domain' => 'required|string',
            'type' => 'required|string',
            'name' => 'required|string',
            'content' => 'required|string',
            'ttl' => 'nullable|integer|min:1',
            'priority' => 'nullable|integer'
        ]);
        
        try {
            $account = CloudflareAccount::findOrFail($request->account_id);
            $result = $this->addDnsRecord(
                $account,
                $request->domain,
                $request->type,
                $request->name,
                $request->content,
                (int)($request->ttl ?: 1),
                $request->priority ? (int)$request->priority : null
            );
            
            if ($result['success']) {
                return redirect()
                    ->route('admin.cloudflare.dns-records', [
                        'domain' => $request->domain,
                        'account_id' => $request->account_id
                    ])
                    ->with('success', 'DNS record added successfully');
            } else {
                return back()->with('error', $result['error'])->withInput();
            }
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
    
    /**
     * Show form to edit DNS record
     */
    public function editRecordForm(Request $request)
    {
        $domain = $request->input('domain');
        $accountId = $request->input('account_id');
        $recordId = $request->input('record_id');
        
        if (!$domain || !$accountId || !$recordId) {
            return back()->with('error', 'Domain, account ID and record ID are required');
        }
        
        try {
            $account = CloudflareAccount::findOrFail($accountId);
            $records = $this->getDnsRecords($account, $domain);
            $record = collect($records['records'])->firstWhere('id', $recordId);
            
            if (!$record) {
                return back()->with('error', 'Record not found');
            }
            
            return view('admin.cloudflare.edit-record', compact('domain', 'record', 'accountId'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * Update DNS record
     */
    public function updateRecord(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:cloudflare_accounts,id',
            'domain' => 'required|string',
            'record_id' => 'required|string',
            'type' => 'required|string',
            'name' => 'required|string',
            'content' => 'required|string',
            'ttl' => 'nullable|integer|min:1',
            'priority' => 'nullable|integer'
        ]);
        
        try {
            $account = CloudflareAccount::findOrFail($request->account_id);
            $result = $this->updateDnsRecord(
                $account,
                $request->domain,
                $request->record_id,
                $request->type,
                $request->name,
                $request->content,
                (int)($request->ttl ?: 1),
                $request->priority ? (int)$request->priority : null
            );
            
            if ($result['success']) {
                return redirect()
                    ->route('admin.cloudflare.dns-records', [
                        'domain' => $request->domain,
                        'account_id' => $request->account_id
                    ])
                    ->with('success', 'DNS record updated successfully');
            } else {
                return back()->with('error', $result['error'])->withInput();
            }
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
    
    /**
     * Delete DNS record
     */
    public function deleteRecord(Request $request)
    {
        $domain = $request->input('domain');
        $accountId = $request->input('account_id');
        $recordId = $request->input('record_id');
        
        if (!$domain || !$accountId || !$recordId) {
            return response()->json(['success' => false, 'error' => 'Domain, account ID and record ID are required'], 400);
        }
        
        try {
            $account = CloudflareAccount::findOrFail($accountId);
            $result = $this->deleteDnsRecord($account, $domain, $recordId);
            
            if ($result['success']) {
                return response()->json(['success' => true, 'message' => 'DNS record deleted successfully']);
            } else {
                return response()->json(['success' => false, 'error' => $result['error']], 400);
            }
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * API endpoint to get nameservers
     */
    public function apiNameservers(Request $request)
    {
        $domain = $request->input('domain');
        $accountId = $request->input('account_id');
        
        if (!$domain || !$accountId) {
            return response()->json(['success' => false, 'error' => 'Domain and account ID are required'], 400);
        }
        
        try {
            $account = CloudflareAccount::findOrFail($accountId);
            $result = $this->getNameservers($account, $domain);
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * API endpoint to get DNS records
     */
    public function apiDnsRecords(Request $request)
    {
        $domain = $request->input('domain');
        $accountId = $request->input('account_id');
        $type = $request->input('type');
        
        if (!$domain || !$accountId) {
            return response()->json(['success' => false, 'error' => 'Domain and account ID are required'], 400);
        }
        
        try {
            $account = CloudflareAccount::findOrFail($accountId);
            $result = $this->getDnsRecords($account, $domain, $type);
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get Cloudflare nameservers for a domain
     */
    private function getNameservers(CloudflareAccount $account, $domain)
    {
        $zoneId = $this->getZoneId($account, $domain);
        $response = $this->makeRequest($account, 'zones/' . $zoneId);
        
        return [
            'success' => true,
            'domain' => $domain,
            'nameservers' => $response['result']['name_servers'] ?? [],
            'status' => $response['result']['status'] ?? 'unknown'
        ];
    }
    
    /**
     * Get DNS records for a domain
     */
    private function getDnsRecords(CloudflareAccount $account, $domain, $type = null)
    {
        $zoneId = $this->getZoneId($account, $domain);
        $endpoint = 'zones/' . $zoneId . '/dns_records';
        
        if ($type) {
            $endpoint .= '?type=' . urlencode($type);
        }
        
        $response = $this->makeRequest($account, $endpoint);
        
        return [
            'success' => true,
            'domain' => $domain,
            'records' => $response['result'] ?? []
        ];
    }
    
    /**
     * Add DNS record
     */
    private function addDnsRecord(CloudflareAccount $account, $domain, $type, $name, $content, $ttl = 1, $priority = null)
    {
        $zoneId = $this->getZoneId($account, $domain);
        
        $data = [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => (int)$ttl
        ];
        
        if (strtoupper($type) === 'MX' && $priority !== null) {
            $data['priority'] = (int)$priority;
        }
        
        $response = $this->makeRequest($account, 'zones/' . $zoneId . '/dns_records', 'POST', $data);
        
        return [
            'success' => true,
            'message' => 'DNS record added successfully',
            'record' => $response['result']
        ];
    }
    
    /**
     * Update DNS record
     */
    private function updateDnsRecord(CloudflareAccount $account, $domain, $recordId, $type, $name, $content, $ttl = 1, $priority = null)
    {
        $zoneId = $this->getZoneId($account, $domain);
        
        $data = [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => (int)$ttl
        ];
        
        if (strtoupper($type) === 'MX' && $priority !== null) {
            $data['priority'] = (int)$priority;
        }
        
        $response = $this->makeRequest($account, 'zones/' . $zoneId . '/dns_records/' . $recordId, 'PUT', $data);
        
        return [
            'success' => true,
            'message' => 'DNS record updated successfully',
            'record' => $response['result']
        ];
    }
    
    /**
     * Delete DNS record
     */
    private function deleteDnsRecord(CloudflareAccount $account, $domain, $recordId)
    {
        $zoneId = $this->getZoneId($account, $domain);
        $this->makeRequest($account, 'zones/' . $zoneId . '/dns_records/' . $recordId, 'DELETE');
        
        return [
            'success' => true,
            'message' => 'DNS record deleted successfully'
        ];
    }
    
    /**
     * Get zone ID for a domain
     */
    private function getZoneId(CloudflareAccount $account, $domain)
    {
        // First try to get from local database
        $localDomain = CloudflareDomain::where('cloudflare_account_id', $account->id)
            ->where('domain_name', $domain)
            ->first();
            
        if ($localDomain && $localDomain->zone_id) {
            return $localDomain->zone_id;
        }
        
        // If not found locally, fetch from Cloudflare API
        $response = $this->makeRequest($account, 'zones?name=' . urlencode($domain));
        
        if (empty($response['result'])) {
            throw new Exception('Domain not found in Cloudflare account: ' . $domain);
        }
        
        $zoneId = $response['result'][0]['id'];
        
        // Update local database if domain exists
        if ($localDomain) {
            $localDomain->update(['zone_id' => $zoneId]);
        }
        
        return $zoneId;
    }
    
    /**
     * Make API request to Cloudflare
     */
    private function makeRequest(CloudflareAccount $account, $endpoint, $method = 'GET', $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        
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
                $errorMsg = 'HTTP Error: ' . $response->status();
                if (isset($responseData['errors'][0]['message'])) {
                    $errorMsg = $responseData['errors'][0]['message'];
                }
                throw new Exception($errorMsg);
            }
            
            if (!isset($responseData['success']) || !$responseData['success']) {
                $errorMsg = 'API request failed';
                if (isset($responseData['errors'][0]['message'])) {
                    $errorMsg = $responseData['errors'][0]['message'];
                }
                throw new Exception($errorMsg);
            }
            
            return $responseData;
            
        } catch (Exception $e) {
            Log::error('Cloudflare API Error: ' . $e->getMessage(), [
                'account_id' => $account->id,
                'endpoint' => $endpoint,
                'method' => $method,
                'data' => $data
            ]);
            throw $e;
        }
    }
}