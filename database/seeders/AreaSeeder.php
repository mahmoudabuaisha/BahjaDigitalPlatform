<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'شمال غزة', 'slug' => 'north-gaza', 'sort_order' => 1],
            ['name' => 'مدينة غزة', 'slug' => 'gaza', 'sort_order' => 2],
            ['name' => 'الوسطى (دير البلح)', 'slug' => 'deir-al-balah', 'sort_order' => 3],
            ['name' => 'خان يونس', 'slug' => 'khan-younis', 'sort_order' => 4],
            ['name' => 'رفح', 'slug' => 'rafah', 'sort_order' => 5],
        ];

        foreach ($areas as $area) {
            Area::updateOrCreate(['slug' => $area['slug']], $area);
        }
    }
}
