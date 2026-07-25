<?php

namespace App\Enums;

enum DivisionType: string
{
    case City = 'city';
    case Province = 'province';

    public function label(): string
    {
        return match ($this) {
            self::City => 'City',
            self::Province => 'Province',
        };
    }

    /**
     * The term used for the administrative area that groups schools under
     * this division type — a City division's schools sit under a
     * District; a Province division's schools sit under a Municipality.
     * Both are the same `District` model — this only changes the label.
     */
    public function areaLabel(): string
    {
        return match ($this) {
            self::City => 'District',
            self::Province => 'Municipality',
        };
    }
}
