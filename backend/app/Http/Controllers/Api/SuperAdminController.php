<?php

namespace App\Http\Controllers\Api;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SuperAdminController extends BaseApiController
{
    /**
     * GET /api/admin/hotels
     */
    public function listHotels(Request $request): JsonResponse
    {
        $query = Hotel::withCount(['users', 'dishes', 'buffets']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        if ($request->filled('status')) {
            $query->where('subscription_status', $request->status);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $hotels  = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->paginated($hotels, 'hotels');
    }

    /**
     * POST /api/admin/hotels
     */
    public function createHotel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'hotel_name'    => 'required|string|max:255',
            'hotel_email'   => 'required|email',
            'hotel_country' => 'nullable|string|size:2',
            'hotel_locale'  => 'nullable|string|in:fr,en,es',
            'plan'          => 'nullable|string|in:trial,starter,pro,enterprise',
            'admin_name'    => 'required|string|max:255',
            'admin_email'   => 'required|email',
            'admin_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $result = DB::transaction(function () use ($request) {
            $slug = $this->generateSlug($request->hotel_name);

            $hotel = Hotel::create([
                'name'                => $request->hotel_name,
                'slug'                => $slug,
                'email'               => strtolower($request->hotel_email),
                'country'             => $request->input('hotel_country', 'MA'),
                'locale'              => $request->input('hotel_locale', 'fr'),
                'plan'                => $request->input('plan', 'trial'),
                'subscription_status' => 'trial',
                'trial_ends_at'       => now()->addDays(14),
                'max_users'           => $this->maxUsersForPlan($request->input('plan', 'trial')),
            ]);

            $admin = User::create([
                'hotel_id' => $hotel->id,
                'name'     => $request->admin_name,
                'email'    => strtolower($request->admin_email),
                'password' => Hash::make($request->admin_password),
                'role'     => 'hotel_admin',
            ]);

            return compact('hotel', 'admin');
        });

        return $this->success([
            'hotel' => $result['hotel'],
            'admin' => $result['admin']->only(['id', 'name', 'email', 'role']),
        ], 'Hotel created', 201);
    }

    /**
     * PUT /api/admin/hotels/:id
     */
    public function updateHotel(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'                   => 'sometimes|string|max:255',
            'email'                  => 'sometimes|email',
            'plan'                   => 'sometimes|string|in:trial,starter,pro,enterprise',
            'subscription_status'    => 'sometimes|string|in:active,trial,past_due,cancelled',
            'max_users'              => 'sometimes|integer|min:1',
            'repetition_alert_hours' => 'nullable|integer|in:24,48,72',
            'is_active'              => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        if (isset($data['plan'])) {
            $data['max_users'] = $data['max_users'] ?? $this->maxUsersForPlan($data['plan']);
        }

        $hotel->update($data);

        return $this->success($hotel->fresh(), 'Hotel updated');
    }

    /**
     * DELETE /api/admin/hotels/:id
     */
    public function deactivateHotel(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update(['is_active' => false, 'subscription_status' => 'cancelled']);
        $hotel->delete();

        return $this->success(null, 'Hotel deactivated');
    }

    /**
     * POST /api/admin/hotels/:id/customize
     */
    public function customizeHotel(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'logo'               => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'branding'           => 'nullable|array',
            'branding.primary_color'   => 'nullable|string|max:7',
            'branding.secondary_color' => 'nullable|string|max:7',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $data = [];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store("hotels/{$hotel->id}/logos", 'public');
            $data['logo_url'] = Storage::url($path);
        }

        if ($request->filled('branding')) {
            $data['branding'] = array_merge($hotel->branding ?? [], $request->branding);
        }

        $hotel->update($data);

        return $this->success($hotel->fresh()->only(['id', 'name', 'logo_url', 'branding']), 'Hotel customized');
    }

    /**
     * GET /api/admin/analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $totalHotels  = Hotel::count();
        $activeHotels = Hotel::where('is_active', true)->where('subscription_status', 'active')->count();
        $trialHotels  = Hotel::where('subscription_status', 'trial')->count();
        $totalUsers   = User::count();
        $totalDishes  = \App\Models\Dish::count();
        $totalBuffets = \App\Models\Buffet::count();

        $planCounts = Hotel::selectRaw('plan, count(*) as count')
            ->where('is_active', true)
            ->groupBy('plan')
            ->pluck('count', 'plan');

        // Rough MRR calculation
        $mrr = ($planCounts['starter'] ?? 0) * 49
             + ($planCounts['pro'] ?? 0) * 149;

        $recentHotels = Hotel::latest()->limit(5)->get(['id', 'name', 'plan', 'subscription_status', 'created_at']);

        return $this->success([
            'hotels'        => [
                'total'   => $totalHotels,
                'active'  => $activeHotels,
                'trial'   => $trialHotels,
                'by_plan' => $planCounts,
            ],
            'users'         => $totalUsers,
            'dishes'        => $totalDishes,
            'buffets'       => $totalBuffets,
            'mrr_usd'       => $mrr,
            'recent_hotels' => $recentHotels,
        ]);
    }

    // --------- helpers ---------

    private function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;
        while (Hotel::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function maxUsersForPlan(string $plan): int
    {
        return match ($plan) {
            'starter'    => 3,
            'pro'        => 10,
            'enterprise' => 9999,
            default      => 3,
        };
    }
}
