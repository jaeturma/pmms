<?php

namespace App\Models;

use Database\Factories\EmergencyCommunicationLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An append-only record of communications sent during an incident
 * response — no update/delete, same discipline as every other
 * transactional log in this app. See docs/medical-drrm.md.
 *
 * @property int $id
 * @property int $emergency_incident_id
 * @property string $message
 * @property int $sent_by_user_id
 * @property Carbon $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['emergency_incident_id', 'message', 'sent_by_user_id', 'sent_at'])]
class EmergencyCommunicationLog extends Model
{
    /** @use HasFactory<EmergencyCommunicationLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EmergencyIncident, $this>
     */
    public function emergencyIncident(): BelongsTo
    {
        return $this->belongsTo(EmergencyIncident::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
