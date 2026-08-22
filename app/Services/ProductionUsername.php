<?php

namespace App\Services;

use App\Models\AccountProvision;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Str;

class ProductionUsername
{
    public function fromName(string $fullName): string
    {
        $parts = collect(preg_split('/\s+/', Str::ascii(trim($fullName))) ?: [])
            ->map(fn (string $part): string => Str::lower(preg_replace('/[^a-z0-9]+/i', '', $part)))
            ->filter()
            ->values();

        if ($parts->isEmpty()) {
            return 'user';
        }

        $first = $parts->first();
        $last = $parts->last();
        if ($parts->count() > 1 && in_array($last, ['jr', 'sr', 'ii', 'iii', 'iv', 'v'], true)) {
            $last = $parts->get($parts->count() - 2).$last;
        }

        return $parts->count() === 1 ? $first : "{$first}.{$last}";
    }

    public function uniqueFor(Person $person): string
    {
        $base = $this->fromName($person->full_name);
        $candidate = $base;
        $suffix = 2;

        while (AccountProvision::query()->where('person_id', '!=', $person->id)->where('suggested_username', $candidate)->exists()
            || User::query()->when($person->user_id !== null, fn ($query) => $query->whereKeyNot($person->user_id))->where('username', $candidate)->exists()) {
            $candidate = $base.$suffix++;
        }

        return $candidate;
    }
}
