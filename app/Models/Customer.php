<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'address', 'birth_date', 'loyalty_points', 'total_spent'];

    public function sales() { return $this->hasMany(Sale::class); }
}
