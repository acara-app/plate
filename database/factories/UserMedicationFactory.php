<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UserMedication;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserMedication>
 */
final class UserMedicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $medications = [
            ['name' => 'Metformin', 'dosage' => '500mg', 'purpose' => 'Blood sugar control'],
            ['name' => 'Lisinopril', 'dosage' => '10mg', 'purpose' => 'Blood pressure'],
            ['name' => 'Atorvastatin', 'dosage' => '20mg', 'purpose' => 'Cholesterol'],
            ['name' => 'Levothyroxine', 'dosage' => '50mcg', 'purpose' => 'Thyroid'],
            ['name' => 'Omeprazole', 'dosage' => '20mg', 'purpose' => 'Acid reflux'],
            ['name' => 'Vitamin D3', 'dosage' => '2000 IU', 'purpose' => 'Supplement'],
            ['name' => 'Aspirin', 'dosage' => '81mg', 'purpose' => 'Heart health'],
            ['name' => 'Amlodipine', 'dosage' => '5mg', 'purpose' => 'Blood pressure'],
        ];

        $medication = fake()->randomElement($medications);

        return [
            'user_profile_id' => UserProfile::factory(),
            'name' => $medication['name'],
            'dosage' => $medication['dosage'],
            'frequency' => fake()->randomElement(['Once daily', 'Twice daily', 'Three times daily', 'As needed', 'With meals']),
            'purpose' => $medication['purpose'],
            'started_at' => fake()->optional(0.7)->dateTimeBetween('-5 years', 'now'),
        ];
    }
}
