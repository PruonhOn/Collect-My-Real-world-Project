<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mirror the Math grade structure for Khmer: create "Khmer - Grade N"
 * child categories (Kindergarten + Grade 1-12) under Khmer so the books
 * view shows the same grade navigation row, and attach the existing
 * Grade 1 Khmer books to the new "Khmer - Grade 1" sub-category.
 */
return new class extends Migration
{
    private const KHMER_DIGITS = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'];

    public function up(): void
    {
        $khmerId = DB::table('categories')->where('name', 'Khmer')->value('id');

        if ($khmerId === null) {
            return;
        }

        for ($position = 0; $position <= 12; $position++) {
            $isKindergarten = $position === 0;
            $name = $isKindergarten ? 'Khmer - Kindergarten' : 'Khmer - Grade '.$position;
            $nameEn = $isKindergarten ? 'Kindergarten' : 'Grade '.$position;
            $nameKm = $isKindergarten
                ? 'ខ្មែរថ្នាក់មត្តេយ្យ'
                : 'ខ្មែរថ្នាក់ទី'.$this->toKhmerDigits($position);
            $descriptionEn = $isKindergarten
                ? 'Khmer literacy for kindergarten.'
                : 'Khmer language and literacy for grade '.$position.'.';
            $descriptionKm = $isKindergarten
                ? 'អក្ខរកម្មខ្មែរសម្រាប់ថ្នាក់មត្តេយ្យ។'
                : 'ភាសាខ្មែរ និងអក្ខរកម្មសម្រាប់ថ្នាក់ទី'.$this->toKhmerDigits($position).'។';

            $categoryId = DB::table('categories')->insertGetId([
                'parent_id' => $khmerId,
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
                'description' => $descriptionKm.' ➜ '.$descriptionEn,
                'position' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ([
                'en' => [$nameEn, $descriptionEn],
                'km' => [$nameKm, $descriptionKm],
            ] as $locale => [$translatedName, $translatedDescription]) {
                DB::table('category_translations')->insert([
                    'category_id' => $categoryId,
                    'locale' => $locale,
                    'name' => $translatedName,
                    'description' => $translatedDescription,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $gradeOneId = DB::table('categories')->where('name', 'Khmer - Grade 1')->value('id');

        if ($gradeOneId !== null) {
            DB::table('books')->where('category_id', $khmerId)->update(['category_id' => $gradeOneId]);
        }
    }

    public function down(): void
    {
        $khmerId = DB::table('categories')->where('name', 'Khmer')->value('id');
        $gradeOneId = DB::table('categories')->where('name', 'Khmer - Grade 1')->value('id');

        if ($khmerId !== null && $gradeOneId !== null) {
            DB::table('books')->where('category_id', $gradeOneId)->update(['category_id' => $khmerId]);
        }

        if ($khmerId !== null) {
            $childIds = DB::table('categories')->where('parent_id', $khmerId)->pluck('id');
            DB::table('category_translations')->whereIn('category_id', $childIds)->delete();
            DB::table('categories')->whereIn('id', $childIds)->delete();
        }
    }

    private function toKhmerDigits(int $number): string
    {
        return implode('', array_map(
            fn (string $digit): string => self::KHMER_DIGITS[(int) $digit],
            str_split((string) $number)
        ));
    }
};
