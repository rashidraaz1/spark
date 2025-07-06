<?php
/**
 * Cloudflare Files Download Script
 * 
 * This script creates a ZIP file with all Cloudflare management system files
 * so you can easily upload them to your server
 */

// Set the content type and filename
$filename = 'cloudflare-management-system-' . date('Y-m-d-H-i-s') . '.zip';

// Create a temporary file
$tempFile = tempnam(sys_get_temp_dir(), 'cloudflare_download');

// Create ZIP archive
$zip = new ZipArchive();
$zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Files to include in the ZIP
$files = [
    // Controllers
    'app/Http/Controllers/Admin/Cloudflare/CloudflareController.php' => getCloudflareController(),
    'app/Http/Controllers/Admin/Cloudflare/CloudflareAccountController.php' => getCloudflareAccountController(),
    
    // Models
    'app/Models/CloudflareAccount.php' => getCloudflareAccountModel(),
    'app/Models/CloudflareDomain.php' => getCloudfareDomainModel(),
    'app/Models/CloudflareDnsRecord.php' => getCloudfareDnsRecordModel(),
    
    // Views - Account Management
    'resources/views/admin/cloudflare/accounts/index.blade.php' => getAccountsIndexView(),
    'resources/views/admin/cloudflare/accounts/create.blade.php' => getAccountsCreateView(),
    'resources/views/admin/cloudflare/accounts/edit.blade.php' => getAccountsEditView(),
    'resources/views/admin/cloudflare/accounts/show.blade.php' => getAccountsShowView(),
    
    // Views - DNS Management
    'resources/views/admin/cloudflare/index.blade.php' => getMainIndexView(),
    'resources/views/admin/cloudflare/nameservers.blade.php' => getNameserversView(),
    'resources/views/admin/cloudflare/dns-records.blade.php' => getDnsRecordsView(),
    'resources/views/admin/cloudflare/add-record.blade.php' => getAddRecordView(),
    'resources/views/admin/cloudflare/edit-record.blade.php' => getEditRecordView(),
    
    // Configuration and Routes
    'routes/cloudflare-routes.php' => getRoutes(),
    'config/cloudflare-config.php' => getConfig(),
    
    // Database
    'database/cloudflare-tables.sql' => getDatabaseTables(),
    'database/cloudflare-migration.php' => getMigrationFile(),
    
    // Documentation
    'README.md' => getReadme(),
    'INSTALLATION.md' => getInstallationGuide(),
    'debug_cloudflare.php' => getDebugScript()
];

// Add files to ZIP
foreach ($files as $filename => $content) {
    $zip->addFromString($filename, $content);
}

$zip->close();

// Download the file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tempFile));

readfile($tempFile);
unlink($tempFile);
exit;

// File contents functions
function getCloudflareController() {
    return '<?php

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
    private $baseUrl = "https://api.cloudflare.com/client/v4/";
    
    public function __construct()
    {
        // Uncomment this line in production
        // $this->middleware("admin");
    }
    
    /**
     * Display Cloudflare DNS management dashboard
     */
    public function index()
    {
        $accounts = CloudflareAccount::active()->with("domains")->get();
        $totalDomains = CloudflareDomain::count();
        $totalRecords = CloudflareDnsRecord::count();
        
        return view("admin.cloudflare.index", compact("accounts", "totalDomains", "totalRecords"));
    }
    
    /**
     * Show nameservers for a domain
     */
    public function nameservers(Request $request)
    {
        $accounts = CloudflareAccount::active()->get();
        $domain = $request->input("domain");
        $accountId = $request->input("account_id");
        
        if (!$domain || !$accountId) {
            return view("admin.cloudflare.nameservers", compact("accounts"));
        }
        
        try {
            $account = CloudflareAccount::findOrFail($accountId);
            $nameservers = $this->getNameservers($account, $domain);
            return view("admin.cloudflare.nameservers", compact("nameservers", "domain", "accounts", "accountId"));
        } catch (Exception $e) {
            return view("admin.cloudflare.nameservers", [
                "error" => $e->getMessage(),
                "domain" => $domain,
                "accounts" => $accounts,
                "accountId" => $accountId
            ]);
        }
    }
    
    /**
     * Get Cloudflare nameservers for a domain
     */
    private function getNameservers(CloudflareAccount $account, $domain)
    {
        $zoneId = $this->getZoneId($account, $domain);
        $response = $this->makeRequest($account, "zones/" . $zoneId);
        
        return [
            "success" => true,
            "domain" => $domain,
            "nameservers" => $response["result"]["name_servers"] ?? [],
            "status" => $response["result"]["status"] ?? "unknown"
        ];
    }
    
    /**
     * Get zone ID for a domain
     */
    private function getZoneId(CloudflareAccount $account, $domain)
    {
        // First try to get from local database
        $localDomain = CloudflareDomain::where("cloudflare_account_id", $account->id)
            ->where("domain_name", $domain)
            ->first();
            
        if ($localDomain && $localDomain->zone_id) {
            return $localDomain->zone_id;
        }
        
        // If not found locally, fetch from Cloudflare API
        $response = $this->makeRequest($account, "zones?name=" . urlencode($domain));
        
        if (empty($response["result"])) {
            throw new Exception("Domain not found in Cloudflare account: " . $domain);
        }
        
        $zoneId = $response["result"][0]["id"];
        
        // Update local database if domain exists
        if ($localDomain) {
            $localDomain->update(["zone_id" => $zoneId]);
        }
        
        return $zoneId;
    }
    
    /**
     * Make API request to Cloudflare
     */
    private function makeRequest(CloudflareAccount $account, $endpoint, $method = "GET", $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        
        // Debug: Log the account data to see what we are working with
        Log::info("Making Cloudflare API request", [
            "account_id" => $account->id,
            "email" => $account->email,
            "api_key_length" => strlen($account->api_key ?? ""),
            "endpoint" => $endpoint,
            "method" => $method
        ]);
        
        // Ensure we have valid credentials
        if (empty($account->email) || empty($account->api_key)) {
            throw new Exception("Missing API credentials. Email: " . ($account->email ? "present" : "missing") . ", API Key: " . ($account->api_key ? "present" : "missing"));
        }
        
        $headers = [
            "X-Auth-Email" => trim($account->email),
            "X-Auth-Key" => trim($account->api_key),
            "Content-Type" => "application/json",
            "User-Agent" => "Laravel-Cloudflare-Manager/1.0"
        ];
        
        $request = Http::withHeaders($headers)->timeout(30);
        
        try {
            if ($method === "GET") {
                $response = $request->get($url);
            } elseif ($method === "POST") {
                $response = $request->post($url, $data);
            } elseif ($method === "PUT") {
                $response = $request->put($url, $data);
            } elseif ($method === "DELETE") {
                $response = $request->delete($url);
            }
            
            $responseData = $response->json();
            
            if (!$response->successful()) {
                $errorMsg = "HTTP Error: " . $response->status();
                if (isset($responseData["errors"][0]["message"])) {
                    $errorMsg = $responseData["errors"][0]["message"];
                }
                throw new Exception($errorMsg);
            }
            
            if (!isset($responseData["success"]) || !$responseData["success"]) {
                $errorMsg = "API request failed";
                if (isset($responseData["errors"][0]["message"])) {
                    $errorMsg = $responseData["errors"][0]["message"];
                }
                throw new Exception($errorMsg);
            }
            
            return $responseData;
            
        } catch (Exception $e) {
            Log::error("Cloudflare API Error: " . $e->getMessage(), [
                "account_id" => $account->id,
                "endpoint" => $endpoint,
                "method" => $method,
                "data" => $data
            ]);
            throw $e;
        }
    }
}';
}

