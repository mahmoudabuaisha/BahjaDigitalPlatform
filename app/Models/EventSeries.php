<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** سلسلة تكرار أسبوعي — كل مرة فعالية مستقلة بحضورها وإلغائها */
class EventSeries extends Model
{
    protected $fillable = ['team_id', 'title', 'recurrence_rule', 'starts_on', 'occurrence_count'];

    protected function casts(): array
    {
        return ['starts_on' => 'date'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'series_id');
    }
}
