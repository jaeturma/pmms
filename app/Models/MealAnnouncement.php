<?php

namespace App\Models;

use Database\Factories\MealAnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An internal Food Team notice (e.g. "Lunch will be served 30 minutes
 * late today") — deliberately not the existing `Announcement` model,
 * which is a publish-gated, Admin/Organizer-only, public-portal-facing
 * advisory. See docs/food-billeting-transport.md.
 *
 * @property int $id
 * @property int $meet_id
 * @property string $title
 * @property string $message
 * @property int $posted_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'title', 'message', 'posted_by_user_id'])]
class MealAnnouncement extends Model
{
    /** @use HasFactory<MealAnnouncementFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Meet, $this>
     */
    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }
}
