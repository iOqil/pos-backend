<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    protected $fillable = ['customer_id', 'sale_id', 'amount', 'paid_amount', 'status', 'due_date', 'note'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function sale() { return $this->belongsTo(Sale::class); }

    public function getRemainingAttribute()
    {
        return $this->amount - $this->paid_amount;
    }
}
