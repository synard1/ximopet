<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class Partner extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'type',
        'code',
        'name',
        'email',
        'address',
        'phone_number',
        'contact_person',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the supply purchases for this supplier
     */
    public function supplyPurchaseBatch()
    {
        return $this->hasMany(SupplyPurchaseBatch::class, 'supplier_id');
    }

    /**
     * Get the feed purchases for this supplier
     */
    public function feedPurchasesBatch()
    {
        return $this->hasMany(FeedPurchaseBatch::class, 'supplier_id');
    }

    /**
     * Get the feed purchases for this supplier
     */
    public function livestockPurchases()
    {
        return $this->hasMany(LivestockPurchase::class, 'vendor_id');
    }

    public function livestockPurchasesAsVendor()
    {
        return $this->hasMany(LivestockPurchase::class, 'vendor_id');
    }

    public function livestockPurchasesAsExpedition()
    {
        return $this->hasMany(LivestockPurchase::class, 'expedition_id');
    }
}
