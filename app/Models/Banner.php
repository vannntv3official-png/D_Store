<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'link_url',
        'image_url',
        'button_text',
        'display_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'subtitle' => 'array',
            'button_text' => 'array',
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function getLocalizedTitle(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->title[$locale] ?? $this->title['en'] ?? (is_array($this->title) ? reset($this->title) : (string) $this->title);
    }

    public function getLocalizedSubtitle(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->subtitle[$locale] ?? $this->subtitle['en'] ?? null;
    }

    public function getLocalizedButtonText(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->button_text[$locale] ?? $this->button_text['en'] ?? null;
    }
}
