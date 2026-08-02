<?php

namespace Database\Factories;

use App\Enums\BilletingAssignmentStatus;
use App\Models\BilletingAssignment;
use App\Models\BilletingVenue;
use App\Models\Delegation;
use App\Models\Meet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BilletingAssignment>
 *
 * Callers that need `meet_id` to actually agree with
 * `billeting_venue_id`'s own meet (the unique-constraint invariant) should
 * pass all three explicitly, same as `EquipmentTransferFactory`'s own
 * independent-by-default FKs — this default state doesn't try to derive
 * one FK from another.
 */
class BilletingAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'billeting_venue_id' => BilletingVenue::factory(),
            'delegation_id' => Delegation::factory(),
            'meet_id' => Meet::factory(),
            'status' => BilletingAssignmentStatus::Assigned,
            'assigned_at' => now(),
        ];
    }
}
