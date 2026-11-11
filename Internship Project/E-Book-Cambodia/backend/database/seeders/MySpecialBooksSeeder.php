<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class MySpecialBooksSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::where('slug', 'kids')->first();

        $book = Book::firstOrCreate(
            ['slug' => 'my-special-books'],
            [
                'category_id' => $category?->id,
                'author_id' => null,
                'price' => 0,
                'is_free' => true,
                'is_published' => true,
                'pages' => 10,
                'file_type' => 'text',
                'file_size' => 0,
                'file_path' => null,
                'cover_image' => null,
                'views_count' => 0,
                'downloads_count' => 0,
            ]
        );

        // Exactly 10 pages: the frontend reader splits content every 250 words,
        // so 2,500 words renders as exactly 10 pages.
        $content = $this->buildContent(10, 250);

        foreach (['km', 'en'] as $locale) {
            \App\Models\BookTranslation::firstOrCreate(
                ['book_id' => $book->id, 'locale' => $locale],
                [
                    'title' => 'My Special Books',
                    'description' => 'A simple 10-page book filled with kind words, encouragement, and good reading habits for everyone.',
                    'content' => $content,
                ]
            );
        }

        $this->command?->info("'My Special Books' created with exactly 10 pages.");
    }

    protected function buildContent(int $pages, int $wordsPerPage): string
    {
        // Determine the total heading word count so we can size the body text
        // precisely and end up with exactly pages * wordsPerPage words total.
        $headings = $this->headings();
        $headingWords = 0;
        foreach (array_slice($headings, 0, $pages) as $h) {
            $headingWords += count(preg_split('/\s+/u', trim($h)));
        }

        $totalWords = $pages * $wordsPerPage;
        $bodyTarget = $totalWords - $headingWords;
        $bodyPerPage = intdiv($bodyTarget, $pages);
        $remainder = $bodyTarget % $pages;

        $blocks = [];
        for ($i = 1; $i <= $pages; $i++) {
            $heading = $headings[$i] ?? "Page $i";
            $count = $bodyPerPage + ($i <= $remainder ? 1 : 0);
            $blocks[] = $heading."\n\n".$this->bodyText($count, $i);
        }

        return implode("\n\n", $blocks);
    }

    protected function headings(): array
    {
        return [
            1 => 'Chapter One - A Warm Welcome',
            2 => 'Chapter Two - The Gift of a Smile',
            3 => 'Chapter Three - Every Small Step Counts',
            4 => 'Chapter Four - Words That Lift Us Up',
            5 => 'Chapter Five - The Courage to Try',
            6 => 'Chapter Six - Kindness Goes Around',
            7 => 'Chapter Seven - Learning Every Day',
            8 => 'Chapter Eight - Believing in Yourself',
            9 => 'Chapter Nine - The Joy of Sharing',
            10 => 'Chapter Ten - A Bright Future Awaits',
        ];
    }

    protected function bodyText(int $wordCount, int $page): string
    {
        $sentences = [
            'Every new day brings us a fresh chance to learn something wonderful.',
            'The small things we do with love can make a very big difference in the world.',
            'Reading opens a door to new ideas, new places, and new dreams.',
            'Always be curious, because curiosity is the beginning of every great discovery.',
            'A gentle word can brighten someone else\'s whole day in an instant.',
            'Practice makes progress, and every small effort moves us forward.',
            'Take a deep breath, believe in yourself, and give it your best try.',
            'Kindness is a gift that grows when we share it with other people.',
            'Patience and hard work turn little steps into great achievements.',
            'When we help each other, we build a stronger and happier community.',
            'Dream big, start small, and keep taking one step at a time.',
            'The more we read, the more wonderful worlds open up before us.',
            'Remember that everyone learns at their own pace, and that is perfectly okay.',
            'Our differences make us interesting, and our shared hopes bring us together.',
            'A clear goal, a kind heart, and steady effort can carry us far in life.',
            'Look for the good in every situation, and you will always find something to be thankful for.',
            'Today is a wonderful day to start something new and exciting.',
            'Sharing what we know makes our knowledge richer and more useful for everyone.',
            'Do not be afraid to ask questions, because questions are the keys to learning.',
            'Celebrate your progress, no matter how small it may seem.',
            'A moment of courage can open doors that stay closed for the fearful.',
            'Saving time to read each day fills our minds with treasures.',
            'True friends encourage us and cheer us on through every challenge.',
            'The things we practice with dedication become the skills we treasure.',
            'Even the longest journey begins with a single, steady step forward.',
            'Stay curious, stay kind, and never stop exploring the world around you.',
            'Great things are built one small brick at a time, with love and patience.',
            'A hopeful mind sees possibilities where others see only problems.',
            'When we listen carefully, we learn far more than when we speak.',
            'Give yourself permission to be a beginner and to enjoy the journey.',
        ];

        // Fill with sentences up to the exact target word count.
        $words = [];
        $i = 0;

        while (count($words) < $wordCount) {
            $sentence = $sentences[$i % count($sentences)];
            $sentenceWords = preg_split('/\s+/u', trim($sentence));
            foreach ($sentenceWords as $w) {
                if (count($words) >= $wordCount) {
                    break;
                }
                $words[] = $w;
            }
            $i++;
        }

        return implode(' ', $words);
    }
}
