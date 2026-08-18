<?php

namespace App\Enums;

enum EligibilityResult: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PendingDsac = 'pending_dsac';
    case ReturnedByDsac = 'returned_by_dsac';
    case PendingMedical = 'pending_medical';
    case Eligible = 'eligible';
    case Ineligible = 'ineligible';
    case PendingRequirements = 'pending_requirements';
    case Restricted = 'restricted';
}
