<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CloudflareDnsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'cloudflare_domain_id',
        'cloudflare_record_id',
        'zone_id',
        'type',
        'name',
        'content',
        'ttl',
        'priority',
        'proxied',
        'locked',
        'created_on_cloudflare',
        'modified_on_cloudflare',
        'last_synced_at',
        'sync_status'
    ];

    protected $casts = [
        'ttl' => 'integer',
        'priority' => 'integer',
        'proxied' => 'boolean',
        'locked' => 'boolean',
        'created_on_cloudflare' => 'datetime',
        'modified_on_cloudflare' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the domain that owns this DNS record
     */
    public function cloudflareDomain()
    {
        return $this->belongsTo(CloudflareDomain::class);
    }

    /**
     * Get the Cloudflare account through the domain
     */
    public function cloudflareAccount()
    {
        return $this->cloudflareDomain->cloudflareAccount();
    }

    /**
     * Scope for specific record types
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', strtoupper($type));
    }

    /**
     * Scope for synced records only
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Mark record as synced
     */
    public function markAsSynced()
    {
        $this->update([
            'sync_status' => 'synced',
            'last_synced_at' => now()
        ]);
    }

    /**
     * Mark record sync as failed
     */
    public function markSyncFailed()
    {
        $this->update(['sync_status' => 'failed']);
    }

    /**
     * Get record type badge class for UI
     */
    public function getTypeBadgeClassAttribute()
    {
        switch ($this->type) {
            case 'A':
                return 'label-primary';
            case 'AAAA':
                return 'label-info';
            case 'CNAME':
                return 'label-warning';
            case 'MX':
                return 'label-success';
            case 'TXT':
                return 'label-default';
            case 'NS':
                return 'label-danger';
            default:
                return 'label-default';
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
     * Get formatted TTL value for display
     */
    public function getFormattedTtlAttribute()
    {
        return $this->ttl == 1 ? 'Auto' : $this->ttl . 's';
    }

    /**
     * Get truncated content for table display
     */
    public function getTruncatedContentAttribute()
    {
        return strlen($this->content) > 50 ? substr($this->content, 0, 50) . '...' : $this->content;
    }
}