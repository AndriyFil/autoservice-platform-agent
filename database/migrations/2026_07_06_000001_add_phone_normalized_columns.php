<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CUSTOMER_PHONE_INDEX = 'customers_workshop_phone_normalized_index';

    private const CUSTOMER_PHONE_UNIQUE = 'customers_workshop_phone_normalized_unique';

    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'phone_normalized')) {
                $table->string('phone_normalized')->nullable()->after('phone');
            }
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_requests', 'customer_phone_normalized')) {
                $table->string('customer_phone_normalized')->nullable()->after('customer_phone');
            }
        });

        DB::table('customers')
            ->select(['id', 'phone'])
            ->orderBy('id')
            ->chunkById(100, function ($customers): void {
                foreach ($customers as $customer) {
                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update([
                            'phone_normalized' => $this->normalizePhone((string) $customer->phone),
                        ]);
                }
            });

        DB::table('booking_requests')
            ->select(['id', 'customer_phone'])
            ->orderBy('id')
            ->chunkById(100, function ($bookingRequests): void {
                foreach ($bookingRequests as $bookingRequest) {
                    DB::table('booking_requests')
                        ->where('id', $bookingRequest->id)
                        ->update([
                            'customer_phone_normalized' => $this->normalizePhone((string) $bookingRequest->customer_phone),
                        ]);
                }
            });

        Schema::table('customers', function (Blueprint $table): void {
            if ($this->hasDuplicateCustomerPhones()) {
                $table->index(['workshop_id', 'phone_normalized'], self::CUSTOMER_PHONE_INDEX);

                return;
            }

            $table->unique(['workshop_id', 'phone_normalized'], self::CUSTOMER_PHONE_UNIQUE);
        });
    }

    public function down(): void
    {
        try {
            Schema::table('customers', function (Blueprint $table): void {
                $table->dropUnique(self::CUSTOMER_PHONE_UNIQUE);
            });
        } catch (Throwable) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->dropIndex(self::CUSTOMER_PHONE_INDEX);
            });
        }

        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'phone_normalized')) {
                $table->dropColumn('phone_normalized');
            }
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_requests', 'customer_phone_normalized')) {
                $table->dropColumn('customer_phone_normalized');
            }
        });
    }

    private function hasDuplicateCustomerPhones(): bool
    {
        return DB::table('customers')
            ->select('workshop_id', 'phone_normalized')
            ->whereNotNull('phone_normalized')
            ->where('phone_normalized', '!=', '')
            ->groupBy('workshop_id', 'phone_normalized')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $hasLeadingPlus = str_starts_with($phone, '+');
        $cleaned = preg_replace('/[\s\-()]+/', '', $phone) ?? '';
        $digits = preg_replace('/\D+/', '', $cleaned) ?? '';

        if (preg_match('/^0\d{9}$/', $digits) === 1) {
            return '+38'.$digits;
        }

        if (preg_match('/^380\d{9}$/', $digits) === 1) {
            return '+'.$digits;
        }

        return $hasLeadingPlus ? '+'.$digits : $digits;
    }
};
