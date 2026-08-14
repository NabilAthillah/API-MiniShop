<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = [
            ['alt' => 'Summer sale up to 50% off', 'url' => '/products'],
            ['alt' => 'New arrivals just landed', 'url' => '/products'],
            ['alt' => 'Free shipping this weekend', 'url' => null],
        ];

        foreach ($banners as $index => $banner) {
            Banner::firstOrCreate(
                ['alt' => $banner['alt']],
                [
                    'image' => "https://picsum.photos/seed/banner-{$index}/1200/400",
                    'url' => $banner['url'],
                ]
            );
        }
    }
}
