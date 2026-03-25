<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Buffet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hotel_id', 'created_by', 'date', 'service', 'theme',
        'pax_count', 'budget_target_per_pax', 'status', 'notes',
    ];

    protected $casts = [
        'date'                 => 'date',
        'pax_count'            => 'integer',
        'budget_target_per_pax' => 'decimal:2',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dishes(): BelongsToMany
    {
        return $this->belongsToMany(Dish::class, 'buffet_dishes')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('buffet_dishes.sort_order');
    }

    /**
     * Calculate total cost = SUM(dish.cost_per_10pax * pax_count / 10)
     */
    public function calculateTotalCost(): float
    {
        $total = 0.0;
        foreach ($this->dishes as $dish) {
            $total += (float) $dish->cost_per_10pax * $this->pax_count / 10;
        }
        return $total;
    }

    /**
     * Cost per person.
     */
    public function calculateCostPerPerson(): float
    {
        if ($this->pax_count <= 0) {
            return 0.0;
        }
        return $this->calculateTotalCost() / $this->pax_count;
    }

    /**
     * Budget variance (positive = under budget).
     */
    public function calculateBudgetVariance(): float
    {
        if (!$this->budget_target_per_pax) {
            return 0.0;
        }
        return (float) $this->budget_target_per_pax - $this->calculateCostPerPerson();
    }

    /**
     * Get cost report grouped by category.
     */
    public function getCostReport(): array
    {
        $byCategory = [];
        foreach ($this->dishes as $dish) {
            $cat = $dish->category;
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = ['category' => $cat, 'dishes' => [], 'subtotal' => 0.0];
            }
            $dishCost = (float) $dish->cost_per_10pax * $this->pax_count / 10;
            $byCategory[$cat]['dishes'][] = [
                'id'              => $dish->id,
                'name_fr'         => $dish->name_fr,
                'name_en'         => $dish->name_en,
                'name_es'         => $dish->name_es,
                'cost_per_10pax'  => $dish->cost_per_10pax,
                'dish_total_cost' => round($dishCost, 2),
            ];
            $byCategory[$cat]['subtotal'] += $dishCost;
        }
        foreach ($byCategory as &$cat) {
            $cat['subtotal'] = round($cat['subtotal'], 2);
        }
        return array_values($byCategory);
    }

    /**
     * Get production sheet (quantities to prepare).
     */
    public function getProductionSheet(): array
    {
        $items = [];
        foreach ($this->dishes as $dish) {
            $qty_kg = round($dish->portion_grams * $this->pax_count / 1000, 3);
            $items[] = [
                'dish_id'       => $dish->id,
                'name_fr'       => $dish->name_fr,
                'name_en'       => $dish->name_en,
                'name_es'       => $dish->name_es,
                'category'      => $dish->category,
                'portion_grams' => $dish->portion_grams,
                'quantity_kg'   => $qty_kg,
                'pax_count'     => $this->pax_count,
                'allergens'     => $dish->allergens ?? [],
                'diets'         => $dish->diets ?? [],
            ];
        }
        return $items;
    }

    public function scopeForHotel($query, int $hotelId)
    {
        return $query->where('hotel_id', $hotelId);
    }
}
