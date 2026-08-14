<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Guarded([])]
class Category extends Model
{
    use HasUuids;

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            $category->slug ??= static::uniqueSlug($category->name);
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }
}
