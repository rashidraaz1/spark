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
        $domain = $request->input('domain');
        $type = $request->input('type');
        
        if (!$domain) {
            return view('admin.cloudflare.dns-records');
        }
        
        try {
            $records = $this->getDnsRecords($domain, $type);
            return view('admin.cloudflare.dns-records', compact('records', 'domain', 'type'));
        } catch (Exception $e) {
            return view('admin.cloudflare.dns-records', [
                'error' => $e->getMessage(),
                'domain' => $domain
            ]);
        }
    }
    
    /**
     * Show form to add DNS record
     */
    public function addRecordForm(Request $request)
    {
        $domain = $request->input('domain');
        return view('admin.cloudflare.add-record', compact('domain'));
    }
    
    /**
     * Add DNS record
     */
    public function addRecord(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'type' => 'required|string',
            'name' => 'required|string',
            'content' => 'required|string',
            'ttl' => 'nullable|integer|min:1',
            'priority' => 'nullable|integer'
        ]);
        
        try {
            $result = $this->addDnsRecord(
                $request->domain,
                $request->type,
                $request->name,
                $request->content,
                (int)($request->ttl ?: 1),
                $request->priority ? (int)$request->priority : null
            );
            
            if ($result['success']) {
                return redirect()
                    ->route('admin.cloudflare.dns-records', ['domain' => $request->domain])
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
        $recordId = $request->input('record_id');
        
        if (!$domain || !$recordId) {
            return back()->with('error', 'Domain and record ID are required');
        }
        
        try {
            $records = $this->getDnsRecords($domain);
            $record = collect($records['records'])->firstWhere('id', $recordId);
            
            if (!$record) {
                return back()->with('error', 'Record not found');
            }
            
            return view('admin.cloudflare.edit-record', compact('domain', 'record'));
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
            'domain' => 'required|string',
            'record_id' => 'required|string',
            'type' => 'required|string',
            'name' => 'required|string',
            'content' => 'required|string',
            'ttl' => 'nullable|integer|min:1',
            'priority' => 'nullable|integer'
        ]);
        
        try {
            $result = $this->updateDnsRecord(
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
                    ->route('admin.cloudflare.dns-records', ['domain' => $request->domain])
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
        $recordId = $request->input('record_id');
        
        if (!$domain || !$recordId) {
            return response()->json(['success' => false, 'error' => 'Domain and record ID are required'], 400);
        }
        
        try {
            $result = $this->deleteDnsRecord($domain, $recordId);
            
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
        
        if (!$domain) {
            return response()->json(['success' => false, 'error' => 'Domain is required'], 400);
        }
        
        try {
            $result = $this->getNameservers($domain);
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
        $type = $request->input('type');
        
        if (!$domain) {
            return response()->json(['success' => false, 'error' => 'Domain is required'], 400);
        }
        
        try {
            $result = $this->getDnsRecords($domain, $type);
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
            'nameservers' => $response['result']['name_servers'],
            'status' => $response['result']['status']
        ];
    }
    
    /**
     * Get DNS records for a domain
     */
    private function getDnsRecords($domain, $type = null)
    {
        $zoneId = $this->getZoneId($domain);
        $endpoint = 'zones/' . $zoneId . '/dns_records';
        
        if ($type) {
            $endpoint .= '?type=' . urlencode($type);
        }
        
        $response = $this->makeRequest($endpoint);
        
        return [
            'success' => true,
            'domain' => $domain,
            'records' => $response['result']
        ];
    }
    
    /**
     * Add DNS record
     */
    private function addDnsRecord($domain, $type, $name, $content, $ttl = 1, $priority = null)
    {
        $zoneId = $this->getZoneId($domain);
        
        $data = [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => (int)$ttl
        ];
        
        if (strtoupper($type) === 'MX' && $priority !== null) {
            $data['priority'] = $priority;
        }
        
        $response = $this->makeRequest('zones/' . $zoneId . '/dns_records', 'POST', $data);
        
        return [
            'success' => true,
            'message' => 'DNS record added successfully',
            'record' => $response['result']
        ];
    }
    
    /**
     * Update DNS record
     */
    private function updateDnsRecord($domain, $recordId, $type, $name, $content, $ttl = 1, $priority = null)
    {
        $zoneId = $this->getZoneId($domain);
        
        $data = [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => (int)$ttl
        ];
        
        if (strtoupper($type) === 'MX' && $priority !== null) {
            $data['priority'] = $priority;
        }
        
        $response = $this->makeRequest('zones/' . $zoneId . '/dns_records/' . $recordId, 'PUT', $data);
        
        return [
            'success' => true,
            'message' => 'DNS record updated successfully',
            'record' => $response['result']
        ];
    }
    
    /**
     * Delete DNS record
     */
    private function deleteDnsRecord($domain, $recordId)
    {
        $zoneId = $this->getZoneId($domain);
        $this->makeRequest('zones/' . $zoneId . '/dns_records/' . $recordId, 'DELETE');
        
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
        $response = $this->makeRequest($account, 'zones?name=' . urlencode($domain));
        
        if (empty($response['result'])) {
            throw new Exception('Domain not found in Cloudflare account: ' . $domain);
        }
        
        return $response['result'][0]['id'];
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