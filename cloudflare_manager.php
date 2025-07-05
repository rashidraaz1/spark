<?php

class CloudflareManager {
    private $apiKey;
    private $email;
    private $baseUrl = 'https://api.cloudflare.com/client/v4/';
    
    public function __construct($email, $apiKey) {
        $this->email = $email;
        $this->apiKey = $apiKey;
    }
    
    /**
     * Make API request to Cloudflare
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'X-Auth-Email: ' . $this->email,
            'X-Auth-Key: ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = isset($decodedResponse['errors'][0]['message']) 
                ? $decodedResponse['errors'][0]['message'] 
                : 'HTTP Error: ' . $httpCode;
            throw new Exception($errorMsg);
        }
        
        return $decodedResponse;
    }
    
    /**
     * Get zone ID for a domain
     */
    private function getZoneId($domain) {
        $response = $this->makeRequest('zones?name=' . urlencode($domain));
        
        if (empty($response['result'])) {
            throw new Exception('Domain not found in Cloudflare account: ' . $domain);
        }
        
        return $response['result'][0]['id'];
    }
    
    /**
     * Get Cloudflare nameservers for a domain
     */
    public function getNameservers($domain) {
        try {
            $zoneId = $this->getZoneId($domain);
            $response = $this->makeRequest('zones/' . $zoneId);
            
            return [
                'success' => true,
                'domain' => $domain,
                'nameservers' => $response['result']['name_servers'],
                'status' => $response['result']['status']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all DNS records for a domain
     */
    public function getDnsRecords($domain, $type = null) {
        try {
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
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Add a new DNS record
     */
    public function addDnsRecord($domain, $type, $name, $content, $ttl = 1, $priority = null) {
        try {
            $zoneId = $this->getZoneId($domain);
            
            $data = [
                'type' => strtoupper($type),
                'name' => $name,
                'content' => $content,
                'ttl' => $ttl
            ];
            
            // Add priority for MX records
            if (strtoupper($type) === 'MX' && $priority !== null) {
                $data['priority'] = $priority;
            }
            
            $response = $this->makeRequest('zones/' . $zoneId . '/dns_records', 'POST', $data);
            
            return [
                'success' => true,
                'message' => 'DNS record added successfully',
                'record' => $response['result']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update an existing DNS record
     */
    public function updateDnsRecord($domain, $recordId, $type, $name, $content, $ttl = 1, $priority = null) {
        try {
            $zoneId = $this->getZoneId($domain);
            
            $data = [
                'type' => strtoupper($type),
                'name' => $name,
                'content' => $content,
                'ttl' => $ttl
            ];
            
            // Add priority for MX records
            if (strtoupper($type) === 'MX' && $priority !== null) {
                $data['priority'] = $priority;
            }
            
            $response = $this->makeRequest('zones/' . $zoneId . '/dns_records/' . $recordId, 'PUT', $data);
            
            return [
                'success' => true,
                'message' => 'DNS record updated successfully',
                'record' => $response['result']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a DNS record
     */
    public function deleteDnsRecord($domain, $recordId) {
        try {
            $zoneId = $this->getZoneId($domain);
            $response = $this->makeRequest('zones/' . $zoneId . '/dns_records/' . $recordId, 'DELETE');
            
            return [
                'success' => true,
                'message' => 'DNS record deleted successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Find DNS record by name and type
     */
    public function findDnsRecord($domain, $name, $type = null) {
        try {
            $zoneId = $this->getZoneId($domain);
            $endpoint = 'zones/' . $zoneId . '/dns_records?name=' . urlencode($name);
            
            if ($type) {
                $endpoint .= '&type=' . urlencode($type);
            }
            
            $response = $this->makeRequest($endpoint);
            
            return [
                'success' => true,
                'records' => $response['result']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

?>