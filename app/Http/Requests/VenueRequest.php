<?php

namespace App\Http\Requests;

use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VenueRequest extends FormRequest
{
    /** @var array{0: float, 1: float}|null */
    private ?array $parsedGpsCoordinates = null;

    protected function prepareForValidation(): void
    {
        if (! $this->filled('readiness_status')) {
            $this->merge(['readiness_status' => 'planned']);
        }

        if ($this->has('gps_location') && $this->filled('gps_location')) {
            $this->parsedGpsCoordinates = $this->extractCoordinates($this->string('gps_location')->toString());
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $venue = $this->route('venue');

        return [
            'name' => [
                'required',
                'string',
                'max:160',
                Rule::unique('venues', 'name')
                    ->ignore($venue instanceof Venue ? $venue->id : null),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'municipality_id' => ['nullable', 'integer', Rule::exists('districts', 'id')->where('active', true)],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'gps_location' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && trim($value) !== '' && $this->extractCoordinates($value) === null) {
                        $fail(__('Enter coordinates such as 7.123456, 125.123456 or paste a Google Maps URL.'));
                    }
                },
            ],
            'public_notes' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'readiness_status' => ['required', Rule::in(['planned', 'for_validation', 'ready', 'needs_attention', 'unavailable'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Return persistence-ready venue data. The UI submits one Google Maps
     * location value; legacy API clients may continue sending lat/long.
     *
     * @return array<string, mixed>
     */
    public function venueData(): array
    {
        $data = $this->safe()->except('gps_location');

        if ($this->has('gps_location')) {
            $data['latitude'] = $this->parsedGpsCoordinates[0] ?? null;
            $data['longitude'] = $this->parsedGpsCoordinates[1] ?? null;
        }

        return $data;
    }

    /** @return array{0: float, 1: float}|null */
    private function extractCoordinates(string $location): ?array
    {
        $decoded = urldecode(trim($location));
        $number = '-?\d{1,3}(?:\.\d+)?';
        $patterns = [
            '/@('.$number.'),('.$number.')/',
            '/[?&](?:q|query|ll)=('.$number.')[,\s+]+('.$number.')/i',
            '/^('.$number.')[,\s]+('.$number.')$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $decoded, $matches) !== 1) {
                continue;
            }

            $latitude = (float) $matches[1];
            $longitude = (float) $matches[2];

            if ($latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {
                return [$latitude, $longitude];
            }
        }

        // Google Maps embed URLs commonly encode longitude before latitude
        // in their `pb` parameter (`!2d{lng}!3d{lat}`).
        if (preg_match('/!2d('.$number.')!3d('.$number.')/', $decoded, $matches) === 1) {
            $longitude = (float) $matches[1];
            $latitude = (float) $matches[2];

            if ($latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {
                return [$latitude, $longitude];
            }
        }

        return null;
    }
}
