<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Guarded([])]
class Product extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            $product->slug ??= static::uniqueSlug($product->name);
        });
    }

    public static function uniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        $exists = fn (string $slug) => static::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        while ($exists($slug)) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function transactionProducts(): HasMany
    {
        return $this->hasMany(TransactionProduct::class, 'product_id', 'id');
    }

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'transaction_products', 'product_id', 'transaction_id')
            ->withPivot('qty');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }
}
