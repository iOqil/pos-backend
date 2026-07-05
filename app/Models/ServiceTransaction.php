<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTransaction extends Model
{
    protected $fillable = ['service_id', 'customer_id', 'cashier_id', 'amount', 'commission_earned', 'status', 'details', 'reference_number'];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function service() { return $this->belongsTo(Service::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
}
