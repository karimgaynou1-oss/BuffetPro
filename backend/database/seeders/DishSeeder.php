<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\Hotel;
use Illuminate\Database\Seeder;

class DishSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::where('slug', 'demo-hotel')->first();
        if (!$hotel) {
            $this->command->warn('Demo hotel not found, skipping dish seeder.');
            return;
        }

        $dishes = $this->getDishes();
        $created = 0;

        foreach ($dishes as $dish) {
            Dish::firstOrCreate(
                [
                    'hotel_id' => $hotel->id,
                    'name_fr'  => $dish['name_fr'],
                ],
                array_merge($dish, ['hotel_id' => $hotel->id, 'is_active' => true])
            );
            $created++;
        }

        $this->command->info("Seeded {$created} dishes for demo hotel.");
    }

    private function getDishes(): array
    {
        return [
            // ─── Moroccan Cuisine ──────────────────────────────────────────
            [
                'name_fr' => 'Tajine de poulet aux olives et citron confit',
                'name_en' => 'Chicken tagine with olives and preserved lemon',
                'name_es' => 'Tajín de pollo con aceitunas y limón confitado',
                'category' => 'main_course',
                'cost_per_10pax' => 180.00,
                'portion_grams' => 350,
                'allergens' => [],
                'diets' => ['halal'],
            ],
            [
                'name_fr' => 'Couscous royal aux sept légumes',
                'name_en' => 'Royal couscous with seven vegetables',
                'name_es' => 'Cuscús real con siete verduras',
                'category' => 'main_course',
                'cost_per_10pax' => 200.00,
                'portion_grams' => 400,
                'allergens' => ['gluten'],
                'diets' => ['halal'],
            ],
            [
                'name_fr' => 'Harira traditionnelle',
                'name_en' => 'Traditional Harira soup',
                'name_es' => 'Sopa Harira tradicional',
                'category' => 'soup',
                'cost_per_10pax' => 60.00,
                'portion_grams' => 250,
                'allergens' => ['gluten', 'celery'],
                'diets' => ['halal', 'vegetarian'],
            ],
            [
                'name_fr' => 'Pastilla au poulet',
                'name_en' => 'Chicken pastilla',
                'name_es' => 'Pastilla de pollo',
                'category' => 'starter',
                'cost_per_10pax' => 150.00,
                'portion_grams' => 200,
                'allergens' => ['gluten', 'eggs', 'nuts'],
                'diets' => ['halal'],
            ],
            [
                'name_fr' => 'Méchoui d\'agneau',
                'name_en' => 'Roasted lamb méchoui',
                'name_es' => 'Cordero asado méchoui',
                'category' => 'main_course',
                'cost_per_10pax' => 350.00,
                'portion_grams' => 300,
                'allergens' => [],
                'diets' => ['halal'],
            ],
            [
                'name_fr' => 'Salade marocaine fraîche',
                'name_en' => 'Fresh Moroccan salad',
                'name_es' => 'Ensalada marroquí fresca',
                'category' => 'salad',
                'cost_per_10pax' => 40.00,
                'portion_grams' => 150,
                'allergens' => [],
                'diets' => ['vegetarian', 'vegan', 'halal'],
            ],
            [
                'name_fr' => 'Briouates au fromage',
                'name_en' => 'Cheese briouats',
                'name_es' => 'Briouat de queso',
                'category' => 'starter',
                'cost_per_10pax' => 80.00,
                'portion_grams' => 100,
                'allergens' => ['gluten', 'milk'],
                'diets' => ['vegetarian', 'halal'],
            ],
            [
                'name_fr' => 'Cornes de gazelle',
                'name_en' => 'Gazelle horns (kaab el ghazal)',
                'name_es' => 'Cuernos de gacela',
                'category' => 'dessert',
                'cost_per_10pax' => 70.00,
                'portion_grams' => 100,
                'allergens' => ['gluten', 'nuts'],
                'diets' => ['vegetarian', 'halal'],
            ],
            [
                'name_fr' => 'Thé à la menthe',
                'name_en' => 'Mint tea',
                'name_es' => 'Té de menta',
                'category' => 'beverage',
                'cost_per_10pax' => 20.00,
                'portion_grams' => 200,
                'allergens' => [],
                'diets' => ['vegetarian', 'vegan', 'halal'],
            ],

            // ─── International ─────────────────────────────────────────────
            [
                'name_fr' => 'Saumon fumé gravlax',
                'name_en' => 'Gravlax smoked salmon',
                'name_es' => 'Salmón ahumado gravlax',
                'category' => 'starter',
                'cost_per_10pax' => 220.00,
                'portion_grams' => 80,
                'allergens' => ['fish'],
                'diets' => [],
            ],
            [
                'name_fr' => 'Rôti de bœuf en croûte',
                'name_en' => 'Beef Wellington',
                'name_es' => 'Rosbif en croûte',
                'category' => 'main_course',
                'cost_per_10pax' => 400.00,
                'portion_grams' => 280,
                'allergens' => ['gluten', 'eggs', 'milk'],
                'diets' => ['halal'],
            ],
            [
                'name_fr' => 'Gratin de légumes provençal',
                'name_en' => 'Provençal vegetable gratin',
                'name_es' => 'Gratén de verduras provenzal',
                'category' => 'side_dish',
                'cost_per_10pax' => 70.00,
                'portion_grams' => 200,
                'allergens' => ['milk'],
                'diets' => ['vegetarian', 'halal'],
            ],
            [
                'name_fr' => 'Riz pilaf aux herbes',
                'name_en' => 'Herb pilaf rice',
                'name_es' => 'Arroz pilaf con hierbas',
                'category' => 'side_dish',
                'cost_per_10pax' => 40.00,
                'portion_grams' => 180,
                'allergens' => [],
                'diets' => ['vegetarian', 'vegan', 'halal'],
            ],
            [
                'name_fr' => 'Soupe de légumes maison',
                'name_en' => 'Homemade vegetable soup',
                'name_es' => 'Sopa de verduras casera',
                'category' => 'soup',
                'cost_per_10pax' => 35.00,
                'portion_grams' => 250,
                'allergens' => ['celery'],
                'diets' => ['vegetarian', 'vegan', 'halal'],
            ],
            [
                'name_fr' => 'Plateau de fromages sélection',
                'name_en' => 'Selection cheese platter',
                'name_es' => 'Selección de quesos',
                'category' => 'cheese',
                'cost_per_10pax' => 160.00,
                'portion_grams' => 120,
                'allergens' => ['milk'],
                'diets' => ['vegetarian'],
            ],

            // ─── Végétarien / Vegan ────────────────────────────────────────
            [
                'name_fr' => 'Buddha bowl quinoa légumes',
                'name_en' => 'Quinoa vegetable buddha bowl',
                'name_es' => 'Buddha bowl de quinoa y verduras',
                'category' => 'main_course',
                'cost_per_10pax' => 110.00,
                'portion_grams' => 350,
                'allergens' => ['sesame'],
                'diets' => ['vegetarian', 'vegan', 'gluten_free'],
            ],
            [
                'name_fr' => 'Houmous maison aux légumes crudités',
                'name_en' => 'Homemade hummus with raw vegetables',
                'name_es' => 'Hummus casero con crudités',
                'category' => 'starter',
                'cost_per_10pax' => 55.00,
                'portion_grams' => 150,
                'allergens' => ['sesame'],
                'diets' => ['vegetarian', 'vegan', 'gluten_free', 'halal'],
            ],
            [
                'name_fr' => 'Plateau de fruits frais de saison',
                'name_en' => 'Seasonal fresh fruit platter',
                'name_es' => 'Plato de frutas frescas de temporada',
                'category' => 'fruit',
                'cost_per_10pax' => 80.00,
                'portion_grams' => 200,
                'allergens' => [],
                'diets' => ['vegetarian', 'vegan', 'halal', 'kosher', 'gluten_free'],
            ],
            [
                'name_fr' => 'Salade niçoise',
                'name_en' => 'Niçoise salad',
                'name_es' => 'Ensalada nizarda',
                'category' => 'salad',
                'cost_per_10pax' => 90.00,
                'portion_grams' => 200,
                'allergens' => ['fish', 'eggs'],
                'diets' => ['gluten_free'],
            ],

            // ─── Desserts ──────────────────────────────────────────────────
            [
                'name_fr' => 'Crème brûlée à la vanille',
                'name_en' => 'Vanilla crème brûlée',
                'name_es' => 'Crème brûlée de vainilla',
                'category' => 'dessert',
                'cost_per_10pax' => 95.00,
                'portion_grams' => 120,
                'allergens' => ['milk', 'eggs'],
                'diets' => ['vegetarian'],
            ],
            [
                'name_fr' => 'Mille-feuille à la crème pâtissière',
                'name_en' => 'Mille-feuille with pastry cream',
                'name_es' => 'Milhojas con crema pastelera',
                'category' => 'pastry',
                'cost_per_10pax' => 90.00,
                'portion_grams' => 150,
                'allergens' => ['gluten', 'milk', 'eggs'],
                'diets' => ['vegetarian'],
            ],
            [
                'name_fr' => 'Sorbet citron menthe',
                'name_en' => 'Lemon mint sorbet',
                'name_es' => 'Sorbete de limón y menta',
                'category' => 'dessert',
                'cost_per_10pax' => 50.00,
                'portion_grams' => 100,
                'allergens' => [],
                'diets' => ['vegetarian', 'vegan', 'gluten_free'],
            ],

            // ─── Breakfast ─────────────────────────────────────────────────
            [
                'name_fr' => 'Omelette aux fines herbes',
                'name_en' => 'Fine herbs omelette',
                'name_es' => 'Tortilla de hierbas finas',
                'category' => 'main_course',
                'cost_per_10pax' => 60.00,
                'portion_grams' => 150,
                'allergens' => ['eggs', 'milk'],
                'diets' => ['vegetarian', 'halal', 'gluten_free'],
            ],
            [
                'name_fr' => 'Assortiment de viennoiseries',
                'name_en' => 'Assorted pastries',
                'name_es' => 'Surtido de bollería',
                'category' => 'pastry',
                'cost_per_10pax' => 65.00,
                'portion_grams' => 120,
                'allergens' => ['gluten', 'milk', 'eggs'],
                'diets' => ['vegetarian'],
            ],
        ];
    }
}
