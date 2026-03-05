<?php

namespace Rz\LaravelAutoTranslator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationValue extends Model
{
    protected $fillable = [
        'translation_key_id',
        'locale',
        'value',
        'is_auto_translated',
        'provider',
    ];

    protected $casts = [
        'is_auto_translated' => 'boolean',
    ];

    public function translationKey(): BelongsTo
    {
        return $this->belongsTo(TranslationKey::class);
    }

    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeMissing($query)
    {
        return $query->whereNull('value');
    }

    public function scopeTranslated($query)
    {
        return $query->whereNotNull('value');
    }
}
