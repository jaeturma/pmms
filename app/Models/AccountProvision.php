<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['person_id', 'linked_user_id', 'suggested_username', 'email', 'target_role', 'status', 'reason'])]
class AccountProvision extends Model
{
    protected $hidden = ['activation_token_hash'];

    protected function casts(): array
    {
        return ['invited_at' => 'datetime', 'activated_at' => 'datetime'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }
}
