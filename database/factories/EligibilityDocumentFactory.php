<?php

namespace Database\Factories;

use App\Enums\EligibilityDocumentType;
use App\Models\Athlete;
use App\Models\EligibilityDocument;
use App\Models\FileUpload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EligibilityDocument>
 */
class EligibilityDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'athlete_id' => Athlete::factory(),
            'file_upload_id' => FileUpload::factory(),
            'document_type' => fake()->randomElement(EligibilityDocumentType::cases()),
        ];
    }
}
