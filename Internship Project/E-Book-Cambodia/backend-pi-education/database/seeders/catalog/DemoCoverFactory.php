<?php

namespace Database\Seeders\Catalog;

use Illuminate\Support\Facades\Storage;

/**
 * Generates the placeholder cover images used by the demo catalogue.
 *
 * The covers are deliberately plain and clearly labelled as samples: they are
 * drawn with GD, they carry the subject and level in Latin script (the Khmer
 * script needs a shaping engine that GD does not have) and they never pretend
 * to be artwork from a real publication.
 */
final class DemoCoverFactory
{
    private const WIDTH = 600;

    private const HEIGHT = 900;

    /**
     * Colours per subject, so the catalogue reads as one coherent shelf.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const PALETTE = [
        DemoBook::SUBJECT_MATH => ['#1e1b4b', '#4338ca'],
        DemoBook::SUBJECT_STATISTICS => ['#312e81', '#4f46e5'],
        DemoBook::SUBJECT_KHMER => ['#052e16', '#15803d'],
        DemoBook::SUBJECT_KHMER_LITERATURE => ['#500724', '#be185d'],
        DemoBook::SUBJECT_LITERATURE => ['#4c0519', '#be123c'],
        DemoBook::SUBJECT_PHYSICS => ['#0c4a6e', '#0284c7'],
        DemoBook::SUBJECT_CHEMISTRY => ['#3b0764', '#7c3aed'],
        DemoBook::SUBJECT_BIOLOGY => ['#14532d', '#16a34a'],
        DemoBook::SUBJECT_EARTH_SCIENCE => ['#134e4a', '#0d9488'],
        DemoBook::SUBJECT_ENGLISH => ['#4c0519', '#e11d48'],
        DemoBook::SUBJECT_FRENCH => ['#1e3a8a', '#2563eb'],
        DemoBook::SUBJECT_HISTORY => ['#451a03', '#b45309'],
        DemoBook::SUBJECT_GEOGRAPHY => ['#1a2e05', '#4d7c0f'],
        DemoBook::SUBJECT_GENERAL_KNOWLEDGE => ['#0f172a', '#475569'],
        DemoBook::SUBJECT_TEACHER => ['#164e63', '#0891b2'],
        DemoBook::SUBJECT_COMPUTER_SCIENCE => ['#172554', '#1d4ed8'],
        DemoBook::SUBJECT_PROGRAMMING => ['#1e1b4b', '#5b21b6'],
        DemoBook::SUBJECT_IT => ['#0f172a', '#0369a1'],
        DemoBook::SUBJECT_BUSINESS => ['#431407', '#ea580c'],
        DemoBook::SUBJECT_RESEARCH => ['#4a044e', '#a21caf'],
        DemoBook::SUBJECT_HEALTH => ['#450a0a', '#dc2626'],
    ];

    private const DEFAULT_TOP = '#1e1b4b';

    private const DEFAULT_BOTTOM = '#4338ca';

    /**
     * The cover path a book of the demo catalogue uses.
     */
    public static function pathFor(string $slug): string
    {
        return 'covers/demo/'.$slug.'.png';
    }

    /**
     * Write the placeholder cover for a book and return its stored path.
     *
     * The file is only written once: re-running the seeder keeps the existing
     * image instead of regenerating it.
     */
    public static function ensure(string $slug, string $subject, string $label): ?string
    {
        $disk = Storage::disk((string) config('books.cover_disk'));
        $path = self::pathFor($slug);

        if ($disk->exists($path)) {
            return $path;
        }

        $binary = self::render($subject, $label);

        if ($binary === null) {
            return null;
        }

        $disk->put($path, $binary);

        return $path;
    }

    /**
     * Draw the placeholder cover and return the PNG bytes.
     */
    public static function render(string $subject, string $label): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        if ($image === false) {
            return null;
        }

        [$top, $bottom] = self::PALETTE[$subject] ?? [self::DEFAULT_TOP, self::DEFAULT_BOTTOM];

        self::paintGradient($image, $top, $bottom);
        self::paintAccent($image);
        self::paintHeadline($image, $subject, $label);
        self::paintFooter($image);

        ob_start();
        $ok = imagepng($image, null, 6);
        $binary = ob_get_clean();
        imagedestroy($image);

        if (! $ok || $binary === false) {
            return null;
        }

