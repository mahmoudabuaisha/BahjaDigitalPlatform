<?php

namespace App\Models;

use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * رسالة من صفحة «تواصلوا معنا»: تبقى «جديدة» حتى تردّ الإدارة وتغلقها.
 */
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message',
        'handled_at', 'handled_by', 'note',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** الرسائل التي لم تُعالَج بعد */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('handled_at');
    }

    public function isHandled(): bool
    {
        return $this->handled_at !== null;
    }

    public function markHandled(?string $note = null, ?int $userId = null): void
    {
        $this->forceFill([
            'handled_at' => now(),
            'handled_by' => $userId,
            'note' => filled($note) ? $note : $this->note,
        ])->save();
    }

    public function reopen(): void
    {
        $this->forceFill(['handled_at' => null, 'handled_by' => null])->save();
    }

    /**
     * رقم الجوال بصيغة واتساب الدولية: 0599… المحلية تصير 970599…،
     * والصيغ الدولية تبقى كما هي بعد نزع الرموز والمسافات.
     */
    public function whatsappDigits(): ?string
    {
        $digits = (string) preg_replace('/\D+/', '', (string) $this->phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '970'.substr($digits, 1);
        } elseif (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            $digits = '970'.$digits;
        }

        return strlen($digits) >= 11 ? $digits : null;
    }

    public function whatsappUrl(): ?string
    {
        $digits = $this->whatsappDigits();

        if ($digits === null) {
            return null;
        }

        $text = 'مرحباً '.$this->name.'، وصلتنا رسالتكم إلى منصّة بَهْجَة'
            .($this->subject ? ' بخصوص «'.$this->subject.'»' : '').'.';

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }

    public function mailtoUrl(): ?string
    {
        if (blank($this->email)) {
            return null;
        }

        $subject = 'رد: '.($this->subject ?: 'رسالتكم إلى منصّة بَهْجَة');

        return 'mailto:'.$this->email.'?subject='.rawurlencode($subject);
    }
}
