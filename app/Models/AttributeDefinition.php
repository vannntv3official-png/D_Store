<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_required',
        'is_filterable',
        'is_variant_defining',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_variant_defining' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)->orderBy('display_order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_attribute')
            ->withPivot('is_required', 'display_order');
    }

    public function getLocalizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->name[$locale] ?? $this->name['en'] ?? (is_array($this->name) ? reset($this->name) : (string) $this->name);
    }
}
