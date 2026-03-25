<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseApiController
{
    /**
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $hotelId = $this->getHotelId($request);
        $users   = User::where('hotel_id', $hotelId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'locale', 'is_active', 'last_login_at', 'created_at']);

        return $this->success(['users' => $users]);
    }

    /**
     * POST /api/users
     */
    public function store(Request $request): JsonResponse
    {
        $hotelId = $this->getHotelId($request);

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
            'role'     => 'required|string|in:hotel_admin,chef,coordinator',
            'locale'   => 'nullable|string|in:fr,en,es',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        // Check hotel user limit
        $hotel = \App\Models\Hotel::find($hotelId);
        if (!$hotel->canAddUser()) {
            return $this->error("User limit reached for your plan ({$hotel->max_users} users)", 403);
        }

        // Check duplicate email within hotel
        if (User::where('hotel_id', $hotelId)->where('email', strtolower($request->email))->exists()) {
            return $this->error('Email already in use within this hotel', 422);
        }

        $user = User::create([
            'hotel_id' => $hotelId,
            'name'     => $request->name,
            'email'    => strtolower($request->email),
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'locale'   => $request->input('locale', 'fr'),
        ]);

        return $this->success($user->only(['id', 'name', 'email', 'role', 'locale', 'is_active']), 'User created', 201);
    }

    /**
     * PUT /api/users/:id
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $hotelId = $this->getHotelId($request);
        $user    = User::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$user) {
            return $this->error('User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email',
            'password' => 'nullable|string|min:8',
            'role'     => 'sometimes|string|in:hotel_admin,chef,coordinator',
            'locale'   => 'nullable|string|in:fr,en,es',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $data = $validator->safe()->except('password');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        $user->update($data);

        return $this->success($user->fresh()->only(['id', 'name', 'email', 'role', 'locale', 'is_active']), 'User updated');
    }

    /**
     * DELETE /api/users/:id — soft deactivate
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $hotelId  = $this->getHotelId($request);
        $authUser = $this->getUser($request);
        $user     = User::where('id', $id)->where('hotel_id', $hotelId)->first();

        if (!$user) {
            return $this->error('User not found', 404);
        }

        if ($user->id === $authUser->id) {
            return $this->error('You cannot deactivate your own account', 403);
        }

        $user->update(['is_active' => false]);
        $user->delete();

        return $this->success(null, 'User deactivated');
    }
}
