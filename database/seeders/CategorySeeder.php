<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // ── Root categories ──────────────────────────────────────────
        $roots = [
            ['name_ar' => 'سندويش',   'name_en' => 'Sandwiches',  'sort_order' => 1],
            ['name_ar' => 'وجبات',    'name_en' => 'Meals',       'sort_order' => 2],
            ['name_ar' => 'مشروبات',  'name_en' => 'Drinks',      'sort_order' => 3],
            ['name_ar' => 'مقبلات',   'name_en' => 'Appetizers',  'sort_order' => 4],
        ];

        foreach ($roots as $data) {
            Category::firstOrCreate(
                ['name_ar' => $data['name_ar'], 'parent_id' => null],
                array_merge($data, ['image' => null, 'is_active' => true])
            );
        }

        // ── Sub-categories ───────────────────────────────────────────
        $sandwichId = Category::query()->where('name_ar', 'سندويش')->value('id');
        $mealsId    = Category::query()->where('name_ar', 'وجبات')->value('id');
        $drinksId   = Category::query()->where('name_ar', 'مشروبات')->value('id');

        $subs = [
            // Under Sandwiches
            ['name_ar' => 'شاورما',   'name_en' => 'Shawarma',         'parent_id' => $sandwichId, 'sort_order' => 1],
            ['name_ar' => 'كرسبي',    'name_en' => 'Crispy',           'parent_id' => $sandwichId, 'sort_order' => 2],
            ['name_ar' => 'فاهيتا',   'name_en' => 'Fajita',           'parent_id' => $sandwichId, 'sort_order' => 3],
            // Under Meals
            ['name_ar' => 'وجبات دجاج', 'name_en' => 'Chicken Meals', 'parent_id' => $mealsId,    'sort_order' => 1],
            ['name_ar' => 'وجبات لحم',  'name_en' => 'Meat Meals',    'parent_id' => $mealsId,    'sort_order' => 2],
            // Under Drinks
            ['name_ar' => 'مشروبات غازية', 'name_en' => 'Soft Drinks', 'parent_id' => $drinksId,  'sort_order' => 1],
            ['name_ar' => 'عصائر',         'name_en' => 'Juices',      'parent_id' => $drinksId,  'sort_order' => 2],
        ];

        foreach ($subs as $data) {
            Category::firstOrCreate(
                ['name_ar' => $data['name_ar'], 'parent_id' => $data['parent_id']],
                array_merge($data, ['image' => null, 'is_active' => true])
            );
        }
    }
}

