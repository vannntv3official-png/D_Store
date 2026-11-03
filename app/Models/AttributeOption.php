<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_definition_id',
        'value',
        'label',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'label' => 'array',
            'display_order' => 'integer',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }

    public function getLocalizedLabel(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->label[$locale] ?? $this->label['en'] ?? (is_array($this->label) ? reset($this->label) : (string) $this->label);
    }
}
