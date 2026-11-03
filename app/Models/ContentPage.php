<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getLocalizedTitle(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->title[$locale] ?? $this->title['en'] ?? (is_array($this->title) ? reset($this->title) : (string) $this->title);
    }

    public function getLocalizedBody(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->body[$locale] ?? $this->body['en'] ?? '';
    }
}
