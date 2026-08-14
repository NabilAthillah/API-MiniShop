<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Electronics', 'Fashion', 'Groceries', 'Home & Living', 'Sports'] as $category) {
            Category::firstOrCreate(
                ['name' => $category],
                ['slug' => Category::uniqueSlug($category)]
            );
        }
    }
}
