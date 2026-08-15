<?php

namespace App\Models;

use Database\Factories\ChildFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Child extends Model
{
    /** @use HasFactory<ChildFactory> */
    use HasFactory;

    protected $table = 'children';

    protected $fillable = ['user_id', 'name', 'birth_date', 'gender', 'grade'];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    /** «أحمد (8 سنوات)» — كما تظهر في بطاقة الحجز */
    public function nameWithAge(): string
    {
        $age = $this->age();

        return $age === null ? $this->name : $this->name.' ('.$age.' سنوات)';
    }
}
