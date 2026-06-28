<?php

namespace App\Support\Intake;

class MissingNextIntakeFieldResolver
{
    /**
     * @var list<MissingNextIntakeFieldRule>
     */
    private readonly array $rules;

    public function __construct()
    {
        $this->rules = [
            new MissingPhoneIntakeFieldRule(),
            new MissingVehicleIntakeFieldRule(),
            new MissingPreferredTimeIntakeFieldRule(),
        ];
    }

    public function resolve(
        ?string $phone,
        ?string $vehicleMake,
        ?string $vehicleModel,
        ?string $vehiclePlate,
        ?string $preferredTimeText,
    ): ?string {
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
