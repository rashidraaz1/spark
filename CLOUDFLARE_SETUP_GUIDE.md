# Cloudflare DNS Management Integration Guide

This guide will help you integrate Cloudflare DNS management functionality into your existing Laravel application.

## 📋 Prerequisites

- Laravel application with admin panel
- Cloudflare account with domains
- Cloudflare API credentials

## 🚀 Installation Steps

### 1. Upload Files

Upload these files to your Laravel application:

```
app/Http/Controllers/Admin/Cloudflare/CloudflareController.php
resources/views/admin/cloudflare/index.blade.php
resources/views/admin/cloudflare/nameservers.blade.php
resources/views/admin/cloudflare/dns-records.blade.php
resources/views/admin/cloudflare/add-record.blade.php
resources/views/admin/cloudflare/edit-record.blade.php
```

### 2. Update Configuration

Add Cloudflare configuration to your `config/services.php`:

```php
'cloudflare' => [
    'email' => env('CLOUDFLARE_EMAIL'),
    'api_key' => env('CLOUDFLARE_API_KEY'),
],
```

### 3. Environment Variables

Add these variables to your `.env` file:

```env
CLOUDFLARE_EMAIL=your-cloudflare-email@example.com
CLOUDFLARE_API_KEY=your-global-api-key-here
```

