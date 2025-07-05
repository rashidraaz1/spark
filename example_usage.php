<?php

require_once 'cloudflare_manager.php';

// Example usage of the CloudflareManager class

// Initialize with your Cloudflare credentials
$email = 'your-email@example.com';
$apiKey = 'your-api-key-here';
$cf = new CloudflareManager($email, $apiKey);

// Example domain
$domain = 'example.com';

echo "=== Cloudflare DNS Manager Examples ===\n\n";

// 1. Get nameservers for a domain
echo "1. Getting nameservers for $domain:\n";
$nameservers = $cf->getNameservers($domain);
if ($nameservers['success']) {
    echo "Nameservers:\n";
    foreach ($nameservers['nameservers'] as $ns) {
        echo "  - $ns\n";
    }
    echo "Status: " . $nameservers['status'] . "\n\n";
} else {
    echo "Error: " . $nameservers['error'] . "\n\n";
}

// 2. Get all DNS records
echo "2. Getting all DNS records for $domain:\n";
$records = $cf->getDnsRecords($domain);
if ($records['success']) {
    echo "Found " . count($records['records']) . " DNS records:\n";
    foreach ($records['records'] as $record) {
        echo "  - {$record['name']} ({$record['type']}) -> {$record['content']}\n";
    }
    echo "\n";
} else {
    echo "Error: " . $records['error'] . "\n\n";
}

// 3. Get specific type of DNS records (e.g., A records)
echo "3. Getting A records for $domain:\n";
$aRecords = $cf->getDnsRecords($domain, 'A');
if ($aRecords['success']) {
    echo "Found " . count($aRecords['records']) . " A records:\n";
    foreach ($aRecords['records'] as $record) {
        echo "  - {$record['name']} -> {$record['content']}\n";
    }
    echo "\n";
} else {
    echo "Error: " . $aRecords['error'] . "\n\n";
}

// 4. Add a new DNS record (uncomment to test)
/*
echo "4. Adding a new A record:\n";
$addResult = $cf->addDnsRecord($domain, 'A', 'test', '192.168.1.1', 300);
if ($addResult['success']) {
    echo "Successfully added DNS record\n";
    echo "Record ID: " . $addResult['record']['id'] . "\n\n";
} else {
    echo "Error: " . $addResult['error'] . "\n\n";
}
*/

// 5. Find a specific DNS record
echo "5. Finding DNS record for 'www':\n";
$findResult = $cf->findDnsRecord($domain, 'www.' . $domain, 'A');
if ($findResult['success'] && !empty($findResult['records'])) {
    echo "Found records:\n";
    foreach ($findResult['records'] as $record) {
        echo "  - ID: {$record['id']}, Content: {$record['content']}\n";
    }
    echo "\n";
} else {
    echo "No records found or error occurred\n\n";
}

// 6. Update a DNS record (uncomment to test - you'll need a valid record ID)
/*
$recordId = 'your-record-id-here';
echo "6. Updating DNS record $recordId:\n";
$updateResult = $cf->updateDnsRecord($domain, $recordId, 'A', 'test', '192.168.1.2', 300);
if ($updateResult['success']) {
    echo "Successfully updated DNS record\n\n";
} else {
    echo "Error: " . $updateResult['error'] . "\n\n";
}
*/

// 7. Delete a DNS record (uncomment to test - you'll need a valid record ID)
/*
$recordId = 'your-record-id-here';
echo "7. Deleting DNS record $recordId:\n";
$deleteResult = $cf->deleteDnsRecord($domain, $recordId);
if ($deleteResult['success']) {
    echo "Successfully deleted DNS record\n\n";
} else {
    echo "Error: " . $deleteResult['error'] . "\n\n";
}
*/

echo "=== End of Examples ===\n";

?>