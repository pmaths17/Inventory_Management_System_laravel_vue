<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = ['name','sku','purchase_price','sale_price'];

    //purchase items for this product
    public function purchaseItems(){
        return $this->hasMany(PurchaseItem::class);
    }
    //sale item for this product
    public function saleItems(){
        return $this->hasMany(SaleItem::class);
    }
    //stockmovement for this product
    public function stockMovements(){
        return $this->hasMany(StockMovement::class);
    }
    // sum of in - out
    public function getCurrentStockAttribute(){
        return $this->stockMovements()
        ->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE -quantity END) as stock")
        ->pluck('stock')
        ->first() ?? 0;
    }
}
