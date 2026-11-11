<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * A description without a file.
     *
     * The default is deliberately file-less. A factory that wrote bytes would mean
     * every test using it had to call `Storage::fake()` first or land a real file
     * in `storage/app/private`, and a test about a title, a consignment or an
     * expiry has no business setting up a disk. `uploaded()` is the state that
     * adds a copy, and the tests that read or download the file ask for it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type' => fake()->randomElement([
                DocumentType::BillOfLading,
                DocumentType::CommercialInvoice,
                DocumentType::PackingList,
                DocumentType::CertificateOfOrigin,
                DocumentType::DeliveryOrder,
            ]),

            'title' => fake()->randomElement([
                'Bill of lading, Rotterdam to Jebel Ali',
                'Commercial invoice, household goods',
                'Packing list, 14 pallets',
                'Certificate of origin, textiles',
                'Delivery order, nominated consignee',
            ]),

            // Not unique, deliberately - one export file repeats it. See the
            // migration.
            'reference' => fake()->bothify('??-#####/????'),

            'shipment_id' => Shipment::factory(),

            'issued_on' => now()->subDays(fake()->numberBetween(1, 40))->toDateString(),

            /*
             * Two thirds of the rows have a date at all, because a packing list
             * and a commercial invoice do not expire and a fixture where every
             * row did would make the expiry filters look universally true.
             */
            'expires_on' => fake()->boolean(65)
                ? now()->addDays(fake()->numberBetween(-20, 400))->toDateString()
                : null,

            'notes' => null,
        ];
    }

    /**
     * With a copy of the file actually on the disk.
     *
     * `withFile()` rather than a `has` on the relation because the version row and
     * the bytes have to agree: a test that downloads the document has to be able to
     * assert on the bytes that came back, and a version row pointing at a path
     * nothing wrote would pass a resource assertion and fail a download one for
     * reasons that have nothing to do with the code under test.
     *
     * Uses the disk named in `config('logistics.documents.disk')` rather than a
     * hard-coded `local`, because the controller stores and reads on that one and
     * a fixture writing to a different disk would fail for a reason that looks
     * like a bug.
     */
    public function uploaded(?string $originalName = null, ?string $mimeType = null): static
    {
        return $this->afterCreating(function (Document $document) use ($originalName, $mimeType): void {
            $this->storeVersion($document, 1, $originalName ?? 'document.pdf', $mimeType ?? 'application/pdf');
        });
    }

    /**
     * A second, superseding copy - the amendment case.
     *
     * Two uploads of the same document is the ordinary shape of this table rather
     * than an edge case: a bill of lading is reissued, a certificate is re-scanned
     * with a corrected expiry. `uploaded()` on its own would only ever produce a
     * register where every document had exactly one version, and the module's
     * whole reason for separating versions from documents would go untested.
     */
    public function amended(string $title = 'Bill of lading, amended'): static
    {
        return $this
            ->state(fn (): array => [
                'title' => $title,
                'reference' => fake()->bothify('??-#####/????'),
            ])
            ->uploaded('original.pdf')
            ->afterCreating(function (Document $document): void {
                $this->storeVersion($document, 2, 'amended.pdf', 'application/pdf');
            });
    }

    /**
     * Paperwork that belongs to the business rather than to a job.
     *
     * All references null. The fleet insurance certificate and the quality
     * certificate are the real rows in this table with no consignment behind them,
     * so a fixture that always attached one would make `shipment_id` look
     * required when the API deliberately does not require it.
     */
    public function companyWide(): static
    {
        return $this->state(fn (): array => [
            'document_type' => DocumentType::InsuranceCertificate,
            'title' => fake()->randomElement([
                'Fleet insurance certificate',
                'ISO 9001 quality certificate',
                'Operating licence',
            ]),
            'shipment_id' => null,
            'reference' => null,
            'expires_on' => now()->addDays(fake()->numberBetween(30, 300))->toDateString(),
        ]);
    }

    /**
     * Already past its expiry date.
     *
     * The case `isExpired()` exists for, and kept out of the random pool above
     * because a fixture that rolled expired rows at random would make an expiry
     * filter's own test depend on the seed.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_on' => now()->subDays(fake()->numberBetween(1, 90))->toDateString(),
        ]);
    }

    /**
     * Still valid, but inside the warning window.
     *
     * The distinction from `expired()` is the whole point of `needs_attention`:
     * these two look the same in a register and mean different amounts of work.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (): array => [
            'expires_on' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
        ]);
    }

    /**
     * Valid for years, or not at all.
     */
    public function validForLong(): static
    {
        return $this->state(fn (): array => [
            'expires_on' => now()->addDays(fake()->numberBetween(120, 700))->toDateString(),
        ]);
    }

    /**
     * Writes a real copy onto the configured disk and records it as a version.
     *
     * Shared by `uploaded()` and `amended()` so the two produce rows the same way
     * the controller does - a file on the disk, then a `DocumentVersion` naming
     * where it went - rather than one of them being a shortcut that happens to
     * satisfy the assertions.
     */
    private function storeVersion(
        Document $document,
        int $number,
        string $originalName,
        string $mimeType,
    ): void {
        $disk = (string) config('logistics.documents.disk', 'local');

        $contents = '%PDF-1.4 version '.$number.' of '.$document->title;

        /*
         * A real temp file with real bytes in it, rather than an empty one with the
         * expected contents described in a variable. The size and the checksum
         * recorded below have to be the size and the checksum of what was actually
         * written, or a test asserting on them passes against a fiction.
         *
         * `$test = true` so `storeAs` reads the temp file in place instead of
         * trying to move it - the fixture is describing a file the client sent,
         * not simulating a real upload's lifecycle.
         */
        $temporary = tempnam(sys_get_temp_dir(), 'document');
        file_put_contents($temporary, $contents);

        $path = (new UploadedFile(
            $temporary,
            $originalName,
            $mimeType,
            null,
            true,
        ))->storeAs('documents/'.$document->getKey(), 'v'.$number.'-'.$originalName, $disk);

        DocumentVersion::query()->create([
            'document_id' => $document->getKey(),
            'version_number' => $number,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'uploaded_by_id' => null,
        ]);
    }
}
