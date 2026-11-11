<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    protected $model = DocumentVersion::class;

    /**
     * A version row on its own.
     *
     * Rows rather than bytes: this factory records where a file went, and does not
     * put one there. `DocumentFactory::uploaded()` is the state that writes a real
     * copy, because a version pointing at a path nothing wrote is a legitimate
     * thing to describe - it is what a restore from a backup older than the upload
     * looks like - and a fixture that always wrote bytes could not express it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->numberBetween(1, 9);

        return [
            'document_id' => Document::factory(),
            'version_number' => $number,
            'disk' => (string) config('logistics.documents.disk', 'local'),
            'path' => 'documents/0/v'.$number.'-document.pdf',
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1_024, 4_194_304),
            'checksum' => hash('sha256', (string) $number),
            // Null rather than a user: a version outlives the account that made it,
            // so a fixture that always attached one would hide the nullable
            // relation from every test.
            'uploaded_by_id' => null,
        ];
    }
}
