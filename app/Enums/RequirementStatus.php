<?php

namespace App\Enums;

enum RequirementStatus: string
{
    case Missing = 'missing';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Expired = 'expired';
}
