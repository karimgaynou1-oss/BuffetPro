<?php

namespace App\Http\Controllers\Api;

use App\Models\Buffet;
use App\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BuffetController extends BaseApiController
{
    /**
     * GET /api/buffets
     */
    public function index(Request $request): JsonResponse
    {
        $hotelId = $this->getHotelId($request);
        $query   = Buffet::where('hotel_id', $hotelId)
            ->with(['creator:id,name,role', 'dishes:id,name_fr,name_en,name_es,category']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('service')) {
            $query->where('service', $request->service);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage  = min((int) $request->input('per_page', 15), 100);
        $buffets  = $query->orderByDesc('date')->paginate($perPage);

        return $this->paginated($buffets, 'buffets');
    }

    /**
     * GET /api/buffets/:id
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $buffet = $this->findOrFail($id, $this->getHotelId($request));
        if (!$buffet) {
            return $this->error('Buffet not found', 404);
        }

        $buffet->load(['creator:id,name,role', 'dishes']);

        return $this->success($this->formatBuffet($buffet));
    }

    /**
     * POST /api/buffets
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date'                  => 'required|date',
            'service'               => 'required|string|in:breakfast,lunch,dinner,brunch,afternoon_tea',
            'theme'                 => 'nullable|string|max:255',
            'pax_count'             => 'required|integer|min:1',
            'budget_target_per_pax' => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
            'dish_ids'              => 'nullable|array',
            'dish_ids.*'            => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $hotelId = $this->getHotelId($request);
        $user    = $this->getUser($request);

        $buffet = DB::transaction(function () use ($request, $hotelId, $user, $validator) {
            $buffet = Buffet::create(array_merge(
                $validator->safe()->except('dish_ids'),
                ['hotel_id' => $hotelId, 'created_by' => $user->id]
            ));

            if ($request->filled('dish_ids')) {
                $this->syncDishes($buffet, $request->dish_ids, $hotelId);
            }

            return $buffet;
        });

        $buffet->load('dishes');
        return $this->success($this->formatBuffet($buffet), 'Buffet created', 201);
    }

    /**
     * PUT /api/buffets/:id
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $buffet = $this->findOrFail($id, $this->getHotelId($request));
        if (!$buffet) {
            return $this->error('Buffet not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'date'                  => 'sometimes|date',
            'service'               => 'sometimes|string|in:breakfast,lunch,dinner,brunch,afternoon_tea',
            'theme'                 => 'nullable|string|max:255',
            'pax_count'             => 'sometimes|integer|min:1',
            'budget_target_per_pax' => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
            'dish_ids'              => 'nullable|array',
            'dish_ids.*'            => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $hotelId = $this->getHotelId($request);

        DB::transaction(function () use ($request, $buffet, $hotelId, $validator) {
            $buffet->update($validator->safe()->except('dish_ids'));
            if ($request->has('dish_ids')) {
                $this->syncDishes($buffet, $request->dish_ids ?? [], $hotelId);
            }
        });

        $buffet->load('dishes');
        return $this->success($this->formatBuffet($buffet), 'Buffet updated');
    }

    /**
     * POST /api/buffets/:id/publish
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $buffet = $this->findOrFail($id, $this->getHotelId($request));
        if (!$buffet) {
            return $this->error('Buffet not found', 404);
        }

        $buffet->update(['status' => 'published']);
        return $this->success(['status' => 'published'], 'Buffet published');
    }

    /**
     * DELETE /api/buffets/:id
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $buffet = $this->findOrFail($id, $this->getHotelId($request));
        if (!$buffet) {
            return $this->error('Buffet not found', 404);
        }

        $buffet->delete();
        return $this->success(null, 'Buffet deleted');
    }

    /**
     * GET /api/buffets/:id/cost-report
     */
    public function costReport(Request $request, int $id): JsonResponse
    {
        $buffet = $this->findOrFail($id, $this->getHotelId($request));
        if (!$buffet) {
            return $this->error('Buffet not found', 404);
        }

        $buffet->load('dishes');
        $totalCost    = $buffet->calculateTotalCost();
        $costPerPax   = $buffet->calculateCostPerPerson();
        $budgetVar    = $buffet->calculateBudgetVariance();

        return $this->success([
            'buffet_id'              => $buffet->id,
            'date'                   => $buffet->date->format('Y-m-d'),
            'service'                => $buffet->service,
            'pax_count'              => $buffet->pax_count,
            'total_cost'             => round($totalCost, 2),
            'cost_per_person'        => round($costPerPax, 2),
            'budget_target_per_pax'  => $buffet->budget_target_per_pax,
            'budget_variance'        => round($budgetVar, 2),
            'categories'             => $buffet->getCostReport(),
        ]);
    }

    /**
     * GET /api/buffets/:id/production-sheet
     */
    public function productionSheet(Request $request, int $id): JsonResponse
    {
        $buffet = $this->findOrFail($id, $this->getHotelId($request));
        if (!$buffet) {
            return $this->error('Buffet not found', 404);
        }

        $buffet->load('dishes');

        return $this->success([
            'buffet_id'   => $buffet->id,
            'date'        => $buffet->date->format('Y-m-d'),
            'service'     => $buffet->service,
            'theme'       => $buffet->theme,
            'pax_count'   => $buffet->pax_count,
            'items'       => $buffet->getProductionSheet(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/buffets/check-repetition
     * Check if any dish in the given list was served in the last X hours.
     */
    public function checkRepetition(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dish_ids' => 'required|array',
            'dish_ids.*' => 'integer',
            'date'     => 'required|date',
            'service'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $hotelId = $this->getHotelId($request);
        $hotel   = \App\Models\Hotel::find($hotelId);
        $hours   = $hotel->repetition_alert_hours ?? 48;

        $checkDate = \Carbon\Carbon::parse($request->date);
        $since     = $checkDate->copy()->subHours($hours);

        $dishIds = $request->dish_ids;

        $alerts = DB::select("
            SELECT d.id as dish_id, d.name_fr, d.name_en, d.name_es,
                   b.date as served_date, b.service as served_service, b.id as buffet_id
            FROM buffet_dishes bd
            JOIN dishes d ON d.id = bd.dish_id
            JOIN buffets b ON b.id = bd.buffet_id
            WHERE b.hotel_id = ?
              AND d.id IN (" . implode(',', array_fill(0, count($dishIds), '?')) . ")
              AND b.date >= ?
              AND b.date < ?
              AND b.deleted_at IS NULL
            ORDER BY b.date DESC
        ", array_merge([$hotelId], $dishIds, [$since->format('Y-m-d'), $checkDate->format('Y-m-d')]));

        return $this->success([
            'alerts'          => $alerts,
            'alert_threshold_hours' => $hours,
            'checked_date'    => $request->date,
        ]);
    }

    // --------- helpers ---------

    private function findOrFail(int $id, int $hotelId): ?Buffet
    {
        return Buffet::where('id', $id)->where('hotel_id', $hotelId)->first();
    }

    private function syncDishes(Buffet $buffet, array $dishIds, int $hotelId): void
    {
        // Validate dish IDs belong to this hotel
        $validIds = Dish::where('hotel_id', $hotelId)
            ->whereIn('id', $dishIds)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $syncData = [];
        foreach ($validIds as $i => $dishId) {
            $syncData[$dishId] = ['sort_order' => $i];
        }
        $buffet->dishes()->sync($syncData);
    }

    private function formatBuffet(Buffet $buffet): array
    {
        $data = $buffet->toArray();
        if ($buffet->relationLoaded('dishes') && $buffet->dishes->isNotEmpty()) {
            $totalCost   = $buffet->calculateTotalCost();
            $costPerPax  = $buffet->calculateCostPerPerson();
            $data['total_cost']       = round($totalCost, 2);
            $data['cost_per_person']  = round($costPerPax, 2);
            $data['budget_variance']  = round($buffet->calculateBudgetVariance(), 2);
            $data['dish_count']       = $buffet->dishes->count();
        }
        return $data;
    }
}
