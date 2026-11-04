<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerPrice extends Model
{
    use HasFactory;

    protected $table = 'reseller_prices';

    protected $fillable = [
        'user_id',
        'product_id',
        'harga_jual',
    ];

    protected $casts = [
        'harga_jual' => 'decimal:0',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
