<?php

namespace Database\Seeders\Catalog;

/**
 * Turns a catalogue record into reader-ready plain text.
 *
 * The reader splits the text on `===` markers and the access service cuts the
 * very same markers for free previews, so every section produced here has to
 * be a self-contained page of the book.
 */
final class DemoContentBuilder
{
    /**
     * Khmer digits, used for the Khmer edition of the catalogue.
     */
    private const KHMER_DIGITS = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'];

    /**
     * @param  array<string, mixed>  $record
     */
    public static function build(array $record): string
    {
        $sections = [self::intro($record)];

        foreach (array_values($record['chapters']) as $index => $chapter) {
            $sections[] = self::chapter($record, $index + 1, (string) $chapter);
        }

        $sections[] = self::outro($record);

        return implode("\n\n===\n\n", $sections);
    }

    /**
     * The number of reader pages the built content will produce.
     *
     * @param  array<string, mixed>  $record
     */
    public static function pageCount(array $record): int
    {
        return count($record['chapters']) + 2;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function intro(array $record): string
    {
        $title = (string) $record['title'];
        $subject = (string) $record['subject'];
        $level = self::levelLabel($record);

        if (self::isKhmer($record)) {
            return implode("\n\n", [
                'កាត់ស្ទង់អាន',
                'សូមស្វាគមន៍មកចូលកាន់ «'.$title.'»។ សៀវភៅនេះគឺជាសៀវភៅគណិតវិទ្យាសម្រាប់'
                    .$level.'។ សូមចាំបាច់ចូលចិត្តថា មាតិកានេះជាសៀវភៅសាកល្បង '
                    .'(មាតិកាសាកល្បង) មិនមែនជាសៀវភៅបោះពុម្ពដែលត្រូវបានអនុម័ត ឬបានបោះពុម្ពជាក់លាក់ឡើយ។',
                'ខ្លឹងនៃសៀវភៅគឺការណែនាំពីរបៀបអាន និងការអាចាស្រ័យចំណាំងតាមមុខវិជ្ជា «'
                    .$subject.'»។ អ្នកនឹងរៀនជាចំនាំង ដោយចាប់ផ្តើមពីគំនិតមូលដ្ឋាន រហូតដល់អាចពន្យល់ចំណាំងដោយខ្លួនឯង។',
            ]);
        }

        return implode("\n\n", [
            'How to read this book',
            'Welcome to "'.$title.'". This book is a demonstration title for '.$level.' '
                .'and covers "'.$subject.'". Please note that the text is sample content prepared for '
                .'demonstrating the Pi Education Cambodia library, not a printed or licensed publication.',
            'Each chapter is a self-contained page. Read the section, then answer the questions in the '
                .'notes so you can check what you have understood.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function chapter(array $record, int $number, string $chapter): string
    {
        $subject = (string) $record['subject'];

        if (self::isKhmer($record)) {
            $headings = [
                'មេរៀនទី'.self::khmerNumber($number).': '.$chapter,
                'មេរៀនទី'.self::khmerNumber($number).': '.$chapter,
                'មេរៀនទី'.self::khmerNumber($number).': '.$chapter,
            ];

            $bodies = [
                $chapter.' គឺជាប្រធានបទសំខាន់នៃមុខវិជ្ជា '.$subject.'។ ក្នុងផ្នែកនេះ '
                    .'អ្នកនឹងរៀន '.$chapter.' ជាជំហានៗ ដោយចាប់ផ្តើមពីគំនិតមូលដ្ឋាន '
                    .'រហូតដល់អាចពន្យល់ម្តងក្នុងការដោះសេចក្តីដោយខ្លួនឯង។ ប្រុងប្រយ័ត្ននឹងលក្ខខណ្ឌនៃ'
                    .'បញ្ហា និងសញ្ញាទ្រង់របស់អ្នក ព្រោះនោះជាមួយដែលអ្នកអាចបានស្រែល និងដោះសេចក្តីត្រូវបាន។',
                'ផ្នែកនេះផ្តោតលើ '.$chapter.'។ ក្នុងការសិក្សាប្រធានបទនេះ អ្នកនឹងត្រូវយក'
                    .'គំនិតមូលដ្ឋានមកអាន ដើម្បីយល់ពីមូលដ្ឋានរបស់ប្រព័ន្ធ '.$subject.' រួចបន្ទាប់មក'
                    .'សាកល្បងអនុវត្តវិធីនៃបញ្ហាជាក់លាក់ ដែលឱ្យអ្នកកែបញ្ហាបានដោយខ្លួនឯង។ កុំភ្លេចពីការសរសេរ'
                    .'ដំណើរការរបស់អ្នក ព្រោះវាជាផ្លូវដែលងាយបំផុតដល់ការយើងចងចាំបានត្រឹមត្រាន់។',
                $chapter.' ត្រូវបានបង្ហាញជាជំហានដោយជាតិ ដោយផ្តោតលើឧទាហរណ៍ជាក់លាក់ពីវិជ្ជា '
                    .$subject.'។ អ្នកនឹងរៀនការសម្គាល់លក្ខខណ្ឌជាក់លាក់ ក្នុងការពិនិត្យចំណាំងដោយខ្លួនឯង '
                    .'រហូតដល់អាចពន្យល់ម្តងថាអ្វីគឺជាសំណួរដែលត្រូវបានដាក់សំណួរបន្តក្នុងសៀវភៅនេះ។',
            ];

            return $headings[$number % 3]."\n\n".$bodies[$number % 3]."\n\n".self::practiceNote($number % 3, true);
        }

        $headings = [
            'Lesson '.$number.': '.$chapter,
            'Lesson '.$number.': '.$chapter,
            'Lesson '.$number.': '.$chapter,
        ];

        $bodies = [
            $chapter.' is a central topic in '.$subject.'. In this section you work through it step by step, '
                .'starting from the underlying idea and ending with an explanation you could give to someone else. '
                .'Keep an eye on the structure of the problem and on the working: that is what makes the method '
                .'transferable to questions you have not seen before.',
            'This section focuses on '.$chapter.'. You will first read the key idea in ordinary words, because a '
                .'definition only sticks once you can restate it in your own language. After that you practise the '
                .'method on problems of increasing difficulty, checking each answer before moving on.',
            $chapter.' is developed in three stages here. You will look at a worked example, compare it with a '
                .'second example that changes one condition, and then try the reasoning yourself. At the end of the '
                .'section you should be able to say which step in '.$subject.' did the real work.',
        ];

        return $headings[$number % 3]."\n\n".$bodies[$number % 3]."\n\n".self::practiceNote($number % 3, false);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function outro(array $record): string
    {
        $title = (string) $record['title'];
        $subject = (string) $record['subject'];

        if (self::isKhmer($record)) {
            return implode("\n\n", [
                'ការពិនិត្យមុខវិជ្ជា និងសេចក្តីស្វែងរក',
                'មុខវិជ្ជាសំខាន់ៗនៃ «'.$title.'» ត្រូវបានរ្លឹៀបម្រុងទៅវិញជាបញ្ហាសាកល្បងមួយ ដែលអាច'
                    .'ដឹងតាមជាក់លាក់។ ពិនិត្យថាតើអ្នកអាចពន្យល់គំនិតមូលដ្ឋាននៃ '.$subject.' ដោយឯករាជ្យ '
                    .'បានឬអត់ រួចស្វែងរកបញ្ហាថ្មី និងសៀវភៅផ្សេងៗទៀតតាម Pi Education Cambodia។',
                'ចំណាំងសន្ធិ: ចូលមើលកំណត់ចំណាំងនៅចុងសៀវភៅ រួចសរសេរចម្លើយរបស់អ្នកក្នុងកំណត់ចំណាំង '
                    .'ដោយមិនបានមើលម្ដីដោយ។',
            ]);
        }

        return implode("\n\n", [
            'Review and reflection',
            'The main ideas of "'.$title.'" can be summarised as a short set of claims you should now be able '
                .'to defend. Check that you can explain the core idea of '.$subject.' in your own words, and '
                .'that you can show where each step of the worked examples came from.',
            'Note for the reader: turn to the questions at the end of the book and answer them before you move on, '
                .'then look for the next title that builds on what you have just read.',
        ]);
    }

    private static function practiceNote(int $variant, bool $isKhmer): string
    {
        if ($isKhmer) {
            $notes = [
                'សូមអានផ្នែកនេះម្តងទៀតដោយយោបល់ រួចឆ្លើយសំណួរក្នុងកំណត់ចំណាំងដើម្បីពិនិត្យថាអ្នកបានយល់យ៉ាងណា។',
                'សូមសរសេរចម្លើយរបស់អ្នកក្នុងកំណត់ចំណាំង រួចប៉ុន្មាននាទីក្នុងការបញ្ជាក់ម្តងទៀត។',
                'សូមធ្វើការសម្ភាសន៍ផ្អែក ហើយពិនិត្យចម្លើយជាការស្រាស់ទៅសាច់ញាតិ ឬម៉ាស៊ីនមួយ។',
            ];

            return $notes[$variant];
        }

        $notes = [
            'Read this section once more, slowly, and then answer the questions in the notes so you can check '
                .'what you understood.',
            'Write your answer in the notes, then take a short break before you look at the solution.',
            'Do the reflection task, then check your answer against a classmate or a family member.',
        ];

        return $notes[$variant];
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function levelLabel(array $record): string
    {
        $level = $record['level'] ?? null;
        $grade = $record['grade'] ?? null;

        if (is_string($grade) && $grade !== '') {
            return trim((string) $level.' '.$grade);
        }

        return is_string($level) && $level !== '' ? $level : 'the general reader';
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function isKhmer(array $record): bool
    {
        return ($record['language'] ?? 'km') === 'km';
    }

    private static function khmerNumber(int $number): string
    {
        return implode('', array_map(
            fn (string $digit): string => self::KHMER_DIGITS[(int) $digit],
            str_split((string) $number)
        ));
    }
}
