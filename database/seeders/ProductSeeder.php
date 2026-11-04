<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProductSeeder extends Seeder
{
    /**
     * Seed the application's database with sample products.
     *
     * @return void
     */
    public function run(): void
    {
        // Create categories first
        $categoryIds = [];
        
        $categories = [
            ['name_category' => 'Dress', 'image_category' => 'category_dress.png'],
            ['name_category' => 'T-Shirt', 'image_category' => 'category_tshirt.png'],
            ['name_category' => 'Jeans', 'image_category' => 'category_jeans.png'],
            ['name_category' => 'Celana', 'image_category' => 'category_celana.png'],
            ['name_category' => 'Atasan', 'image_category' => 'category_atasan.png'],
            ['name_category' => 'Rok', 'image_category' => 'category_rok.png'],
        ];
        
        foreach ($categories as $category) {
            $categoryIds[] = DB::table('categories')->insertGetId($category + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Create sample products
        $products = [
            // Dress products
            [
                'category_id' => $categoryIds[0],
                'name_product' => 'Dress Cantik Pink',
                'description' => 'Dress wanita elegan dengan warna pink yang cantik. Cocok untuk acara casual maupun formal.',
                'price_product' => 285000,
                'price_strike' => 350000,
            ],
            [
                'category_id' => $categoryIds[0],
                'name_product' => 'Dress Mini floral',
                'description' => 'Dress mini dengan motif floral yang segar dan nyaman dipakai.',
                'price_product' => 175000,
                'price_strike' => 250000,
            ],
            [
                'category_id' => $categoryIds[0],
                'name_product' => 'Maxi Dress Premium',
                'description' => 'Maxi dress premium berkualitas tinggi, nyaman dan elegan.',
                'price_product' => 450000,
                'price_strike' => 550000,
            ],
            
            // T-Shirt products
            [
                'category_id' => $categoryIds[1],
                'name_product' => 'T-Shirt Polos Premium',
                'description' => 'T-shirt polos berkualitas tinggi, nyaman dan adem. Tersedia berbagai ukuran.',
                'price_product' => 125000,
                'price_strike' => 150000,
            ],
            [
                'category_id' => $categoryIds[1],
                'name_product' => 'T-Shirt Graphic Print',
                'description' => 'T-shirt dengan graphic print trendy, cocok untuk anak muda.',
                'price_product' => 150000,
                'price_strike' => 200000,
            ],
            
            // Jeans products
            [
                'category_id' => $categoryIds[2],
                'name_product' => 'Jeans Slim Fit',
                'description' => 'Jeans slim fit yang nyaman dan stylish untuk sehari-hari.',
                'price_product' => 350000,
                'price_strike' => 420000,
            ],
            [
                'category_id' => $categoryIds[2],
                'name_product' => 'Jeans Skinny Premium',
                'description' => 'Jeans skinny premium dengan bahan stretch yang nyaman.',
                'price_product' => 380000,
                'price_strike' => 450000,
            ],
            
            // Celana products
            [
                'category_id' => $categoryIds[3],
                'name_product' => 'Celana Kulot Basic',
                'description' => 'Celana kulot basic yang nyaman dan cocok untuk berbagai acara.',
                'price_product' => 165000,
                'price_strike' => 200000,
            ],
            [
                'category_id' => $categoryIds[3],
                'name_product' => 'Wide Leg Pants',
                'description' => 'Wide leg pants elegan untuk tampilan profesional.',
                'price_product' => 225000,
                'price_strike' => 280000,
            ],
            
            // Atasan products
            [
                'category_id' => $categoryIds[4],
                'name_product' => 'Blouse Kerja',
                'description' => 'Blouse formal untuk kerja, nyaman dan profesional.',
                'price_product' => 195000,
                'price_strike' => 250000,
            ],
            [
                'category_id' => $categoryIds[4],
                'name_product' => 'Kemeja Flanel',
                'description' => 'Kemeja flanel casual yang trendy dan nyaman.',
                'price_product' => 225000,
                'price_strike' => 275000,
            ],
            
            // Rok products
            [
                'category_id' => $categoryIds[5],
                'name_product' => 'Rok Mini Plisket',
                'description' => 'Rok mini dengan plisket yang cantik dan playful.',
                'price_product' => 135000,
                'price_strike' => 175000,
            ],
            [
                'category_id' => $categoryIds[5],
                'name_product' => 'Midi Skirt Wrap',
                'description' => 'Midi skirt dengan model wrap yang elegan.',
                'price_product' => 185000,
                'price_strike' => 225000,
            ],
        ];
        
        foreach ($products as $product) {
            $productId = DB::table('products')->insertGetId($product + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Add variants
            $variants = [
                ['variant_name' => 'S - Small'],
                ['variant_name' => 'M - Medium'],
                ['variant_name' => 'L - Large'],
                ['variant_name' => 'XL - Extra Large'],
            ];
            
            foreach ($variants as $variant) {
                DB::table('product_variants')->insert([
                    'product_id' => $productId,
                    'variant_name' => $variant['variant_name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Add sizes with stock
            $sizes = [
                ['name_size' => 'S', 'stock_size' => rand(10, 50)],
                ['name_size' => 'M', 'stock_size' => rand(10, 50)],
                ['name_size' => 'L', 'stock_size' => rand(10, 50)],
                ['name_size' => 'XL', 'stock_size' => rand(10, 50)],
            ];
            
            foreach ($sizes as $size) {
                DB::table('product_sizes')->insert([
                    'product_id' => $productId,
                    'name_size' => $size['name_size'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
