<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;
    protected $fillable =['customer_id','sale_date','total_amount','status','created_by'];
    public function customer(){
        return $this->belongsTo(Customer::class);
    }
    public function items(){
        return $this->hasMany(SaleItem::class);
    }
    public function creator(){
        return $this->belongsTo(User::class,'created_by');
    }
}
