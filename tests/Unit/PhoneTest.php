<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    #[DataProvider('ukrainianPhoneProvider')]
    public function test_ukrainian_phone_formats_normalize_to_canonical_value(string $phone): void
    {
        $this->assertSame('+380685620040', (new Phone($phone))->normalize());
    }

    /**
     * @return array<int, array{string}>
     */
    public static function ukrainianPhoneProvider(): array
    {
        return [
            ['0685620040'],
            ['+380685620040'],
            ['380685620040'],
            ['068 562 00 40'],
            ['+38 (068) 562-00-40'],
        ];
    }

    public function test_imperfect_phone_input_returns_cleaned_value_without_crashing(): void
    {
        $this->assertSame('+123', (new Phone('+abc 1-2(3)'))->normalize());
        $this->assertSame('123', (new Phone('abc 1-2(3)'))->normalize());
    }
}
