<?php

namespace App\Models;

use App\Enums\PersonnelRole;
use Database\Factories\PersonnelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $delegation_id
 * @property string $first_name
 * @property string $last_name
 * @property PersonnelRole $role
 * @property string|null $phone
 * @property string|null $email
 * @property int|null $photo_upload_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['delegation_id', 'first_name', 'last_name', 'role', 'phone', 'email'])]
class Personnel extends Model
{
    /** @use HasFactory<PersonnelFactory> */
    use HasFactory;

    /**
     * Eloquent would pluralize to "personnels"; the table is "personnel".
     */
    protected $table = 'personnel';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => PersonnelRole::class,
        ];
    }

    /**
     * @return BelongsTo<Delegation, $this>
     */
    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    /**
     * @return BelongsToMany<Sport, $this>
     */
    public function sports(): BelongsToMany
    {
        return $this->belongsToMany(Sport::class, 'personnel_sport')->withTimestamps();
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'photo_upload_id');
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
