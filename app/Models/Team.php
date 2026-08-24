<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'public_id',
        'name',
        'slug',
        'description',
        'logo_path',
        'contact_name',
        'whatsapp_phone',
        'social_links',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Team $team): void {
            $team->public_id ??= (string) Str::ulid();
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function whatsappUrl(): ?string
    {
        return $this->whatsapp_phone
            ? 'https://wa.me/'.preg_replace('/\D/', '', $this->whatsapp_phone)
            : null;
    }

    public function upcomingEvents(): HasMany
    {
        return $this->events()
            ->whereIn('status', EventStatus::publiclyVisible())
            ->whereDate('start_date', '>=', today())
            ->orderBy('start_date')
            ->orderBy('start_time');
    }
}
