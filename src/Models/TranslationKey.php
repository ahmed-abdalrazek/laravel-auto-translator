<?php

namespace Aar\AutoTranslator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationKey extends Model
{
    protected $fillable = ['key', 'group', 'file', 'is_dead'];

    protected $casts = [
        'is_dead' => 'boolean',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(TranslationValue::class);
    }

    public function scopeLive($query)
    {
        return $query->where('is_dead', false);
    }

    public function scopeDead($query)
    {
        return $query->where('is_dead', true);
    }

    public function scopeForGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get the translation for a specific locale.
     */
    public function translationFor(string $locale): ?TranslationValue
    {
        return $this->values()->where('locale', $locale)->first();
    }

    /**
     * Check if the key has a translation for the given locale.
     */
    public function hasTranslation(string $locale): bool
    {
        return $this->values()
            ->where('locale', $locale)
            ->whereNotNull('value')
            ->exists();
    }

    /**
     * Get missing locales for this key.
     */
    public function missingLocales(array $locales): array
    {
        $translated = $this->values()
            ->whereNotNull('value')
            ->pluck('locale')
            ->toArray();

        return array_values(array_diff($locales, $translated));
    }
}
