<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerRole = Role::where('name', 'Customer')->firstOrFail();
        $customers = User::where('role_id', $customerRole->id)->with('addresses')->get()
            ->filter(fn (User $customer) => $customer->addresses->isNotEmpty());

        $productIds = Product::pluck('id');

        if ($productIds->isEmpty() || $customers->isEmpty()) {
            return;
        }

        $faker = fake();

        foreach ($customers as $customer) {
            if ($customer->transactions()->exists()) {
                continue;
            }

            $transactionCount = $faker->numberBetween(1, 3);

            for ($i = 0; $i < $transactionCount; $i++) {
                $transaction = Transaction::create([
                    'user_id' => $customer->id,
                    'address_id' => $customer->addresses->random()->id,
                ]);

                $itemCount = $faker->numberBetween(1, 3);

                foreach ($productIds->random(min($itemCount, $productIds->count())) as $productId) {
                    $transaction->transactionProducts()->create([
                        'product_id' => $productId,
                        'qty' => $faker->numberBetween(1, 5),
                    ]);
                }
            }
        }
    }
}
