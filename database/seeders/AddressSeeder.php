<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerRole = Role::where('name', 'Customer')->firstOrFail();
        $customers = User::where('role_id', $customerRole->id)->get();
        $faker = fake();

        foreach ($customers as $customer) {
            if ($customer->addresses()->exists()) {
                continue;
            }

            $addressCount = $faker->numberBetween(1, 2);

            for ($i = 0; $i < $addressCount; $i++) {
                $customer->addresses()->create([
                    'label' => $faker->randomElement(['Home', 'Office', 'Apartment']),
                    'full_address' => $faker->address(),
                    'note' => $faker->boolean(40) ? $faker->sentence() : null,
                    'recipient_name' => $customer->name,
                    'recipient_phone_number' => $customer->phone,
                    'is_primary' => $i === 0,
                ]);
            }
        }
    }
}
