<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CloudflareAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'api_key',
        'api_token',
        'is_active',
        'is_default',
        'last_synced_at',
        'total_domains',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_synced_at' => 'datetime',
        'total_domains' => 'integer',
    ];

    protected $hidden = [
        'api_key',
        'api_token'
    ];

    /**
     * Automatically encrypt the API key when saving
     */
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    /**
     * Automatically decrypt the API key when accessing
     */
    public function getApiKeyAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Automatically encrypt the API token when saving
     */
    public function setApiTokenAttribute($value)
    {
        $this->attributes['api_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Automatically decrypt the API token when accessing
     */
    public function getApiTokenAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all domains for this account
     */
    public function domains()
    {
        return $this->hasMany(CloudflareDomain::class);
    }

    /**
     * Get active domains for this account
     */
    public function activeDomains()
    {
        return $this->hasMany(CloudflareDomain::class)->where('is_active', true);
    }

    /**
     * Get the default account
     */
    public static function getDefault()
    {
        return self::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Set this account as default (and unset others)
     */
    public function setAsDefault()
    {
        // Unset all other default accounts
        self::where('is_default', true)->update(['is_default' => false]);
        
        // Set this as default
        $this->update(['is_default' => true, 'is_active' => true]);
    }

    /**
     * Scope for active accounts only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get display name for the account
     */
    public function getDisplayNameAttribute()
    {
        return $this->name ?: $this->email;
    }
}