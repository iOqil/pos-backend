<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['sale_number', 'customer_id', 'cashier_id', 'subtotal', 'discount_amount', 'tax_amount', 'total', 'status', 'note'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function items() { return $this->hasMany(SaleItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
}
