<?php

namespace App\Models;

use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * مشترك في نشرة بَهْجَة: يبدأ «بانتظار التأكيد» حتى يضغط رابط بريده،
 * ويبقى فعّالاً حتى يلغي بنفسه. لا تُرسَل نشرة إلا لمؤكَّد لم يُلغِ.
 */
class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    protected $fillable = ['email', 'area_id', 'token', 'confirmed_at', 'unsubscribed_at', 'last_sent_at'];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public static function newToken(): string
    {
        return Str::random(48);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /** المؤكَّدون الذين لم يلغوا — وحدهم تصلهم النشرة */
    public function scopeActive(Builder $query): void
    {
        $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function isUnsubscribed(): bool
    {
        return $this->unsubscribed_at !== null;
    }

    public function isActive(): bool
    {
        return $this->isConfirmed() && ! $this->isUnsubscribed();
    }

    public function confirm(): void
    {
        $this->forceFill([
            'confirmed_at' => $this->confirmed_at ?? now(),
            'unsubscribed_at' => null,
        ])->save();
    }

    public function unsubscribe(): void
    {
        $this->forceFill(['unsubscribed_at' => now()])->save();
    }

    public function tokenMatches(string $token): bool
    {
        return hash_equals($this->token, $token);
    }

    public function confirmUrl(): string
    {
        return route('newsletter.confirm', ['subscriber' => $this, 'token' => $this->token]);
    }

    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', ['subscriber' => $this, 'token' => $this->token]);
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isUnsubscribed() => 'ملغى',
            $this->isConfirmed() => 'مؤكَّد',
            default => 'بانتظار التأكيد',
        };
    }

    public function statusColor(): string
    {
        return match (true) {
            $this->isUnsubscribed() => 'gray',
            $this->isConfirmed() => 'success',
            default => 'warning',
        };
    }
}