        return $binary;
    }

    /**
     * @param  \GdImage  $image
     */
    private static function paintGradient($image, string $top, string $bottom): void
    {
        $from = self::rgb($top);
        $to = self::rgb($bottom);

        for ($y = 0; $y < self::HEIGHT; $y++) {
            $ratio = $y / max(1, self::HEIGHT - 1);
            $colour = imagecolorallocate(
                $image,
                (int) round($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) round($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) round($from[2] + ($to[2] - $from[2]) * $ratio)
            );

            imageline($image, 0, $y, self::WIDTH, $y, $colour);
        }
    }

    /**
     * @param  \GdImage  $image
     */
    private static function paintAccent($image): void
    {
        imagealphablending($image, true);

        $light = imagecolorallocatealpha($image, 255, 255, 255, 108);
        $dark = imagecolorallocatealpha($image, 15, 23, 42, 110);

        $points = [
            0, 720, self::WIDTH, 520, self::WIDTH, 640, 0, 840,
        ];

        imagefilledpolygon($image, $points, $light);
        imagefilledpolygon($image, [0, 840, self::WIDTH, 640, self::WIDTH, 760, 0, 900], $dark);
    }

    /**
     * @param  \GdImage  $image
     */
    private static function paintHeadline($image, string $subject, string $label): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocatealpha($image, 255, 255, 255, 40);

        self::write($image, 'PI EDUCATION CAMBODIA', 48, 62, 20, $muted);
        self::write($image, 'DEMO CATALOGUE', 48, 94, 16, $muted);

        $lines = self::wrap(strtoupper($subject), self::WIDTH - 140, 46);

        $y = 330;

        foreach ($lines as $line) {
            self::write($image, $line, 70, $y, 46, $white, true);
            $y += 58;
        }

        $rule = imagecolorallocatealpha($image, 255, 255, 255, 70);
        imagefilledrectangle($image, 70, $y + 10, 260, $y + 14, $rule);

        if ($label !== '') {
            self::write($image, strtoupper($label), 70, $y + 42, 24, $white);
        }
    }

    /**
     * @param  \GdImage  $image
     */
    private static function paintFooter($image): void
    {
        $band = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 30, 27, 75);
        $soft = imagecolorallocate($image, 107, 114, 128);

        imagefilledrectangle($image, 0, self::HEIGHT - 104, self::WIDTH, self::HEIGHT, $band);

        self::write($image, 'SAMPLE COVER', 48, self::HEIGHT - 84, 24, $ink, true);
        self::write($image, 'FOR DEMONSTRATION ONLY', 48, self::HEIGHT - 54, 16, $soft);
    }

    /**
     * Draw a line of text, using a TrueType font when one is available.
     *
     * @param  \GdImage  $image
     */
    private static function write($image, string $text, int $x, int $y, int $size, int $colour, bool $bold = false): void
    {
        if ($text === '') {
            return;
        }

        $font = self::font($bold);

        if ($font !== null) {
            imagettftext($image, $size, 0, $x, $y, $colour, $font, $text);

            return;
        }

        // Fall back to the built-in bitmap font, scaled up so it stays legible
        // on a cover-sized canvas.
        $width = imagefontwidth(5) * strlen($text);
        $height = imagefontheight(5);
        $scale = max(1, (int) floor($size / 10));
        $tile = imagecreatetruecolor($width, $height);

        if ($tile === false) {
            return;
        }

        imagealphablending($tile, false);
        imagesavealpha($tile, true);
        imagefill($tile, 0, 0, imagecolorallocatealpha($tile, 0, 0, 0, 127));
        imagealphablending($tile, true);
        imagestring($tile, 5, 0, 0, $text, imagecolorallocate($tile, 255, 255, 255));

        imagecopyresampled(
            $image,
            $tile,
            $x,
            $y - $height * $scale,
            0,
            0,
            $width * $scale,
            $height * $scale,
            $width,
            $height
        );

        imagedestroy($tile);
    }

    /**
     * Break a string into lines that fit the given width.
     *
     * @return list<string>
     */
    private static function wrap(string $text, int $maxWidth, int $size): array
    {
        $words = preg_split('/\s+/u', $text) ?: [];
        $font = self::font(true);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;

            if (self::widthOf($candidate, $size, $font) <= $maxWidth || $current === '') {
                $current = $candidate;

                continue;
            }

            $lines[] = $current;
            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private static function widthOf(string $text, int $size, ?string $font): int
    {
        if ($font === null) {
            return imagefontwidth(5) * strlen($text);
        }

        $box = imagettfbbox($size, 0, $font, $text);

        if ($box === false) {
            return 0;
        }

        return (int) max(abs($box[2] - $box[0]), abs($box[4] - $box[6]));
    }

    /**
     * The first TrueType font that exists on this machine.
     */
    private static function font(bool $bold = false): ?string
    {
        $candidates = $bold
            ? [
                'C:/Windows/Fonts/arialbd.ttf',
                'C:/Windows/Fonts/segoeuib.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            ]
            : [
                'C:/Windows/Fonts/arial.ttf',
                'C:/Windows/Fonts/segoeui.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
