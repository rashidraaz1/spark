# Cloudflare DNS Manager

This PHP library provides functionality to manage Cloudflare DNS records and retrieve nameservers through the Cloudflare API.

## Features

- ✅ Get Cloudflare nameservers for a domain
- ✅ List all DNS records for a domain
- ✅ Filter DNS records by type (A, AAAA, CNAME, MX, etc.)
- ✅ Add new DNS records
- ✅ Update existing DNS records
- ✅ Delete DNS records
- ✅ Find DNS records by name and type
- ✅ RESTful API endpoints
- ✅ JSON responses
- ✅ Error handling

## Files

- `cloudflare_manager.php` - Main CloudflareManager class
- `cloudflare_api.php` - RESTful API endpoints
- `example_usage.php` - Usage examples
- `config.example.php` - Configuration template

## Setup

1. **Get Cloudflare API credentials:**
   - Go to [Cloudflare Dashboard](https://dash.cloudflare.com/profile/api-tokens)
   - Either use your Global API Key + Email
   - Or create an API Token (recommended)

2. **Configure credentials:**
   - Edit `cloudflare_api.php` and update these lines:
   ```php
   $CLOUDFLARE_EMAIL = 'your-email@example.com';
   $CLOUDFLARE_API_KEY = 'your-api-key-here';
   ```

3. **Upload files to your server:**
   - Upload all PHP files to `/home/smartfee/public_html/sparkpostapi/`

## API Usage

### Base URL
```
https://smartfeed.spunkydeals.com/sparkpostapi/cloudflare_api.php
```

### Available Endpoints

#### 1. Get Help
```bash
GET ?action=help
```

#### 2. Get Nameservers
```bash
GET ?action=nameservers&domain=example.com
```

**Response:**
```json
{
    "success": true,
    "domain": "example.com",
    "nameservers": [
        "alice.ns.cloudflare.com",
        "bob.ns.cloudflare.com"
    ],
    "status": "active"
}
```

#### 3. Get DNS Records
```bash
# Get all records
GET ?action=records&domain=example.com

# Get specific type
GET ?action=records&domain=example.com&type=A
```

**Response:**
```json
{
    "success": true,
    "domain": "example.com",
    "records": [
        {
            "id": "rec_id_123",
            "type": "A",
            "name": "example.com",
            "content": "192.168.1.1",
            "ttl": 1
        }
    ]
}
```

#### 4. Add DNS Record
```bash
POST ?action=add&domain=example.com
Content-Type: application/json

{
    "type": "A",
    "name": "subdomain",
    "content": "192.168.1.1",
    "ttl": 300
}
```

#### 5. Update DNS Record
```bash
POST ?action=update&domain=example.com
Content-Type: application/json

{
    "record_id": "rec_id_123",
    "type": "A",
    "name": "subdomain",
    "content": "192.168.1.2",
    "ttl": 300
}
```

#### 6. Delete DNS Record
```bash
DELETE ?action=delete&domain=example.com&record_id=rec_id_123
```

#### 7. Find DNS Record
```bash
GET ?action=find&domain=example.com&name=subdomain&type=A
```

## PHP Class Usage

```php
require_once 'cloudflare_manager.php';

// Initialize
$cf = new CloudflareManager('your-email@example.com', 'your-api-key');

// Get nameservers
$nameservers = $cf->getNameservers('example.com');
if ($nameservers['success']) {
    print_r($nameservers['nameservers']);
}

// Get DNS records
$records = $cf->getDnsRecords('example.com');
if ($records['success']) {
    foreach ($records['records'] as $record) {
        echo $record['name'] . ' -> ' . $record['content'] . "\n";
    }
}

// Add DNS record
$result = $cf->addDnsRecord('example.com', 'A', 'test', '192.168.1.1', 300);
if ($result['success']) {
    echo "Record added successfully\n";
}

// Update DNS record
$result = $cf->updateDnsRecord('example.com', 'record_id', 'A', 'test', '192.168.1.2', 300);

// Delete DNS record
$result = $cf->deleteDnsRecord('example.com', 'record_id');

// Find DNS record
$result = $cf->findDnsRecord('example.com', 'subdomain.example.com', 'A');
```

## CURL Examples

### Get Nameservers
```bash
curl "https://smartfeed.spunkydeals.com/sparkpostapi/cloudflare_api.php?action=nameservers&domain=example.com"
```

### Add DNS Record
```bash
curl -X POST "https://smartfeed.spunkydeals.com/sparkpostapi/cloudflare_api.php?action=add&domain=example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "A",
    "name": "test",
    "content": "192.168.1.1",
    "ttl": 300
  }'
```

### Update DNS Record
```bash
curl -X POST "https://smartfeed.spunkydeals.com/sparkpostapi/cloudflare_api.php?action=update&domain=example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "record_id": "your_record_id",
    "type": "A",
    "name": "test",
    "content": "192.168.1.2",
    "ttl": 300
  }'
```

## Supported DNS Record Types

- A (IPv4 address)
- AAAA (IPv6 address)
- CNAME (Canonical name)
- MX (Mail exchange) - requires priority field
- TXT (Text)
- NS (Name server)
- SRV (Service) - requires priority, weight, port
- CAA (Certificate Authority Authorization)

## Error Handling

All responses include a `success` field. If `success` is `false`, an `error` field will contain the error message:

```json
{
    "success": false,
    "error": "Domain not found in Cloudflare account: example.com"
}
```

## Security Notes

1. **Protect your API credentials** - Never commit them to version control
2. **Use HTTPS** - Always use HTTPS in production
3. **Rate limiting** - Cloudflare API has rate limits, implement appropriate delays
4. **Input validation** - The API includes basic validation, but add more as needed
5. **Access control** - Consider adding authentication to your API endpoints

## Installation on Your Server

1. SSH into your server:
   ```bash
   ssh smartfee@51.222.249.118
   ```

2. Navigate to the directory:
   ```bash
   cd /home/smartfee/public_html/sparkpostapi
   ```

3. Upload these files:
   - `cloudflare_manager.php`
   - `cloudflare_api.php`
   - `example_usage.php`

4. Update the credentials in `cloudflare_api.php`

5. Test the API:
   ```bash
   curl "https://smartfeed.spunkydeals.com/sparkpostapi/cloudflare_api.php?action=help"
   ```

## Troubleshooting

1. **Invalid credentials**: Verify your Cloudflare email and API key
2. **Domain not found**: Ensure the domain is added to your Cloudflare account
3. **cURL errors**: Check if cURL is enabled in PHP
4. **Permission errors**: Ensure PHP files have proper permissions (644)

## License

This code is provided as-is for educational and practical use.