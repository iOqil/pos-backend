<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'address', 'birth_date', 'loyalty_points', 'total_spent'];

    protected $casts = [
        'total_spent' => 'decimal:2',
        'birth_date' => 'date',
    ];

    public function sales() { return $this->hasMany(Sale::class); }
    public function debts() { return $this->hasMany(Debt::class); }

    // Aktiv (to'lanmagan) nasiyalar jami
    public function getActiveDebtAttribute()
    {
        return $this->debts()->where('status', 'active')->sum(\DB::raw('amount - paid_amount'));
    }
}
