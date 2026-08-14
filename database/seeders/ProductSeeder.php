<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id');
        $faker = fake();

        Product::factory(20)
            ->make()
            ->each(function (Product $product) use ($categoryIds, $faker) {
                $product->category_id = $categoryIds->random();
                $product->slug = Product::uniqueSlug($product->name);
                $product->save();

                $imageCount = $faker->numberBetween(1, 3);

                for ($i = 0; $i < $imageCount; $i++) {
                    $product->images()->create([
                        'path' => "https://picsum.photos/seed/{$product->id}-{$i}/600/600",
                        'alt' => $product->name,
                        'order' => $i,
                    ]);
                }
            });
    }
}
