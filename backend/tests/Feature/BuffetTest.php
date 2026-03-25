<?php

namespace Tests\Feature;

use App\Models\Buffet;
use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuffetTest extends TestCase
{
    use RefreshDatabase;

    private function createDish(array $override = []): Dish
    {
        return Dish::create(array_merge([
            'hotel_id'       => $this->hotel->id,
            'name_fr'        => 'Plat test',
            'name_en'        => 'Test dish',
            'name_es'        => 'Plato test',
            'category'       => 'main_course',
            'cost_per_10pax' => 100.00,
            'portion_grams'  => 200,
            'is_active'      => true,
        ], $override));
    }

    public function test_create_buffet(): void
    {
        $dish1 = $this->createDish();
        $dish2 = $this->createDish(['name_fr' => 'Plat 2', 'cost_per_10pax' => 50]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/buffets', [
                'date'                  => '2025-06-15',
                'service'               => 'lunch',
                'theme'                 => 'Marocain',
                'pax_count'             => 100,
                'budget_target_per_pax' => 15.00,
                'dish_ids'              => [$dish1->id, $dish2->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.pax_count', 100)
            ->assertJsonPath('data.service', 'lunch');

        $this->assertDatabaseHas('buffets', [
            'hotel_id' => $this->hotel->id,
            'service'  => 'lunch',
            'pax_count' => 100,
        ]);
    }

    public function test_cost_report_calculation(): void
    {
        $dish1 = $this->createDish(['cost_per_10pax' => 100.00]);
        $dish2 = $this->createDish(['name_fr' => 'D2', 'cost_per_10pax' => 50.00]);

        $buffet = Buffet::create([
            'hotel_id'              => $this->hotel->id,
            'created_by'            => $this->adminUser->id,
            'date'                  => '2025-06-15',
            'service'               => 'lunch',
            'pax_count'             => 100,
            'budget_target_per_pax' => 20.00,
            'status'                => 'draft',
        ]);
        $buffet->dishes()->attach([$dish1->id, $dish2->id]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/buffets/{$buffet->id}/cost-report");

        $response->assertStatus(200);
        $data = $response->json('data');

        // total = (100 * 100/10) + (50 * 100/10) = 1000 + 500 = 1500
        $this->assertEquals(1500.00, $data['total_cost']);
        // cost per person = 1500 / 100 = 15.00
        $this->assertEquals(15.00, $data['cost_per_person']);
        // variance = 20.00 - 15.00 = 5.00
        $this->assertEquals(5.00, $data['budget_variance']);
    }

    public function test_production_sheet(): void
    {
        $dish = $this->createDish(['portion_grams' => 300, 'cost_per_10pax' => 100]);

        $buffet = Buffet::create([
            'hotel_id'   => $this->hotel->id,
            'created_by' => $this->adminUser->id,
            'date'       => '2025-06-15',
            'service'    => 'lunch',
            'pax_count'  => 50,
            'status'     => 'draft',
        ]);
        $buffet->dishes()->attach($dish->id);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/buffets/{$buffet->id}/production-sheet");

        $response->assertStatus(200);
        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        // qty = 300g * 50 pax / 1000 = 15 kg
        $this->assertEquals(15.0, $items[0]['quantity_kg']);
    }

    public function test_publish_buffet(): void
    {
        $buffet = Buffet::create([
            'hotel_id'   => $this->hotel->id,
            'created_by' => $this->adminUser->id,
            'date'       => '2025-06-20',
            'service'    => 'dinner',
            'pax_count'  => 80,
            'status'     => 'draft',
        ]);

        $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/buffets/{$buffet->id}/publish")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('buffets', ['id' => $buffet->id, 'status' => 'published']);
    }

    public function test_multi_tenant_isolation(): void
    {
        // Create buffet for another hotel
        $otherHotel = \App\Models\Hotel::create([
            'name' => 'Other', 'slug' => 'other2', 'email' => 'x@x.com',
            'is_active' => true, 'subscription_status' => 'active', 'plan' => 'starter',
        ]);
        $otherUser = \App\Models\User::create([
            'hotel_id' => $otherHotel->id, 'name' => 'Other Admin', 'email' => 'adm@other2.com',
            'password' => \Hash::make('pass'), 'role' => 'hotel_admin', 'is_active' => true,
        ]);
        $otherBuffet = Buffet::create([
            'hotel_id'   => $otherHotel->id,
            'created_by' => $otherUser->id,
            'date'       => '2025-06-15',
            'service'    => 'lunch',
            'pax_count'  => 20,
            'status'     => 'draft',
        ]);

        $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/buffets/{$otherBuffet->id}")
            ->assertStatus(404);
    }
}
