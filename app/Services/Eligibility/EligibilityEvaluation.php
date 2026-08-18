<?php

namespace App\Services\Eligibility;

use App\Enums\EligibilityResult;

final class EligibilityEvaluation
{
    public function __construct(public readonly array $rules, private readonly ?EligibilityResult $calculatedResult = null) {}

    public function result(): EligibilityResult
    {
        if ($this->calculatedResult !== null) {
            return $this->calculatedResult;
        }

        if (collect($this->rules)->contains('status', 'failed')) {
            return EligibilityResult::Ineligible;
        }
        if (collect($this->rules)->contains('status', 'pending')) {
            return EligibilityResult::PendingRequirements;
        }

        return EligibilityResult::Eligible;
    }

    public function toArray(): array
    {
        return ['result' => $this->result()->value, 'rules' => $this->rules];
    }
}
