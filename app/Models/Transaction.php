<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded([])]
class Transaction extends Model
{
    use HasUuids;

    public function transactionProducts(): HasMany
    {
        return $this->hasMany(TransactionProduct::class, 'transaction_id', 'id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'transaction_products', 'transaction_id', 'product_id')
            ->withPivot('qty');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id', 'id');
    }
}
