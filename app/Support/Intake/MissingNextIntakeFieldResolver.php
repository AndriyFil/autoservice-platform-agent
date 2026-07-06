<?php

namespace App\Support\Intake;

use App\Enums\MissingIntakeField;

class MissingNextIntakeFieldResolver
{
    /**
     * @var list<MissingNextIntakeFieldRule>
     */
    private readonly array $rules;

    public function __construct()
    {
        $this->rules = [
            new MissingPhoneIntakeFieldRule,
        ];
    }

    public function resolve(
        ?string $phone,
        ?string $vehicleMake,
        ?string $vehicleModel,
        ?string $vehiclePlate,
        ?string $preferredTimeText,
    ): ?MissingIntakeField {
        $snapshot = new IntakeExtractionSnapshot(
            phone: $phone,
            vehicleMake: $vehicleMake,
            vehicleModel: $vehicleModel,
            vehiclePlate: $vehiclePlate,
            preferredTimeText: $preferredTimeText,
        );

        foreach ($this->rules as $rule) {
            if ($rule->matches($snapshot)) {
                return $rule->field();
            }
        }

        return null;
    }
}
