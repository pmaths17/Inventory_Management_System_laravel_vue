<?php

// namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
// use Illuminate\Database\Seeder;
// use DB;
// use Faker\Factory as Faker;

// class SalesTableSeeder extends Seeder
// {
//     /**
//      * Run the database seeds.
//      */
//     public function run()
//     {
//         $faker = Faker::create();
//         $customerIds = DB::table('customers')->pluck('id');
//         $userId = 1; // default created_by

//         // Get all products and track stock
//         $products = DB::table('products')->get()->keyBy('id');
//         $stock = [];
//         foreach ($products as $product) {
//             // Current stock = sum of "in" - sum of "out"
//             $stockIn = DB::table('stock_movements')
//                 ->where('product_id', $product->id)
//                 ->where('type', 'in')
//                 ->sum('quantity');
//             $stockOut = DB::table('stock_movements')
//                 ->where('product_id', $product->id)
//                 ->where('type', 'out')
//                 ->sum('quantity');

//             $stock[$product->id] = $stockIn - $stockOut;
//         }

//         for ($i = 0; $i < 15; $i++) {
//             // Insert sale
//             $saleId = DB::table('sales')->insertGetId([
//                 'customer_id' => $faker->randomElement($customerIds),
//                 'sale_date' => $faker->dateTimeThisYear,
//                 'total_amount' => 0, // will update later after adding items
//                 'status' => $faker->randomElement(['draft', 'completed']),
//                 'created_by' => $userId,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ]);

//             $itemsCount = rand(1, 5); // each sale has 1-5 items
//             $saleTotal = 0;

//             // Randomly pick products for this sale
//             $productIds = $products->keys()->shuffle()->take($itemsCount);

//             foreach ($productIds as $productId) {
//                 // Skip if no stock
//                 if ($stock[$productId] <= 0) continue;

//                 // Quantity <= available stock
//                 $quantity = rand(1, min(5, $stock[$productId]));

//                 $price = $products[$productId]->sale_price;
//                 $subtotal = $price * $quantity;
//                 $saleTotal += $subtotal;

//                 // Insert sale item
//                 DB::table('sale_items')->insert([
//                     'sale_id' => $saleId,
//                     'product_id' => $productId,
//                     'quantity' => $quantity,
//                     'price' => $price,
//                     'subtotal' => $subtotal,
//                     'created_at' => now(),
//                     'updated_at' => now(),
//                 ]);

//                 // Reduce stock
//                 $stock[$productId] -= $quantity;

//                 // Insert stock movement
//                 DB::table('stock_movements')->insert([
//                     'product_id' => $productId,
//                     'quantity' => $quantity,
//                     'type' => 'out',
//                     'reference_type' => 'sale',
//                     'reference_id' => $saleId,
//                     'created_at' => now(),
//                     'updated_at' => now(),
//                 ]);
//             }

//             // Update total_amount in sale
//             DB::table('sales')->where('id', $saleId)->update([
//                 'total_amount' => $saleTotal
//             ]);
//         }
//     }
// }



namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\StockMovement;
use DB;

class SalesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();
        $customerIds = DB::table('customers')->pluck('id');
        $userId = 1; // default created_by

        // Get all products as models
        $products = Product::all()->keyBy('id');

        for ($i = 0; $i < 15; $i++) {
            // Seed only finalized transactions.
            $saleStatus = 'completed';

            // Insert sale
            $saleId = DB::table('sales')->insertGetId([
                'customer_id' => $faker->randomElement($customerIds),
                'sale_date' => $faker->dateTimeThisYear,
                'total_amount' => 0,
                'status' => $saleStatus,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $itemsCount = rand(1, 5); // 1-5 items per sale
            $saleTotal = 0;

            // Randomly pick products for this sale
            $productIds = $products->keys()->shuffle()->take($itemsCount);

            foreach ($productIds as $productId) {
                $product = $products[$productId];

                // Find purchase items with remaining stock
                $purchaseItem = DB::table('purchase_items')
                    ->where('product_id', $productId)
                    ->where('quantity_remaining', '>', 0)
                    ->orderBy('id')
                    ->first();

                if (!$purchaseItem) continue; // no stock left

                // Pick quantity <= available stock
                $maxQty = min(5, $purchaseItem->quantity_remaining);
                $quantity = rand(1, $maxQty);

                $subtotal = $product->sale_price * $quantity;
                $saleTotal += $subtotal;

                // Insert sale item
                SaleItem::create([
                    'sale_id' => $saleId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product->sale_price,
                    'subtotal' => $subtotal,
                ]);

                StockMovement::create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'type' => 'out',
                    'reference_type' => 'sale',
                    'reference_id' => $saleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update quantity_remaining in purchase item
                DB::table('purchase_items')
                    ->where('id', $purchaseItem->id)
                    ->update([
                        'quantity_remaining' => $purchaseItem->quantity_remaining - $quantity,
                    ]);
            }

            // Update total_amount in sale
            DB::table('sales')->where('id', $saleId)->update([
                'total_amount' => $saleTotal
            ]);
        }
    }
}
