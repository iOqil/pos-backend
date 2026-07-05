<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedBarcode extends Model
{
    protected $fillable = ['product_id', 'barcode_value', 'printed_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'printed_at' => 'datetime',
        ];
    }

    public function product() { return $this->belongsTo(Product::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
