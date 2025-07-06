<?php
/**
 * Cloudflare API Debug Script
 * 
 * This script helps debug Cloudflare API connection issues
 * Place this file in your Laravel root directory and run it via browser
 */

// Basic config
$debug = true;
$errors = [];
$results = [];

// Test 1: Check if we can connect to Cloudflare
$results['connectivity_test'] = testConnectivity();

// Test 2: Test your specific credentials
if (isset($_POST['test_credentials'])) {
    $email = $_POST['email'] ?? '';
    $apiKey = $_POST['api_key'] ?? '';
    
    if ($email && $apiKey) {
        $results['credential_test'] = testCredentials($email, $apiKey);
    } else {
        $errors[] = 'Please provide both email and API key';
    }
}

function testConnectivity() {
    $url = 'https://api.cloudflare.com/client/v4/user';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 403, // 403 means we connected but need auth
        'http_code' => $httpCode,
        'curl_error' => $error,
        'response' => $response ? json_decode($response, true) : null
    ];
}

function testCredentials($email, $apiKey) {
    $url = 'https://api.cloudflare.com/client/v4/user/tokens/verify';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Auth-Email: ' . trim($email),
        'X-Auth-Key: ' . trim($apiKey),
        'Content-Type: application/json',
        'User-Agent: Debug-Script/1.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $result = [
        'success' => $httpCode === 200,
        'http_code' => $httpCode,
        'curl_error' => $error,
        'response' => $response ? json_decode($response, true) : null,
        'email_length' => strlen($email),
        'api_key_length' => strlen($apiKey)
    ];
    
    // Test zones endpoint
    if ($result['success']) {
        $zonesUrl = 'https://api.cloudflare.com/client/v4/zones';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zonesUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Auth-Email: ' . trim($email),
            'X-Auth-Key: ' . trim($apiKey),
            'Content-Type: application/json'
        ]);
        
        $zonesResponse = curl_exec($ch);
        $zonesHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result['zones_test'] = [
            'success' => $zonesHttpCode === 200,
            'http_code' => $zonesHttpCode,
            'response' => $zonesResponse ? json_decode($zonesResponse, true) : null
        ];
    }
    
    return $result;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cloudflare API Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; 
        }
        button { 
            background: #007bff; color: white; padding: 10px 20px; border: none; 
            border-radius: 4px; cursor: pointer; 
        }
        button:hover { background: #0056b3; }
        .status-box { 
            padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid;
        }
        .status-success { background: #d4edda; border-color: #28a745; }
        .status-error { background: #f8d7da; border-color: #dc3545; }
        .status-warning { background: #fff3cd; border-color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Cloudflare API Debug Tool</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="status-box status-error">
                <h3>❌ Errors:</h3>
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="status-box status-<?= $results['connectivity_test']['success'] ? 'success' : 'error' ?>">
            <h3>🌐 Connectivity Test</h3>
            <p><strong>Status:</strong> <?= $results['connectivity_test']['success'] ? '✅ Can reach Cloudflare API' : '❌ Cannot reach Cloudflare API' ?></p>
            <p><strong>HTTP Code:</strong> <?= $results['connectivity_test']['http_code'] ?></p>
            <?php if ($results['connectivity_test']['curl_error']): ?>
                <p><strong>CURL Error:</strong> <?= htmlspecialchars($results['connectivity_test']['curl_error']) ?></p>
            <?php endif; ?>
        </div>
        
        <h2>🔑 Test Your API Credentials</h2>
        <form method="POST">
            <div class="form-group">
                <label>Cloudflare Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Cloudflare Global API Key:</label>
                <input type="password" name="api_key" value="<?= htmlspecialchars($_POST['api_key'] ?? '') ?>" required>
                <small>Get this from: <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">Cloudflare Dashboard → My Profile → API Tokens</a></small>
            </div>
            
            <button type="submit" name="test_credentials">🧪 Test Credentials</button>
        </form>
        
        <?php if (isset($results['credential_test'])): ?>
            <div class="status-box status-<?= $results['credential_test']['success'] ? 'success' : 'error' ?>">
                <h3>🔐 Credential Test Results</h3>
                <p><strong>Status:</strong> <?= $results['credential_test']['success'] ? '✅ Credentials Valid' : '❌ Credentials Invalid' ?></p>
                <p><strong>HTTP Code:</strong> <?= $results['credential_test']['http_code'] ?></p>
                <p><strong>Email Length:</strong> <?= $results['credential_test']['email_length'] ?> characters</p>
                <p><strong>API Key Length:</strong> <?= $results['credential_test']['api_key_length'] ?> characters</p>
                
                <?php if ($results['credential_test']['curl_error']): ?>
                    <p><strong>CURL Error:</strong> <?= htmlspecialchars($results['credential_test']['curl_error']) ?></p>
                <?php endif; ?>
                
                <?php if (isset($results['credential_test']['zones_test'])): ?>
                    <h4>🌍 Zones Access Test</h4>
                    <p><strong>Status:</strong> <?= $results['credential_test']['zones_test']['success'] ? '✅ Can access zones' : '❌ Cannot access zones' ?></p>
                    <p><strong>HTTP Code:</strong> <?= $results['credential_test']['zones_test']['http_code'] ?></p>
                    
                    <?php if ($results['credential_test']['zones_test']['success']): ?>
                        <p><strong>Domains Found:</strong> <?= count($results['credential_test']['zones_test']['response']['result'] ?? []) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($debug && $results['credential_test']['response']): ?>
                    <h4>📋 Raw API Response:</h4>
                    <pre><?= htmlspecialchars(json_encode($results['credential_test']['response'], JSON_PRETTY_PRINT)) ?></pre>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="status-box status-warning">
            <h3>💡 Troubleshooting Tips</h3>
            <ul>
                <li><strong>Invalid request headers:</strong> Usually means wrong email or API key</li>
                <li><strong>HTTP 403:</strong> Forbidden - Check your API key permissions</li>
                <li><strong>HTTP 401:</strong> Unauthorized - Email or API key is incorrect</li>
                <li><strong>HTTP 429:</strong> Rate limited - Wait a few minutes and try again</li>
                <li><strong>CURL errors:</strong> Network connectivity issues</li>
            </ul>
        </div>
        
        <div class="status-box status-warning">
            <h3>🔧 Laravel Integration Check</h3>
            <p>After fixing your credentials, make sure to:</p>
            <ol>
                <li>Update your account in the database with correct credentials</li>
                <li>Clear Laravel cache: <code>php artisan cache:clear</code></li>
                <li>Check Laravel logs: <code>storage/logs/laravel.log</code></li>
                <li>Test in Laravel: <code>/admin/cloudflare/api/debug-account?account_id=1</code></li>
            </ol>
        </div>
    </div>
</body>
</html>