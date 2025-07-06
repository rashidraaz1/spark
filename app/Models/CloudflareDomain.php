<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CloudflareDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'cloudflare_account_id',
        'zone_id',
        'domain_name',
        'status',
        'nameservers',
        'plan_name',
        'plan_id',
        'dns_records_count',
        'created_on_cloudflare',
        'modified_on_cloudflare',
        'last_synced_at',
        'sync_status',
        'is_active'
    ];

    protected $casts = [
        'nameservers' => 'array',
        'dns_records_count' => 'integer',
        'created_on_cloudflare' => 'datetime',
        'modified_on_cloudflare' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the Cloudflare account that owns this domain
     */
    public function cloudflareAccount()
    {
        return $this->belongsTo(CloudflareAccount::class);
    }

    /**
     * Get all DNS records for this domain
     */
    public function dnsRecords()
    {
        return $this->hasMany(CloudflareDnsRecord::class);
    }

    /**
     * Get active DNS records for this domain
     */
    public function activeDnsRecords()
    {
        return $this->hasMany(CloudflareDnsRecord::class)->where('sync_status', 'synced');
    }

    /**
     * Scope for active domains only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for synced domains only
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Mark domain as synced
     */
    public function markAsSynced()
    {
        $this->update([
            'sync_status' => 'synced',
            'last_synced_at' => now()
        ]);
    }

    /**
     * Mark domain sync as failed
     */
    public function markSyncFailed()
    {
        $this->update(['sync_status' => 'failed']);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute()
    {
        switch ($this->status) {
            case 'active':
                return 'label-success';
            case 'pending':
                return 'label-warning';
            case 'inactive':
                return 'label-default';
            default:
                return 'label-info';
        }
    }

    /**
     * Get sync status badge class for UI
     */
    public function getSyncStatusBadgeClassAttribute()
    {
        switch ($this->sync_status) {
            case 'synced':
                return 'label-success';
            case 'pending':
                return 'label-warning';
            case 'failed':
                return 'label-danger';
            default:
                return 'label-default';
        }
    }

    /**
     * Get nameservers as a formatted string
     */
    public function getNameserversStringAttribute()
    {
        return $this->nameservers ? implode(', ', $this->nameservers) : 'N/A';
    }
}