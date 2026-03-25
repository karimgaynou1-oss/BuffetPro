<?php

namespace Tests\Feature;

use App\Models\Dish;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DishTest extends TestCase
{
    use RefreshDatabase;

    private function createDish(array $override = []): Dish
    {
        return Dish::create(array_merge([
            'hotel_id'       => $this->hotel->id,
            'name_fr'        => 'Tajine de poulet',
            'name_en'        => 'Chicken tagine',
            'name_es'        => 'Tajín de pollo',
            'category'       => 'main_course',
            'cost_per_10pax' => 150.00,
            'portion_grams'  => 350,
            'allergens'      => [],
            'diets'          => ['halal'],
            'is_active'      => true,
        ], $override));
    }

    public function test_list_dishes_for_hotel(): void
    {
        $this->createDish();
        $this->createDish(['name_fr' => 'Couscous', 'name_en' => 'Couscous', 'name_es' => 'Cuscús']);

        // Create a dish for another hotel (should NOT be visible)
        $otherHotel = Hotel::create([
            'name' => 'Other Hotel', 'slug' => 'other-hotel', 'email' => 'o@o.com',
            'is_active' => true, 'subscription_status' => 'active', 'plan' => 'starter',
        ]);
        Dish::create([
            'hotel_id' => $otherHotel->id, 'name_fr' => 'Harira', 'name_en' => 'Harira', 'name_es' => 'Harira',
            'category' => 'soup', 'cost_per_10pax' => 30, 'portion_grams' => 250, 'is_active' => true,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/dishes');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('data.meta.total'));
    }

    public function test_create_dish(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/dishes', [
                'name_fr'        => 'Tajine d\'agneau',
                'name_en'        => 'Lamb tagine',
                'name_es'        => 'Tajín de cordero',
                'category'       => 'main_course',
                'cost_per_10pax' => 200.00,
                'portion_grams'  => 300,
                'allergens'      => [],
                'diets'          => ['halal'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name_fr', 'Tajine d\'agneau')
            ->assertJsonPath('data.hotel_id', $this->hotel->id);
    }

    public function test_create_dish_validates_required_fields(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/dishes', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    public function test_coordinator_cannot_create_dish(): void
    {
        $coordinator = User::create([
            'hotel_id' => $this->hotel->id,
            'name'     => 'Coord',
            'email'    => 'coord@test.com',
            'password' => Hash::make('password'),
            'role'     => 'coordinator',
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders($coordinator))
            ->postJson('/api/dishes', [
                'name_fr' => 'Test', 'name_en' => 'Test', 'name_es' => 'Test',
                'category' => 'other', 'cost_per_10pax' => 50, 'portion_grams' => 100,
            ]);

        $response->assertStatus(403);
    }

    public function test_soft_delete_dish(): void
    {
        $dish = $this->createDish();

        $this->withHeaders($this->getAuthHeaders())
            ->deleteJson("/api/dishes/{$dish->id}")
            ->assertStatus(200);

        $dish->refresh();
        $this->assertFalse($dish->is_active);
        $this->assertNotNull($dish->deleted_at);
    }

    public function test_cannot_access_other_hotel_dish(): void
    {
        $otherHotel = Hotel::create([
            'name' => 'Other', 'slug' => 'other', 'email' => 'o@o.com',
            'is_active' => true, 'subscription_status' => 'active', 'plan' => 'starter',
        ]);
        $otherDish = Dish::create([
            'hotel_id' => $otherHotel->id, 'name_fr' => 'Secret', 'name_en' => 'Secret', 'name_es' => 'Secreto',
            'category' => 'other', 'cost_per_10pax' => 50, 'portion_grams' => 100, 'is_active' => true,
        ]);

        $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/dishes/{$otherDish->id}")
            ->assertStatus(404);
    }
}
