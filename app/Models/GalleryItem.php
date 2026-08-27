<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meet_id', 'meet_sport_id', 'sport_event_id', 'file_upload_id', 'uploaded_by', 'reviewed_by', 'title', 'caption', 'description', 'capture_date', 'status', 'is_featured', 'display_order', 'file_hash', 'submitted_at', 'published_at', 'rejected_at', 'rejection_reason'])]
class GalleryItem extends Model
{
    protected function casts(): array
    {
        return [
            'capture_date' => 'date', 'is_featured' => 'boolean', 'display_order' => 'integer',
            'submitted_at' => 'datetime', 'published_at' => 'datetime', 'rejected_at' => 'datetime',
        ];
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    public function meetSport(): BelongsTo
    {
        return $this->belongsTo(MeetSport::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'sport_event_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'file_upload_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
