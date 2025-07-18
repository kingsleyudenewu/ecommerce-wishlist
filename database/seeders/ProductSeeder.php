<?php

namespace Database\Seeders;

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
        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'description' => 'Latest iPhone with advanced features',
                'price' => 999.99,
                'image_url' => 'https://example.com/iphone15.jpg',
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'description' => 'Flagship Android smartphone',
                'price' => 899.99,
                'image_url' => 'https://example.com/galaxy-s24.jpg',
            ],
            [
                'name' => 'MacBook Pro M3',
                'description' => 'Professional laptop for developers',
                'price' => 1999.99,
                'image_url' => 'https://example.com/macbook-pro.jpg',
            ],
            [
                'name' => 'AirPods Pro',
                'description' => 'Wireless earbuds with noise cancellation',
                'price' => 249.99,
                'image_url' => 'https://example.com/airpods-pro.jpg',
            ],
            [
                'name' => 'iPad Air',
                'description' => 'Versatile tablet for work and entertainment',
                'price' => 599.99,
                'image_url' => 'https://example.com/ipad-air.jpg',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
