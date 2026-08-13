<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'ألعاب ترفيهية', 'slug' => 'games', 'icon' => 'heroicon-o-puzzle-piece', 'color' => 'warning', 'sort_order' => 1],
            ['name' => 'دعم نفسي اجتماعي', 'slug' => 'psychosocial', 'icon' => 'heroicon-o-heart', 'color' => 'danger', 'sort_order' => 2],
            ['name' => 'رسم وأشغال يدوية', 'slug' => 'arts-crafts', 'icon' => 'heroicon-o-paint-brush', 'color' => 'info', 'sort_order' => 3],
            ['name' => 'مسرح ودمى', 'slug' => 'theatre', 'icon' => 'heroicon-o-sparkles', 'color' => 'primary', 'sort_order' => 4],
            ['name' => 'أناشيد وموسيقى', 'slug' => 'music', 'icon' => 'heroicon-o-musical-note', 'color' => 'success', 'sort_order' => 5],
            ['name' => 'رياضة وحركة', 'slug' => 'sports', 'icon' => 'heroicon-o-trophy', 'color' => 'warning', 'sort_order' => 6],
            ['name' => 'حكايات وقصص', 'slug' => 'stories', 'icon' => 'heroicon-o-book-open', 'color' => 'info', 'sort_order' => 7],
            ['name' => 'مناسبات خاصة', 'slug' => 'special', 'icon' => 'heroicon-o-gift', 'color' => 'danger', 'sort_order' => 8],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
