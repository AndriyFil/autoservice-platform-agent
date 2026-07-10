<?php

namespace Database\Seeders;

use App\Domain\Shared\ValueObjects\Phone;
use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Local demo login: owner@example.com / password
        $owner = User::updateOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Main Auto Owner',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $workshop = Workshop::updateOrCreate(
            ['slug' => 'main-auto'],
            ['name' => 'Main Auto'],
        );

        WorkshopUser::updateOrCreate(
            [
                'workshop_id' => $workshop->id,
                'user_id' => $owner->id,
            ],
            ['role' => WorkshopUserRole::Owner],
        );

        $customers = collect([
            [
                'name' => 'Olena Kovalenko',
                'phone' => '+380 67 123 45 67',
                'normalized_phone' => '380671234567',
            ],
            [
                'name' => 'Andrii Shevchenko',
                'phone' => '+380 50 234 56 78',
                'normalized_phone' => '380502345678',
            ],
            [
                'name' => 'Maria Bondar',
                'phone' => '+380 63 345 67 89',
                'normalized_phone' => '380633456789',
            ],
            [
                'name' => 'Petro Melnyk',
                'phone' => '+380 97 456 78 90',
                'normalized_phone' => '380974567890',
            ],
            [
                'name' => 'Iryna Tkachenko',
                'phone' => '+380 93 567 89 01',
                'normalized_phone' => '380935678901',
            ],
        ])->mapWithKeys(fn (array $customer): array => [
            $customer['normalized_phone'] => Customer::updateOrCreate(
                [
                    'workshop_id' => $workshop->id,
                    'phone_normalized' => (new Phone($customer['phone']))->normalize(),
                ],
                [
                    'name' => $customer['name'],
                    'phone' => $customer['phone'],
                    'normalized_phone' => $customer['normalized_phone'],
                ],
            ),
        ]);

        $vehicles = collect([
            [
                'customer' => '380671234567',
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'license_plate' => 'AA1234KA',
            ],
            [
                'customer' => '380502345678',
                'brand' => 'Volkswagen',
                'model' => 'Golf',
                'license_plate' => 'BC2456OP',
            ],
            [
                'customer' => '380502345678',
                'brand' => 'Ford',
                'model' => 'Focus',
                'license_plate' => 'BC7788AP',
            ],
            [
                'customer' => '380633456789',
                'brand' => 'Renault',
                'model' => 'Megane',
                'license_plate' => 'KA4321IB',
            ],
            [
                'customer' => '380935678901',
                'brand' => 'Honda',
                'model' => 'Civic',
                'license_plate' => 'AE9012HX',
            ],
        ])->mapWithKeys(fn (array $vehicle): array => [
            $vehicle['license_plate'] => Vehicle::updateOrCreate(
                [
                    'workshop_id' => $workshop->id,
                    'license_plate' => $vehicle['license_plate'],
                ],
                [
                    'customer_id' => $customers[$vehicle['customer']]->id,
                    'brand' => $vehicle['brand'],
                    'model' => $vehicle['model'],
                ],
            ),
        ]);

        foreach ([
            [
                'customer' => '380671234567',
                'vehicle' => 'AA1234KA',
                'problem_description' => 'Brake pads squeak during morning start.',
                'preferred_date' => '2026-06-15',
                'status' => BookingRequestStatus::New,
                'created_at' => '2026-06-11 09:15:00',
            ],
            [
                'customer' => '380502345678',
                'vehicle' => 'BC2456OP',
                'problem_description' => 'Annual service and oil change.',
                'preferred_date' => '2026-06-16',
                'status' => BookingRequestStatus::Confirmed,
                'created_at' => '2026-06-10 14:30:00',
            ],
            [
                'customer' => '380633456789',
                'vehicle' => 'KA4321IB',
                'problem_description' => 'Engine warning light after refueling.',
                'preferred_date' => '2026-06-17',
                'status' => BookingRequestStatus::Rejected,
                'created_at' => '2026-06-09 11:45:00',
            ],
            [
                'customer' => '380974567890',
                'vehicle' => null,
                'problem_description' => 'Needs inspection before buying used car.',
                'preferred_date' => null,
                'status' => BookingRequestStatus::Cancelled,
                'created_at' => '2026-06-08 16:20:00',
            ],
            [
                'customer' => '380502345678',
                'vehicle' => 'BC7788AP',
                'problem_description' => 'Air conditioner blows warm air.',
                'preferred_date' => '2026-06-18',
                'status' => BookingRequestStatus::New,
                'created_at' => '2026-06-12 10:00:00',
            ],
            [
                'customer' => '380935678901',
                'vehicle' => null,
                'problem_description' => 'Battery drains overnight.',
                'preferred_date' => '2026-06-19',
                'status' => BookingRequestStatus::Confirmed,
                'created_at' => '2026-06-12 12:30:00',
            ],
        ] as $bookingRequest) {
            $customer = $customers[$bookingRequest['customer']];

            BookingRequest::updateOrCreate(
                [
                    'workshop_id' => $workshop->id,
                    'customer_id' => $customer->id,
                    'problem_description' => $bookingRequest['problem_description'],
                ],
                [
                    'vehicle_id' => $bookingRequest['vehicle']
                        ? $vehicles[$bookingRequest['vehicle']]->id
                        : null,
                    'created_by_user_id' => $owner->id,
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->phone,
                    'preferred_date' => $bookingRequest['preferred_date'],
                    'status' => $bookingRequest['status'],
                    'created_at' => $bookingRequest['created_at'],
                    'updated_at' => $bookingRequest['created_at'],
                ],
            );
        }
    }
}
