<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dish extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hotel_id', 'name_fr', 'name_en', 'name_es', 'category',
        'cost_per_10pax', 'portion_grams', 'allergens', 'diets',
        'description_fr', 'description_en', 'description_es',
        'image_url', 'is_active',
    ];

    protected $casts = [
        'allergens'      => 'array',
        'diets'          => 'array',
        'cost_per_10pax' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function buffets(): BelongsToMany
    {
        return $this->belongsToMany(Buffet::class, 'buffet_dishes')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * Get localised name based on locale.
     */
    public function getLocalizedName(string $locale = 'fr'): string
    {
        return match ($locale) {
            'en' => $this->name_en ?: $this->name_fr,
            'es' => $this->name_es ?: $this->name_fr,
            default => $this->name_fr,
        };
    }

    /**
     * Scope to active dishes belonging to a hotel.
     */
    public function scopeForHotel($query, int $hotelId)
    {
        return $query->where('hotel_id', $hotelId)->where('is_active', true);
    }
}
