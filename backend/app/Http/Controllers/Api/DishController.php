<?php

namespace App\Http\Controllers\Api;

use App\Models\Dish;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DishController extends BaseApiController
{
    public function __construct(private readonly TranslationService $translator) {}

    /**
     * GET /api/dishes
     */
    public function index(Request $request): JsonResponse
    {
        $hotelId = $this->getHotelId($request);
        $query = Dish::where('hotel_id', $hotelId)
            ->where('is_active', true);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_fr', 'like', "%{$s}%")
                  ->orWhere('name_en', 'like', "%{$s}%")
                  ->orWhere('name_es', 'like', "%{$s}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('diet')) {
            $query->whereJsonContains('diets', $request->diet);
        }

        if ($request->filled('allergen_free')) {
            $allergen = $request->allergen_free;
            $query->where(function ($q) use ($allergen) {
                $q->whereNull('allergens')
                  ->orWhereJsonDoesntContain('allergens', $allergen);
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $dishes  = $query->orderBy($request->input('sort_by', 'name_fr'))
                         ->paginate($perPage);

        return $this->paginated($dishes, 'dishes');
    }

    /**
     * GET /api/dishes/:id
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $dish = $this->findOrFail($id, $this->getHotelId($request));
        if (!$dish) {
            return $this->error('Dish not found', 404);
        }
        return $this->success($dish);
    }

    /**
     * POST /api/dishes
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_fr'          => 'required|string|max:255',
            'name_en'          => 'required|string|max:255',
            'name_es'          => 'required|string|max:255',
            'category'         => 'required|string|in:starter,soup,salad,main_course,side_dish,dessert,pastry,beverage,cheese,fruit,other',
            'cost_per_10pax'   => 'required|numeric|min:0',
            'portion_grams'    => 'required|integer|min:1',
            'allergens'        => 'nullable|array',
            'allergens.*'      => 'string',
            'diets'            => 'nullable|array',
            'diets.*'          => 'string|in:vegetarian,vegan,halal,kosher,gluten_free',
            'description_fr'   => 'nullable|string',
            'description_en'   => 'nullable|string',
            'description_es'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $dish = Dish::create(array_merge(
            $validator->validated(),
            ['hotel_id' => $this->getHotelId($request)]
        ));

        return $this->success($dish, 'Dish created', 201);
    }

    /**
     * PUT /api/dishes/:id
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $dish = $this->findOrFail($id, $this->getHotelId($request));
        if (!$dish) {
            return $this->error('Dish not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name_fr'          => 'sometimes|string|max:255',
            'name_en'          => 'sometimes|string|max:255',
            'name_es'          => 'sometimes|string|max:255',
            'category'         => 'sometimes|string|in:starter,soup,salad,main_course,side_dish,dessert,pastry,beverage,cheese,fruit,other',
            'cost_per_10pax'   => 'sometimes|numeric|min:0',
            'portion_grams'    => 'sometimes|integer|min:1',
            'allergens'        => 'nullable|array',
            'allergens.*'      => 'string',
            'diets'            => 'nullable|array',
            'diets.*'          => 'string|in:vegetarian,vegan,halal,kosher,gluten_free',
            'description_fr'   => 'nullable|string',
            'description_en'   => 'nullable|string',
            'description_es'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $dish->update($validator->validated());

        return $this->success($dish, 'Dish updated');
    }

    /**
     * DELETE /api/dishes/:id — soft delete (is_active = false)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $dish = $this->findOrFail($id, $this->getHotelId($request));
        if (!$dish) {
            return $this->error('Dish not found', 404);
        }

        $dish->update(['is_active' => false]);
        $dish->delete();

        return $this->success(null, 'Dish deactivated');
    }

    /**
     * POST /api/dishes/translate
     */
    public function translate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text'        => 'required|string',
            'source_lang' => 'required|string|in:fr,en,es',
            'target_lang' => 'required|string|in:fr,en,es',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $translated = $this->translator->translate(
                $request->text,
                $request->source_lang,
                $request->target_lang
            );
            return $this->success(['translated_text' => $translated]);
        } catch (\Exception $e) {
            return $this->error('Translation failed: ' . $e->getMessage(), 500);
        }
    }

    // --------- helpers ---------

    private function findOrFail(int $id, int $hotelId): ?Dish
    {
        return Dish::where('id', $id)
            ->where('hotel_id', $hotelId)
            ->first();
    }
}
