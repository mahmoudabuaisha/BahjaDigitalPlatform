<?php

namespace App\Models;

use App\Enums\TeamApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** طلب انضمام فريق تطوعي — لا تشغيل قبل الاعتماد */
class TeamApplication extends Model
{
    protected $fillable = [
        'team_name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'description',
        'geographic_scope',
        'status',
        'decision_reason',
        'reviewed_by',
        'reviewed_at',
        'team_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => TeamApplicationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
