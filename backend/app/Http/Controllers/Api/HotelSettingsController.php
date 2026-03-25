<?php

namespace App\Http\Controllers\Api;

use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HotelSettingsController extends BaseApiController
{
    /**
     * GET /api/settings
     */
    public function show(Request $request): JsonResponse
    {
        $hotel = Hotel::find($this->getHotelId($request));
        if (!$hotel) {
            return $this->error('Hotel not found', 404);
        }

        return $this->success($this->formatHotel($hotel));
    }

    /**
     * PUT /api/settings
     */
    public function update(Request $request): JsonResponse
    {
        $hotel = Hotel::find($this->getHotelId($request));
        if (!$hotel) {
            return $this->error('Hotel not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name'                    => 'sometimes|string|max:255',
            'email'                   => 'sometimes|email',
            'phone'                   => 'nullable|string|max:50',
            'address'                 => 'nullable|string',
            'country'                 => 'nullable|string|size:2',
            'locale'                  => 'nullable|string|in:fr,en,es',
            'currency'                => 'nullable|string|size:3',
            'timezone'                => 'nullable|string',
            'branding'                => 'nullable|array',
            'repetition_alert_hours'  => 'nullable|integer|in:24,48,72',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $hotel->update($validator->validated());

        return $this->success($this->formatHotel($hotel->fresh()), 'Settings updated');
    }

    /**
     * POST /api/settings/upload-logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $hotel = Hotel::find($this->getHotelId($request));
        if (!$hotel) {
            return $this->error('Hotel not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $path = $request->file('logo')->store("hotels/{$hotel->id}/logos", 'public');
        $url  = Storage::url($path);

        $hotel->update(['logo_url' => $url]);

        return $this->success(['logo_url' => $url], 'Logo uploaded');
    }

    private function formatHotel(Hotel $hotel): array
    {
        return [
            'id'                     => $hotel->id,
            'name'                   => $hotel->name,
            'slug'                   => $hotel->slug,
            'email'                  => $hotel->email,
            'phone'                  => $hotel->phone,
            'address'                => $hotel->address,
            'country'                => $hotel->country,
            'locale'                 => $hotel->locale,
            'currency'               => $hotel->currency,
            'timezone'               => $hotel->timezone,
            'logo_url'               => $hotel->logo_url,
            'branding'               => $hotel->branding,
            'plan'                   => $hotel->plan,
            'subscription_status'    => $hotel->subscription_status,
            'max_users'              => $hotel->max_users,
            'repetition_alert_hours' => $hotel->repetition_alert_hours,
            'trial_ends_at'          => $hotel->trial_ends_at?->toIso8601String(),
        ];
    }
}
