<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded([])]
class Role extends Model
{
    use HasUuids;

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}
