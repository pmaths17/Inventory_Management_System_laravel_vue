<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'sku', 'purchase_price', 'sale_price'];

    //purchase items for this product
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }
    //sale item for this product
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
    //stockmovement for this product
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
    // sum of in - out
    public function getCurrentStockAttribute()
    {
        return $this->stockMovements()
            ->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE -quantity END) as stock")
            ->pluck('stock')
            ->first() ?? 0;
    }
    public function scopeWithStock($query)
    {
         return $query->withSum(['stockMovements as current_stock' => function ($query) {
             $query->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE -quantity END)");
         }], 'quantity');
    }
    public function getAvailableStockAttribute()
    {
        return $this->current_stock - $this->locked_stock;
    }
    // Lock stock (for sale creation)
    public function lockStock(int $quantity)
    {
        $this->locked_stock += $quantity;
        $this->save();
    }
    // Unlock stock (if sale cancelled or failed)
    public function unlockStock(int $quantity)
    {
        $this->locked_stock = max(0, $this->locked_stock - $quantity);
        $this->save();
    }
    public function fifoBatches()
    {
        // Only batches with remaining stock
        return $this->purchaseItems()
            ->where('quantity_remaining', '>', 0)
            ->orderBy('created_at', 'asc'); // oldest first
    }
}
