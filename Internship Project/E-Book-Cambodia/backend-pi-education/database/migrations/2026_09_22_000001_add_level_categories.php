<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Add the education-level categories (Primary, Lower Secondary,
 * Upper Secondary School, Competitive Exams) that the Study Book and
 * Teacher Book pages surface alongside the Math and Khmer subjects.
 */
return new class extends Migration
{
    /** @var list<array{position: int, en: string, km: string}> */
    private const LEVELS = [
        ['position' => 8, 'en' => 'Primary', 'km' => 'បឋមសិក្សា'],
        ['position' => 9, 'en' => 'Lower Secondary', 'km' => 'អនុវិទ្យាល័យ'],
        ['position' => 10, 'en' => 'Upper Secondary School', 'km' => 'វិទ្យាល័យ'],
        ['position' => 11, 'en' => 'Competitive Exams', 'km' => 'ប្រឡងប្រជែង'],
    ];

    public function up(): void
    {
        foreach (self::LEVELS as $level) {
            $existing = DB::table('categories')->where('name', $level['en'])->value('id');

            if ($existing !== null) {
                DB::table('categories')
                    ->where('id', $existing)
                    ->update(['position' => $level['position'], 'parent_id' => null]);

                continue;
            }

            $categoryId = DB::table('categories')->insertGetId([
                'parent_id' => null,
                'name' => $level['en'],
                'slug' => Str::slug($level['en']).'-'.Str::lower(Str::random(4)),
                'description' => $level['km'].' ➜ '.$level['en'].' education level.',
                'position' => $level['position'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ([
                'en' => [$level['en'], $level['en'].' education level.'],
                'km' => [$level['km'], 'កម្រិតអប់រំ'.' '.$level['km'].'។'],
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
    }

    public function down(): void
    {
        $names = array_column(self::LEVELS, 'en');

        $ids = DB::table('categories')->whereIn('name', $names)->pluck('id');
        DB::table('category_translations')->whereIn('category_id', $ids)->delete();
        DB::table('categories')->whereIn('id', $ids)->delete();
    }
};
