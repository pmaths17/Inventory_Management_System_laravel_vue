<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
use Faker\Factory as Faker;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
        public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 20; $i++) {
            $purchasePrice = $faker->randomFloat(2, 10, 100);
            $salePrice = $purchasePrice + $faker->randomFloat(2, 5, 50);

            DB::table('products')->insert([
                'name' => $faker->word,
                'sku' => strtoupper($faker->unique()->bothify('???-#####')),
                'purchase_price' => $purchasePrice,
                'sale_price' => $salePrice,
                'locked_stock' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => rand(0,9) === 0 ? now() : null, // 10% deleted
            ]);
        }
    }
}
