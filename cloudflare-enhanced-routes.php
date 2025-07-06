// Add these routes to your web.php file

// Enhanced Cloudflare Management Routes with Multi-Account Support
Route::prefix('admin/cloudflare')->name('admin.cloudflare.')->middleware('admin')->group(function () {
    
    // Main Dashboard
    Route::get('/', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'index'])->name('index');
    
    // Account Management
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'store'])->name('store');
        Route::get('/{account}', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'show'])->name('show');
        Route::get('/{account}/edit', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'destroy'])->name('destroy');
        
        // Account Actions
        Route::post('/{account}/sync', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'syncDomains'])->name('sync');
        Route::post('/{account}/test-connection', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'testAccountConnection'])->name('test-connection');
        Route::post('/test-connection', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'testConnection'])->name('test-connection.new');
        Route::post('/sync-all', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'syncAllAccounts'])->name('sync-all');
        Route::post('/add-domain', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'addDomain'])->name('add-domain');
    });
    
    // DNS Management (Updated for Account Selection)
    Route::get('/nameservers', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'nameservers'])->name('nameservers');
    Route::get('/dns-records', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'dnsRecords'])->name('dns-records');
    Route::get('/add-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'addRecordForm'])->name('add-record');
    Route::post('/add-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'addRecord'])->name('add-record.store');
    Route::get('/edit-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'editRecordForm'])->name('edit-record');
    Route::post('/edit-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'updateRecord'])->name('edit-record.update');
    Route::delete('/delete-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'deleteRecord'])->name('delete-record');
    Route::post('/delete-record', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'deleteRecord'])->name('delete-record.post');
    
    // API Routes
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/nameservers', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'apiNameservers'])->name('nameservers');
        Route::get('/dns-records', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'apiDnsRecords'])->name('dns-records');
        Route::get('/accounts/{account}/domains', [App\Http\Controllers\Admin\Cloudflare\CloudflareAccountController::class, 'getAccountDomains'])->name('accounts.domains');
        Route::get('/debug-account', [App\Http\Controllers\Admin\Cloudflare\CloudflareController::class, 'debugAccount'])->name('debug-account');
    });
});