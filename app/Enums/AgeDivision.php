<?php

namespace App\Enums;

enum AgeDivision: string
{
    case Elementary = 'elementary';
    case Secondary = 'secondary';
    case Mixed = 'mixed';
    case ElementaryAndSecondary = 'elementary_secondary';
    case ParagamesIntellectualDisability = 'paragames_intellectual_disability';
    case ParagamesIntellectualDisabilityYouth15Below = 'paragames_intellectual_disability_youth_15_below';
    case ParagamesIntellectualDisabilityJunior16Up = 'paragames_intellectual_disability_junior_16_up';
    case ParagamesVisuallyImpaired = 'paragames_visually_impaired';
    case ParagamesOrtho = 'paragames_ortho';
    case ParagamesOthers = 'paragames_others';

    public function label(): string
    {
        return match ($this) {
            self::Elementary => 'Elementary',
            self::Secondary => 'Secondary',
            self::Mixed => 'Mixed',
            self::ElementaryAndSecondary => 'Elementary & Secondary',
            self::ParagamesIntellectualDisability => 'Paragames Division Intellectual Disability',
            self::ParagamesIntellectualDisabilityYouth15Below => 'Intellectual Disability - Youth 15 below',
            self::ParagamesIntellectualDisabilityJunior16Up => 'Intellectual Disability - Junior 16 up',
            self::ParagamesVisuallyImpaired => 'Visually Impaired',
            self::ParagamesOrtho => 'Ortho',
            self::ParagamesOthers => 'Others',
        };
    }

    public function accepts(self $athleteDivision): bool
    {
        return $this === $athleteDivision
            || ($this === self::ElementaryAndSecondary
                && in_array($athleteDivision, [self::Elementary, self::Secondary], true));
    }
}
