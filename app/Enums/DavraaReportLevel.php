<?php

namespace App\Enums;

/**
 * DAVRAA "Level" line on the report — ELEM / SEC / SNED. Deliberately a
 * small dedicated enum rather than `AgeDivision`: the DAVRAA template
 * carries SNED (Special Needs Education) as a peer of Elementary and
 * Secondary, which `AgeDivision` does not model.
 */
enum DavraaReportLevel: string
{
    case Elementary = 'elementary';
    case Secondary = 'secondary';
    case Sned = 'sned';

    public function label(): string
    {
        return match ($this) {
            self::Elementary => 'Elementary',
            self::Secondary => 'Secondary',
            self::Sned => 'SNED',
        };
    }

    /** The abbreviation printed on the report header. */
    public function reportLabel(): string
    {
        return match ($this) {
            self::Elementary => 'ELEM',
            self::Secondary => 'SEC',
            self::Sned => 'SNED',
        };
    }
}
