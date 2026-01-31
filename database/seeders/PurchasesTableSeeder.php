<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
use Faker\Factory as Faker;

class PurchasesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run()
    {
        $faker = Faker::create();
        $supplierIds = DB::table('suppliers')->pluck('id');
        $products = DB::table('products')->get()->keyBy('id');

        for ($i = 0; $i < 10; $i++) {
            // Insert purchase
            $purchaseId = DB::table('purchases')->insertGetId([
                'supplier_id' => $faker->randomElement($supplierIds),
                'purchase_date' => $faker->dateTimeThisYear,
                'total_amount' => 0, // will calculate after adding items
                'status' => $faker->randomElement(['draft','posted']),
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $itemsCount = rand(1, 5);
            $purchaseTotal = 0;

            $productIds = $products->keys()->shuffle()->take($itemsCount);

            foreach ($productIds as $productId) {
                $price = $products[$productId]->purchase_price;
                $quantity = $faker->numberBetween(1, 10);
                $subtotal = $price * $quantity;
                $purchaseTotal += $subtotal;

                // Insert purchase item
                DB::table('purchase_items')->insert([
                    'purchase_id' => $purchaseId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'quantity_remaining' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Insert stock movement "in"
                DB::table('stock_movements')->insert([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'type' => 'in',
                    'reference_type' => 'purchase',
                    'reference_id' => $purchaseId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Update total_amount in purchase
            DB::table('purchases')->where('id', $purchaseId)->update([
                'total_amount' => $purchaseTotal
            ]);
        }
    }
}
