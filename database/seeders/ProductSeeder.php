<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Use the first available user (admin) as product owner
        $userId = User::value('id') ?? 1;

        // Helper: resolve category id by Arabic name
        $cat = fn(string $nameAr): ?int => Category::where('name_ar', $nameAr)->value('id');

        $products = [
            // ── شاورما ─────────────────────────────────────────────
            [
                'category_id'     => $cat('شاورما'),
                'name_ar'         => 'شاورما دجاج',
                'name_en'         => 'Chicken Shawarma',
                'description_ar'  => 'شاورما دجاج مشوي بالتوابل الشرقية مع صلصة الثوم والمخللات',
                'description_en'  => 'Grilled chicken shawarma with garlic sauce and pickles',
                'price'           => 8000,
                'discount_price'  => 7000,
                'is_featured'     => true,
                'sort_order'      => 1,
            ],
            [
                'category_id'     => $cat('شاورما'),
                'name_ar'         => 'شاورما لحم',
                'name_en'         => 'Meat Shawarma',
                'description_ar'  => 'شاورما لحم مشوي بالبهارات مع صلصة الطحينة',
                'description_en'  => 'Grilled meat shawarma with tahini sauce',
                'price'           => 10000,
                'discount_price'  => null,
                'is_featured'     => true,
                'sort_order'      => 2,
            ],
            [
                'category_id'     => $cat('شاورما'),
                'name_ar'         => 'شاورما مكسيكي',
                'name_en'         => 'Mexican Shawarma',
                'description_ar'  => 'شاورما دجاج حار بالجبنة والجالابينيو',
                'description_en'  => 'Spicy chicken shawarma with cheese and jalapeño',
                'price'           => 9000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 3,
            ],

            // ── كرسبي ──────────────────────────────────────────────
            [
                'category_id'     => $cat('كرسبي'),
                'name_ar'         => 'كرسبي دجاج',
                'name_en'         => 'Crispy Chicken',
                'description_ar'  => 'دجاج مقرمش مقلي بعجينة الكرسبي الخاصة مع صلصة الهوني موستارد',
                'description_en'  => 'Crispy fried chicken with honey mustard sauce',
                'price'           => 9500,
                'discount_price'  => 8500,
                'is_featured'     => true,
                'sort_order'      => 1,
            ],
            [
                'category_id'     => $cat('كرسبي'),
                'name_ar'         => 'كرسبي حار',
                'name_en'         => 'Spicy Crispy',
                'description_ar'  => 'كرسبي دجاج بالصلصة الحارة المميزة',
                'description_en'  => 'Crispy chicken with special hot sauce',
                'price'           => 10000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 2,
            ],

            // ── فاهيتا ─────────────────────────────────────────────
            [
                'category_id'     => $cat('فاهيتا'),
                'name_ar'         => 'فاهيتا دجاج',
                'name_en'         => 'Chicken Fajita',
                'description_ar'  => 'فاهيتا دجاج مشوي بالفلفل الملون والبصل',
                'description_en'  => 'Grilled chicken fajita with colorful peppers and onions',
                'price'           => 9000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 1,
            ],
            [
                'category_id'     => $cat('فاهيتا'),
                'name_ar'         => 'فاهيتا لحم',
                'name_en'         => 'Meat Fajita',
                'description_ar'  => 'فاهيتا لحم مع الجبنة والسور كريم',
                'description_en'  => 'Meat fajita with cheese and sour cream',
                'price'           => 11000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 2,
            ],

            // ── وجبات دجاج ─────────────────────────────────────────
            [
                'category_id'     => $cat('وجبات دجاج'),
                'name_ar'         => 'وجبة دجاج كرسبي',
                'name_en'         => 'Crispy Chicken Meal',
                'description_ar'  => 'قطعتان كرسبي + بطاطا + مشروب',
                'description_en'  => '2 crispy pieces + fries + drink',
                'price'           => 18000,
                'discount_price'  => 15000,
                'is_featured'     => true,
                'sort_order'      => 1,
            ],
            [
                'category_id'     => $cat('وجبات دجاج'),
                'name_ar'         => 'وجبة شاورما دجاج',
                'name_en'         => 'Chicken Shawarma Meal',
                'description_ar'  => 'شاورما دجاج + بطاطا + سلطة + مشروب',
                'description_en'  => 'Chicken shawarma + fries + salad + drink',
                'price'           => 16000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 2,
            ],
            [
                'category_id'     => $cat('وجبات دجاج'),
                'name_ar'         => 'وجبة عائلية دجاج',
                'name_en'         => 'Family Chicken Meal',
                'description_ar'  => '8 قطع دجاج + بطاطا كبيرة + 4 مشروبات',
                'description_en'  => '8 chicken pieces + large fries + 4 drinks',
                'price'           => 55000,
                'discount_price'  => 50000,
                'is_featured'     => true,
                'sort_order'      => 3,
            ],

            // ── وجبات لحم ──────────────────────────────────────────
            [
                'category_id'     => $cat('وجبات لحم'),
                'name_ar'         => 'وجبة شاورما لحم',
                'name_en'         => 'Meat Shawarma Meal',
                'description_ar'  => 'شاورما لحم + أرز + سلطة + مشروب',
                'description_en'  => 'Meat shawarma + rice + salad + drink',
                'price'           => 20000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 1,
            ],
            [
                'category_id'     => $cat('وجبات لحم'),
                'name_ar'         => 'وجبة مشاوي مشكلة',
                'name_en'         => 'Mixed Grill Meal',
                'description_ar'  => 'كباب + كفتة + دجاج مشوي + أرز + سلطة',
                'description_en'  => 'Kebab + kofta + grilled chicken + rice + salad',
                'price'           => 35000,
                'discount_price'  => 30000,
                'is_featured'     => true,
                'sort_order'      => 2,
            ],

            // ── مشروبات غازية ──────────────────────────────────────
            [
                'category_id'     => $cat('مشروبات غازية'),
                'name_ar'         => 'كولا',
                'name_en'         => 'Cola',
                'description_ar'  => 'كوكاكولا علبة 330 مل',
                'description_en'  => 'Coca-Cola can 330 ml',
                'price'           => 2000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 1,
            ],
            [
                'category_id'     => $cat('مشروبات غازية'),
                'name_ar'         => 'بيبسي',
                'name_en'         => 'Pepsi',
                'description_ar'  => 'بيبسي علبة 330 مل',
                'description_en'  => 'Pepsi can 330 ml',
                'price'           => 2000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 2,
            ],
            [
                'category_id'     => $cat('مشروبات غازية'),
                'name_ar'         => 'سبرايت',
                'name_en'         => 'Sprite',
                'description_ar'  => 'سبرايت علبة 330 مل',
                'description_en'  => 'Sprite can 330 ml',
                'price'           => 2000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 3,
            ],

            // ── عصائر ──────────────────────────────────────────────
            [
                'category_id'     => $cat('عصائر'),
                'name_ar'         => 'عصير برتقال طازج',
                'name_en'         => 'Fresh Orange Juice',
                'description_ar'  => 'عصير برتقال طازج 500 مل',
                'description_en'  => 'Fresh squeezed orange juice 500 ml',
                'price'           => 4500,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 1,
            ],
            [
                'category_id'     => $cat('عصائر'),
                'name_ar'         => 'عصير مانجو',
                'name_en'         => 'Mango Juice',
                'description_ar'  => 'عصير مانجو طبيعي بارد 500 مل',
                'description_en'  => 'Natural cold mango juice 500 ml',
                'price'           => 5000,
                'discount_price'  => 4000,
                'is_featured'     => false,
                'sort_order'      => 2,
            ],

            // ── مقبلات ─────────────────────────────────────────────
            [
                'category_id'     => $cat('مقبلات'),
                'name_ar'         => 'حمص بالزيت',
                'name_en'         => 'Hummus with Olive Oil',
                'description_ar'  => 'حمص كريمي بزيت الزيتون والكمون',
                'description_en'  => 'Creamy hummus with olive oil and cumin',
                'price'           => 5000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 1,
            ],
            [
                'category_id'     => $cat('مقبلات'),
                'name_ar'         => 'بطاطا مقلية',
                'name_en'         => 'French Fries',
                'description_ar'  => 'بطاطا مقلية مقرمشة بالملح والبهارات',
                'description_en'  => 'Crispy french fries with salt and spices',
                'price'           => 4000,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 2,
            ],
            [
                'category_id'     => $cat('مقبلات'),
                'name_ar'         => 'سلطة خضراء',
                'name_en'         => 'Green Salad',
                'description_ar'  => 'سلطة خضار طازجة بعصير الليمون وزيت الزيتون',
                'description_en'  => 'Fresh vegetable salad with lemon juice and olive oil',
                'price'           => 3500,
                'discount_price'  => null,
                'is_featured'     => false,
                'sort_order'      => 3,
            ],
        ];

        foreach ($products as $i => $data) {
            $nameEn = $data['name_en'];
            $slug   = Str::slug($nameEn) . '-' . ($i + 1);

            Product::firstOrCreate(
                ['name_ar' => $data['name_ar'], 'category_id' => $data['category_id']],
                array_merge($data, [
                    'name'         => $data['name_ar'],
                    'details'      => $data['description_ar'],
                    'quantity'     => 0,
                    'user_id'      => $userId,
                    'image'        => null,
                    'is_available' => true,
                    'is_active'    => true,
                    'slug'         => $slug,
                ])
            );
        }
    }
}

