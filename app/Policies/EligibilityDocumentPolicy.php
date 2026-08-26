<?php

namespace App\Policies;

use App\Models\EligibilityDocument;
use App\Models\User;

class EligibilityDocumentPolicy
{
    public function verify(User $user, EligibilityDocument $document): bool
    {
        $review = $document->athlete->eligibilityReview;

        return $review !== null
            && $user->hasPermission($document->document_type->verificationPermission(), $review->meet);
    }
}
