<?php

namespace App\Modules\Commerce\Models;

use App\Modules\User\Models\Admin;
use App\Modules\User\Models\Vendor;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'discount_type',
        'discount_value',
        'banner_image',
        'start_date',
        'end_date',
        'is_active',
        'vendor_id',
        'status',
        'reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'approved_at' => 'datetime',
        'discount_value' => 'decimal:2',
    ];

    /**
     * Get the vendor that owns the promotion request
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the admin who approved the promotion
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /**
     * Check if promotion is currently active
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active 
            && $this->status === 'approved'
            && now()->between($this->start_date, $this->end_date);
    }

    /**
     * Check if promotion is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->is_active 
            && $this->status === 'approved'
            && now()->lt($this->start_date);
    }

    /**
     * Check if promotion is expired
     */
    public function isExpired(): bool
    {
        return now()->gt($this->end_date);
    }

    /**
     * Scope for active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('status', 'approved')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope for scheduled promotions
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_active', true)
            ->where('status', 'approved')
            ->where('start_date', '>', now());
    }

    /**
     * Scope for expired promotions
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved promotions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected promotions
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
