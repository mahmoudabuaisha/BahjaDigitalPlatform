<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AreaSeeder::class,
            CategorySeeder::class,
            AdminUserSeeder::class,
        ]);

        // البيانات التجريبية محلياً فقط
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
