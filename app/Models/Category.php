<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_url',
        'is_featured',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('display_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(AttributeDefinition::class, 'category_attribute')
            ->withPivot('is_required', 'display_order')
            ->orderByPivot('display_order');
    }

    public function getLocalizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->name[$locale] ?? $this->name['en'] ?? (is_array($this->name) ? reset($this->name) : (string) $this->name);
    }
}
