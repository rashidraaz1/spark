<?php

require_once 'cloudflare_manager.php';

// Configuration - You need to set your Cloudflare credentials here
$CLOUDFLARE_EMAIL = 'your-email@example.com'; // Replace with your Cloudflare email
$CLOUDFLARE_API_KEY = 'your-api-key-here'; // Replace with your Cloudflare API key

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

// Function to get request data
function getRequestData() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: [];
}

try {
    // Initialize Cloudflare Manager
    $cf = new CloudflareManager($CLOUDFLARE_EMAIL, $CLOUDFLARE_API_KEY);
    
    // Get request method and action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $domain = $_GET['domain'] ?? $_POST['domain'] ?? '';
    
    // Validate domain
    if (empty($domain) && !in_array($action, ['help'])) {
        sendResponse(['success' => false, 'error' => 'Domain parameter is required'], 400);
    }
    
    switch ($action) {
        case 'help':
            sendResponse([
                'success' => true,
                'message' => 'Cloudflare DNS Manager API',
                'endpoints' => [
                    'GET ?action=nameservers&domain=example.com' => 'Get nameservers for domain',
                    'GET ?action=records&domain=example.com' => 'Get all DNS records for domain',
                    'GET ?action=records&domain=example.com&type=A' => 'Get DNS records of specific type',
                    'POST ?action=add' => 'Add DNS record (requires domain, type, name, content in POST body)',
                    'POST ?action=update' => 'Update DNS record (requires domain, record_id, type, name, content in POST body)',
                    'DELETE ?action=delete&domain=example.com&record_id=xxx' => 'Delete DNS record',
                    'GET ?action=find&domain=example.com&name=subdomain' => 'Find DNS record by name'
                ]
            ]);
            break;
            
        case 'nameservers':
            if ($method !== 'GET') {
                sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            }
            
            $result = $cf->getNameservers($domain);
            sendResponse($result);
            break;
            
        case 'records':
            if ($method !== 'GET') {
                sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            }
            
            $type = $_GET['type'] ?? null;
            $result = $cf->getDnsRecords($domain, $type);
            sendResponse($result);
            break;
            
        case 'add':
            if ($method !== 'POST') {
                sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            }
            
            $data = getRequestData();
            $required = ['type', 'name', 'content'];
            
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendResponse(['success' => false, 'error' => "Field '$field' is required"], 400);
                }
            }
            
            $ttl = $data['ttl'] ?? 1;
            $priority = $data['priority'] ?? null;
            
            $result = $cf->addDnsRecord($domain, $data['type'], $data['name'], $data['content'], $ttl, $priority);
            sendResponse($result);
            break;
            
        case 'update':
            if ($method !== 'POST' && $method !== 'PUT') {
                sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            }
            
            $data = getRequestData();
            $required = ['record_id', 'type', 'name', 'content'];
            
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendResponse(['success' => false, 'error' => "Field '$field' is required"], 400);
                }
            }
            
            $ttl = $data['ttl'] ?? 1;
            $priority = $data['priority'] ?? null;
            
            $result = $cf->updateDnsRecord($domain, $data['record_id'], $data['type'], $data['name'], $data['content'], $ttl, $priority);
            sendResponse($result);
            break;
            
        case 'delete':
            if ($method !== 'DELETE' && $method !== 'POST') {
                sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            }
            
            $recordId = $_GET['record_id'] ?? $_POST['record_id'] ?? '';
            if (empty($recordId)) {
                sendResponse(['success' => false, 'error' => 'record_id parameter is required'], 400);
            }
            
            $result = $cf->deleteDnsRecord($domain, $recordId);
            sendResponse($result);
            break;
            
        case 'find':
            if ($method !== 'GET') {
                sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            }
            
            $name = $_GET['name'] ?? '';
            $type = $_GET['type'] ?? null;
            
            if (empty($name)) {
                sendResponse(['success' => false, 'error' => 'name parameter is required'], 400);
            }
            
            $result = $cf->findDnsRecord($domain, $name, $type);
            sendResponse($result);
            break;
            
        default:
            sendResponse(['success' => false, 'error' => 'Invalid action. Use ?action=help for available endpoints'], 400);
            break;
    }
    
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
}

?>