function getReadme() {
    return '# Cloudflare Management System

A comprehensive Laravel-based system for managing multiple Cloudflare accounts, domains, and DNS records.

## Features

- ✅ Multi-account Cloudflare management
- ✅ Encrypted API credential storage
- ✅ DNS record management (A, AAAA, CNAME, MX, TXT, NS)
- ✅ Domain synchronization
- ✅ Nameserver management
- ✅ Real-time API testing
- ✅ Enhanced error handling

## Quick Start

1. Upload all files to your Laravel application
2. Run the database migration
3. Copy routes to your web.php file
4. Clear Laravel cache
5. Add your Cloudflare account

## File Structure

```
app/
├── Http/Controllers/Admin/Cloudflare/
│   ├── CloudflareController.php
│   └── CloudflareAccountController.php
├── Models/
│   ├── CloudflareAccount.php
│   ├── CloudflareDomain.php
│   └── CloudflareDnsRecord.php
resources/views/admin/cloudflare/
├── accounts/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── index.blade.php
├── nameservers.blade.php
├── dns-records.blade.php
├── add-record.blade.php
└── edit-record.blade.php
```

## Support

- Use the debug script to test API connections
- Check Laravel logs for detailed error information
- Refer to INSTALLATION.md for detailed setup instructions

Created with ❤️ for Laravel developers';
}

function getInstallationGuide() {
    return '# Installation Guide

## Step 1: Upload Files

Upload all files from this ZIP to your Laravel application maintaining the directory structure.

## Step 2: Database Setup

Run the SQL commands in `database/cloudflare-tables.sql` or use the migration file.

```bash
php artisan migrate --path=database/migrations/cloudflare_migration.php
```

## Step 3: Routes Setup

Copy the routes from `routes/cloudflare-routes.php` to your `routes/web.php` file.

## Step 4: Clear Cache

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Step 5: Test Installation

1. Upload `debug_cloudflare.php` to your Laravel root
2. Visit: `https://yourdomain.com/debug_cloudflare.php`
3. Test your Cloudflare credentials

## Step 6: Add Your First Account

1. Go to `/admin/cloudflare/accounts`
2. Click "Add New Account"
3. Enter your Cloudflare email and API key
4. Test connection before saving

## Troubleshooting

### Invalid Request Headers
- Check your email and API key are correct
- Use the debug script to test credentials
- Ensure API key has proper permissions

### 404 Errors
- Make sure routes are properly copied
- Clear Laravel cache
- Check route:list for conflicts

### Missing Views
- Ensure all view files are uploaded
- Check file permissions
- Clear view cache

## Getting Your API Key

1. Login to [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Go to My Profile → API Tokens
3. Find "Global API Key" and click "View"
4. Copy the key for use in the system

## Security Notes

- API keys are automatically encrypted in the database
- Never expose API keys in client-side code
- Use middleware protection in production
- Regularly rotate your API keys';
}

function getDebugScript() {
    return file_get_contents('debug_cloudflare.php');
}

// Add more file content functions as needed...

?>
<!DOCTYPE html>
<html>
<head>
    <title>Download Cloudflare Management Files</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; text-align: center; }
        .download-btn { 
            background: #007bff; color: white; padding: 15px 30px; 
            text-decoration: none; border-radius: 5px; font-size: 18px;
        }
        .download-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>📦 Cloudflare Management System</h1>
    <p>Click the button below to download all files as a ZIP package:</p>
    <a href="?download=1" class="download-btn">⬇️ Download ZIP File</a>
    
    <?php if (isset($_GET['download'])): ?>
        <script>
            // Auto-start download
            window.location.href = window.location.href;
        </script>
    <?php endif; ?>
</body>
</html>';