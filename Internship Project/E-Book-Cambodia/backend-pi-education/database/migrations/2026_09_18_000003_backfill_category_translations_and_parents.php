<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill normalized category data:
 *  - create km/en translations from the legacy `name` / `description` columns
 *  - turn the name-encoded "Math - Grade N" rows into real parent/child
 *    relationships (parent_id) and set a stable sort position.
 */
return new class extends Migration
{
    private const KHMER_DIGITS = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'];

    /** @var array<string, string> */
    private const MAIN_KHMER = [
        'Literature' => 'អក្សរសិល្ប៍',
        'Education' => 'ការអប់រំ',
        'History' => 'ប្រវត្តិសាស្ត្រ',
        'Technology' => 'បច្ចេកវិទ្យា',
        'Business' => 'អាជីវកម្ម',
        'Health' => 'សុខភាព',
        'Math' => 'គណិតវិទ្យា',
        'Khmer' => 'ខ្មែរ',
    ];

    /** @var array<string, string> */
    private const SUBJECT_KHMER_PREFIX = [
        'Math' => 'គណិត',
        'Khmer' => 'ខ្មែរ',
    ];

    /** @var list<string> */
    private const MAIN_ORDER = ['Math', 'Khmer', 'Literature', 'Education', 'History', 'Technology', 'Business', 'Health'];

    public function up(): void
    {
        $categories = DB::table('categories')->orderBy('id')->get();

        foreach ($categories as $category) {
            $isGrade = preg_match(
                '/^(.+?)\s*[-–—]\s*(?:grade\s*(\d+)|kindergarten)\s*$/iu',
                (string) $category->name,
                $matches
            ) === 1;

            if ($isGrade) {
                $base = trim($matches[1]);
                $number = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : null;
                $parentId = DB::table('categories')->where('name', $base)->value('id');

                DB::table('categories')
                    ->where('id', $category->id)
                    ->update([
                        'parent_id' => $parentId,
                        'position' => $number ?? 0,
                    ]);

                $nameEn = $number === null ? 'Kindergarten' : 'Grade '.$number;
                $prefix = self::SUBJECT_KHMER_PREFIX[$base] ?? $base;
                $nameKm = $number === null
                    ? $prefix.'ថ្នាក់មត្តេយ្យ'
                    : $prefix.'ថ្នាក់ទី'.$this->toKhmerDigits($number);
            } else {
                $position = array_search($category->name, self::MAIN_ORDER, true);
                DB::table('categories')
                    ->where('id', $category->id)
                    ->update(['position' => $position === false ? 999 : $position]);

                $nameEn = (string) $category->name;
                $nameKm = self::MAIN_KHMER[$category->name] ?? (string) $category->name;
            }

            [$descriptionKm, $descriptionEn] = $this->splitDescription($category->description);

            foreach ([
                'en' => [$nameEn, $descriptionEn],
                'km' => [$nameKm, $descriptionKm],
            ] as $locale => [$name, $description]) {
                DB::table('category_translations')->updateOrInsert(
                    ['category_id' => $category->id, 'locale' => $locale],
                    [
                        'name' => $name,
                        'description' => $description,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('category_translations')->truncate();
        DB::table('categories')->update(['parent_id' => null, 'position' => 0]);
    }

    /**
     * Legacy descriptions look like "គណិតវិទ្យា ➜ Mathematics books for all levels."
     * Split it into a Khmer and an English half when the separator is present.
     *
     * @return array{0: string, 1: string}
     */
    private function splitDescription(?string $description): array
    {
        $description = trim((string) $description);

        if ($description === '') {
            return ['', ''];
        }

        $parts = preg_split('/➜|—>|->/', $description, 2);

        if (count($parts) === 2) {
            return [trim($parts[0]), trim($parts[1])];
        }

        return [$description, $description];
    }

    private function toKhmerDigits(int $number): string
    {
        return implode('', array_map(
            fn (string $digit): string => self::KHMER_DIGITS[(int) $digit],
            str_split((string) $number)
        ));
    }
};
