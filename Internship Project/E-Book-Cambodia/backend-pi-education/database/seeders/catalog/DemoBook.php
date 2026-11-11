<?php

namespace Database\Seeders\Catalog;

use App\Models\Book;

/**
 * Builder for the demo catalogue records.
 *
 * A record is display data only: it never carries a document path, a purchase
 * or a membership, so running the demo catalogue can never widen what a real
 * reader is allowed to read.
 */
final class DemoBook
{
    public const CATEGORY_MATH = 'Math';

    public const CATEGORY_KHMER = 'Khmer';

    public const CATEGORY_LITERATURE = 'Literature';

    public const CATEGORY_EDUCATION = 'Education';

    public const CATEGORY_HISTORY = 'History';

    public const CATEGORY_TECHNOLOGY = 'Technology';

    public const CATEGORY_BUSINESS = 'Business';

    public const CATEGORY_HEALTH = 'Health';

    public const CATEGORY_PRIMARY = 'Primary';

    public const CATEGORY_LOWER_SECONDARY = 'Lower Secondary';

    public const CATEGORY_UPPER_SECONDARY = 'Upper Secondary School';

    public const CATEGORY_COMPETITIVE = 'Competitive Exams';

    public const CATEGORY_KHMER_LITERATURE = 'អក្សរសាស្ត្រខ្មែរ';

    public const LEVEL_PRIMARY = 'Primary';

    public const LEVEL_LOWER_SECONDARY = 'Lower Secondary';

    public const LEVEL_UPPER_SECONDARY = 'Upper Secondary';

    public const LEVEL_HIGHER_EDUCATION = 'Higher Education';

    public const LEVEL_TEACHER = 'Teacher Resource';

    public const SUBJECT_KHMER = 'Khmer Language';

    public const SUBJECT_KHMER_LITERATURE = 'Khmer Literature';

    public const SUBJECT_MATH = 'Mathematics';

    public const SUBJECT_STATISTICS = 'Mathematics & Statistics';

    public const SUBJECT_PHYSICS = 'Physics';

    public const SUBJECT_CHEMISTRY = 'Chemistry';

    public const SUBJECT_BIOLOGY = 'Biology';

    public const SUBJECT_EARTH_SCIENCE = 'Earth Science & Environment';

    public const SUBJECT_ENGLISH = 'English Language';

    public const SUBJECT_FRENCH = 'French Language';

    public const SUBJECT_HISTORY = 'History';

    public const SUBJECT_GEOGRAPHY = 'Geography';

    public const SUBJECT_GENERAL_KNOWLEDGE = 'General Knowledge';

    public const SUBJECT_TEACHER = 'Teacher Resources';

    public const SUBJECT_COMPUTER_SCIENCE = 'Computer Science';

    public const SUBJECT_PROGRAMMING = 'Programming';

    public const SUBJECT_IT = 'Information Technology';

    public const SUBJECT_BUSINESS = 'Business & Management';

    public const SUBJECT_RESEARCH = 'Academic Research Skills';

    public const SUBJECT_HEALTH = 'Health & Wellness';

    public const SUBJECT_LITERATURE = 'Literature';

    /**
     * Every demo record credits this display name. It is deliberately generic:
     * no real author, publisher or date is invented for demonstration data.
     */
    public const DEFAULT_AUTHOR = 'Pi Education Cambodia (Demo Catalogue)';

    /**
     * Build a normalised catalogue record.
     *
     * @param  list<string>  $chapters  Chapter headings, one reader page each.
     * @return array<string, mixed>
     */
    public static function make(
        string $slug,
        string $title,
        string $category,
        string $subject,
        string $description,
        array $chapters,
        string $language = 'km',
        string $audience = 'student',
        ?string $level = null,
        ?string $grade = null,
        ?string $author = null,
        float $price = 0.0,
        bool $free = true,
        bool $vip = false,
        bool $soon = false,
        string $previewMode = Book::PREVIEW_MODE_NONE,
        int $previewPages = 0,
        int $daysAgo = 45,
        int $views = 0,
        int $purchases = 0,
    ): array {
        return [
            'slug' => $slug,
            'title' => $title,
            'category' => $category,
            'subject' => $subject,
            'level' => $level,
            'grade' => $grade,
            'language' => $language,
            'audience' => $audience,
            'author' => $author ?? self::DEFAULT_AUTHOR,
            'description' => $description,
            'chapters' => array_values($chapters),
            'price' => $price,
            'free' => $free,
            'vip' => $vip,
            'soon' => $soon,
            'preview_mode' => $previewMode,
            'preview_pages' => $previewPages,
            'days_ago' => $daysAgo,
            'views' => $views,
            'purchases' => $purchases,
        ];
    }

    /**
     * The category name of a Math grade shelf, e.g. "Math - Grade 7".
     */
    public static function mathGrade(int $grade): string
    {
        return $grade === 0 ? 'Math - Kindergarten' : 'Math - Grade '.$grade;
    }

    /**
     * The category name of a Khmer grade shelf, e.g. "Khmer - Grade 7".
     */
    public static function khmerGrade(int $grade): string
    {
        return $grade === 0 ? 'Khmer - Kindergarten' : 'Khmer - Grade '.$grade;
    }

    /**
     * The grade label stored on a book, e.g. "Grade 7" or "Kindergarten".
     */
    public static function gradeLabel(int $grade): string
    {
        return $grade === 0 ? 'Kindergarten' : 'Grade '.$grade;
    }

    /**
     * The education level that belongs to a school grade.
     */
    public static function levelForGrade(int $grade): string
    {
        return match (true) {
            $grade <= 6 => self::LEVEL_PRIMARY,
            $grade <= 9 => self::LEVEL_LOWER_SECONDARY,
            default => self::LEVEL_UPPER_SECONDARY,
        };
    }
}