**To get your Cloudflare API Key:**
1. Log in to [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Go to **My Profile** → **API Tokens**
3. Find your **Global API Key** and click **View**

### 4. Add Routes

Add these routes to your `routes/web.php` file in the admin section:

```php
// Cloudflare DNS Management Routes
Route::prefix('admin/cloudflare')->name('admin.cloudflare.')->middleware('admin')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'index'])->name('index');
    Route::get('/nameservers', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'nameservers'])->name('nameservers');
    Route::get('/dns-records', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'dnsRecords'])->name('dns-records');
    Route::get('/add-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'addRecordForm'])->name('add-record');
    Route::post('/add-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'addRecord'])->name('add-record.store');
    Route::get('/edit-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'editRecordForm'])->name('edit-record');
    Route::post('/edit-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'updateRecord'])->name('edit-record.update');
    Route::delete('/delete-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'deleteRecord'])->name('delete-record');
    
    // API Routes
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/nameservers', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'apiNameservers'])->name('nameservers');
        Route::get('/dns-records', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'apiDnsRecords'])->name('dns-records');
    });
});
```

### 5. Update Admin Menu

Add this menu section to your admin menu (after your existing Domain Manager dropdown):

```html
<li {!! Request::is('admin/cloudflare*') ? 'class="mm-active"' : '' !!}>
    <a href="#">
        <i class="livicon" data-name="cloud" data-c="#28a745" data-hc="#28a745" data-size="18" data-loop="true"></i>
        <span class="title">Cloudflare DNS</span>
        <span class="fas fa-angle-left"></span>
    </a>
    <ul class="sub-menu">
        <li {!! Request::is('admin/cloudflare') ? 'class="active"' : '' !!}>
            <a href="{{ route('admin.cloudflare.index') }}">
                <i class="fa fa-angle-double-right"></i> Dashboard
            </a>
        </li>
        <li {!! Request::is('admin/cloudflare/nameservers') ? 'class="active"' : '' !!}>
            <a href="{{ route('admin.cloudflare.nameservers') }}">
                <i class="fa fa-angle-double-right"></i> Nameservers
            </a>
        </li>
        <li {!! Request::is('admin/cloudflare/dns-records') ? 'class="active"' : '' !!}>
            <a href="{{ route('admin.cloudflare.dns-records') }}">
                <i class="fa fa-angle-double-right"></i> DNS Records
            </a>
        </li>
        <li {!! Request::is('admin/cloudflare/add-record') ? 'class="active"' : '' !!}>
            <a href="{{ route('admin.cloudflare.add-record') }}">
                <i class="fa fa-angle-double-right"></i> Add DNS Record
            </a>
        </li>
    </ul>
</li>
```

### 6. Clear Cache

Clear your Laravel caches:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## ✅ Features

### Dashboard
- Overview of all Cloudflare DNS management features
- Quick actions for checking nameservers and DNS records
- Help documentation

### Nameservers Management
- Check Cloudflare nameservers for any domain
- Copy nameservers to clipboard
- Instructions for updating domain registrar settings

### DNS Records Management
- View all DNS records for a domain
- Filter records by type (A, AAAA, CNAME, MX, TXT, NS)
- Add new DNS records
- Edit existing DNS records
- Delete DNS records
- Real-time updates

### Supported Record Types
- **A Records**: IPv4 addresses
- **AAAA Records**: IPv6 addresses
- **CNAME Records**: Canonical names
- **MX Records**: Mail exchange (with priority)
- **TXT Records**: Text records (SPF, DKIM, etc.)
- **NS Records**: Name servers

## 🔧 Usage

### Access the Dashboard
1. Login to your admin panel
2. Navigate to **Cloudflare DNS** → **Dashboard**

### Check Nameservers
1. Go to **Cloudflare DNS** → **Nameservers**
2. Enter your domain name
3. Click **Get Nameservers**
4. Copy the nameservers and update your domain registrar

### Manage DNS Records
1. Go to **Cloudflare DNS** → **DNS Records**
2. Enter your domain name
3. Optionally filter by record type
4. Click **Search** to view records

### Add DNS Record
1. Go to **Cloudflare DNS** → **Add DNS Record**
2. Fill in the form:
   - **Domain**: Your domain name
   - **Type**: Record type (A, CNAME, etc.)
   - **Name**: Subdomain or @ for root
   - **Content**: Target value
   - **TTL**: Time to live
   - **Priority**: For MX records only
3. Click **Add DNS Record**

### Edit DNS Record
1. From the DNS Records list, click the **Edit** button
2. Modify the record details
3. Click **Update DNS Record**

### Delete DNS Record
1. From the DNS Records list, click the **Delete** button
2. Confirm the deletion

## 🔒 Security Notes

1. **Protect API Credentials**: Never commit API keys to version control
2. **Use HTTPS**: Always use HTTPS in production
3. **Rate Limiting**: Cloudflare API has rate limits
4. **Access Control**: Ensure proper admin authentication
5. **Input Validation**: The system includes validation for all inputs

## 🐛 Troubleshooting

### Common Issues

**1. "Domain not found in Cloudflare account"**
- Ensure the domain is added to your Cloudflare account
- Check domain spelling

**2. "Invalid API credentials"**
- Verify your email and API key in `.env`
- Ensure API key has proper permissions

**3. "cURL Error"**
- Check internet connectivity
- Verify Cloudflare API is accessible

**4. "Route not found"**
- Clear route cache: `php artisan route:clear`
- Ensure routes are added correctly

### Debug Mode

To enable debugging, add this to your controller constructor:

```php
Log::info('Cloudflare Request', ['domain' => $domain, 'endpoint' => $endpoint]);
```

## 📱 API Endpoints

The system also provides API endpoints for programmatic access:

- `GET /admin/cloudflare/api/nameservers?domain=example.com`
- `GET /admin/cloudflare/api/dns-records?domain=example.com&type=A`

## 🔄 Future Enhancements

Potential future features:
- Bulk DNS record operations
- DNS record import/export
- DNS record validation
- Zone file management
- SSL certificate management
- Analytics and reporting

## 📞 Support

If you encounter any issues:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify Cloudflare API status
3. Review configuration settings
4. Test with a simple domain first

## 📄 License

This integration is provided as-is for educational and practical use with your Laravel application.

---

**Installation Date**: {DATE}
**Laravel Version**: Compatible with Laravel 8+
**PHP Version**: PHP 7.4+