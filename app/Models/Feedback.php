<?php

namespace App\Models;

use App\Enums\FeedbackSource;
use Database\Factories\FeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    /** @use HasFactory<FeedbackFactory> */
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'event_id',
        'source',
        'rating',
        'device_hash',
        'message',
        'subject',
        'contact_name',
        'contact_email',
        'contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'source' => FeedbackSource::class,
            'rating' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
