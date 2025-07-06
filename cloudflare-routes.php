// Add these routes to your web.php file in the admin section

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