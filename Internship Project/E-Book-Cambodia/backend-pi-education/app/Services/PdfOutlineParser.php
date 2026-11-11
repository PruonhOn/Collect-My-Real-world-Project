<?php

namespace App\Services;

use App\Services\Support\OutlineReadableFpdi;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfHexString;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObjectReference;
use setasign\Fpdi\PdfParser\Type\PdfString;
use setasign\Fpdi\PdfParser\Type\PdfType;

/**
 * Reads the existing bookmark tree (PDF outline) out of a document.
 *
 * Only structure-level information is extracted - titles and the 1-based page
 * each entry points at - so a PDF's own bookmarks can seed a Table of Contents
 * without any text extraction or OCR. Documents that carry no outline return an
 * empty list (scanned files usually have none), which the caller reports as
 * "extraction unavailable" rather than an error.
 */
class PdfOutlineParser
{
    /**
     * @return array<int, array{title: string, title_en: ?string, page: int, depth: int}>
     */
    public function extract(string $pdfBytes): array
    {
        if (! str_starts_with(ltrim($pdfBytes), '%PDF-')) {
            return [];
        }

        $tmp = tmpfile();

        if ($tmp === false) {
            return [];
        }

        try {
            fwrite($tmp, $pdfBytes);
            $path = stream_get_meta_data($tmp)['uri'];

            $pdf = new OutlineReadableFpdi;
            $pdf->setSourceFile($path);
            $parser = $pdf->parsedDocument();

            $catalog = $parser->getCatalog();
            $outlines = $this->resolve($catalog, $parser);
            $outlines = $outlines instanceof PdfDictionary
                ? $this->resolve(PdfDictionary::get($outlines, 'Outlines'), $parser)
                : null;

            if (! $outlines instanceof PdfDictionary) {
                return [];
            }

            $first = PdfDictionary::get($outlines, 'First');

            if (! $first) {
                return [];
            }

            $pages = $this->pageNumberMap($parser);
            $items = [];
            $this->walk($first, 1, $pages, $parser, $items, []);

            return $items;
        } catch (\Throwable) {
            return [];
        } finally {
            fclose($tmp);
        }
    }

    /**
     * Recursively flatten an outline node into depth-tagged entries. `$seen`
     * guards against malformed documents whose outline links back on itself.
     *
     * @param  array{0: int, 1: int}  $node  object number and generation
     * @param  array<int, int>  $pages  object id => 1-based page number
     * @param  array<int, array{title: string, title_en: ?string, page: int, depth: int}>  $items
     * @param  array<int, bool>  $seen
     */
    private function walk(mixed $node, int $depth, array $pages, object $parser, array &$items, array $seen): void
    {
        $current = $node;

        while ($current) {
            $objectNumber = $current instanceof PdfIndirectObjectReference ? (int) $current->value : 0;

            if ($objectNumber > 0 && isset($seen[$objectNumber])) {
                return;
            }

            if ($objectNumber > 0) {
                $seen[$objectNumber] = true;
            }

            $dictionary = $this->resolve($current, $parser);

            if (! $dictionary instanceof PdfDictionary) {
                return;
            }

            $text = $this->titleOf($dictionary);
            $page = $this->destinationPageNumber($dictionary, $pages, $parser);

            if ($text !== '') {
                $items[] = [
                    'title' => $text,
                    'title_en' => null,
                    'page' => $page,
                    'depth' => $depth,
                ];
            }

            $firstChild = PdfDictionary::get($dictionary, 'First');

            if ($firstChild) {
                $this->walk($firstChild, $depth + 1, $pages, $parser, $items, $seen);
            }

            $current = PdfDictionary::get($dictionary, 'Next');
        }
    }

    private function titleOf(PdfDictionary $dictionary): string
    {
        $title = PdfDictionary::get($dictionary, 'Title');

        $value = match (true) {
            $title instanceof PdfHexString => $this->decodeHexString((string) $title->value),
            $title instanceof PdfString => (string) $title->value,
            default => '',
        };

        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/u', '', $value) ?? '');
    }

    /**
     * PDF hex strings hold raw bytes as hex digits. Khmer text is usually
     * UTF-16BE with a byte-order mark, so a leading FE FF (or FF FE) selects
     * the UTF-16 decoder; plain text is passed through.
     */
    private function decodeHexString(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex) ?? '';

        if ($hex === '' || strlen($hex) % 2 !== 0) {
            return $hex;
        }

        $bytes = hex2bin($hex);

        if ($bytes === false) {
            return $hex;
        }

        $encoding = match (true) {
            str_starts_with($bytes, "\xFE\xFF") => 'UTF-16BE',
            str_starts_with($bytes, "\xFF\xFE") => 'UTF-16LE',
            default => null,
        };

        if ($encoding === null) {
            return $bytes;
        }

        return mb_convert_encoding(substr($bytes, 2), 'UTF-8', $encoding);
    }

    /**
     * Build a map of page object id to 1-based page number so outline
     * destinations resolve to a page without walking the tree twice.
     *
     * @return array<int, int>
     */
    private function pageNumberMap(object $parser): array
    {
        $map = [];

        try {
            $pages = PdfDictionary::get($parser->getCatalog(), 'Pages');
            $this->collectPages($pages, $parser, $map);
        } catch (\Throwable) {
            return $map;
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $map
     */
    private function collectPages(mixed $node, object $parser, array &$map): void
    {
        $dictionary = $this->resolve($node, $parser);

        if (! $dictionary instanceof PdfDictionary) {
            return;
        }

        $kids = PdfDictionary::get($dictionary, 'Kids');

        if ($kids instanceof PdfArray) {
            foreach ($kids->value as $kid) {
                $kidDictionary = $this->resolve($kid, $parser);

                if (! $kidDictionary instanceof PdfDictionary) {
                    continue;
                }

                $type = PdfDictionary::get($kidDictionary, 'Type');

                if ($type && $type->value === 'Pages') {
                    $this->collectPages($kid, $parser, $map);
                } elseif ($kid instanceof PdfIndirectObjectReference) {
                    $map[(int) $kid->value] = count($map) + 1;
                }
            }

            return;
        }

        // A root page tree with no Kids (single page document).
        if ($node instanceof PdfIndirectObjectReference) {
            $map[(int) $node->value] = 1;
        }
    }

    /**
     * Resolve a destination to its 1-based page number, defaulting to 1 so an
     * outline entry is never dropped for an unresolvable target.
     *
     * @param  array<int, int>  $pages
     */
    private function destinationPageNumber(PdfDictionary $dictionary, array $pages, object $parser): int
    {
        $dest = PdfDictionary::get($dictionary, 'Dest');

        if (! $dest instanceof PdfArray) {
            $dest = $this->resolve($dest, $parser);
        }

        if (! $dest instanceof PdfArray || ! isset($dest->value[0])) {
            return 1;
        }

        $target = $dest->value[0];

        // Direct destination: the first element is a page reference (`[ref /XYZ ...]`).
        if ($target instanceof PdfIndirectObjectReference) {
            return $pages[(int) $target->value] ?? 1;
        }

        $resolved = $this->resolve($target, $parser);

        // Named destination dictionary: `/D [pageRef ...]` or `/Dest` action.
        if ($resolved instanceof PdfDictionary) {
            $pageRef = PdfDictionary::get($resolved, 'D') ?? PdfDictionary::get($resolved, 'Dest');

            if ($pageRef instanceof PdfArray && isset($pageRef->value[0])) {
                $first = $pageRef->value[0];
                $id = $first instanceof PdfIndirectObjectReference ? (int) $first->value : null;

                if ($id !== null && isset($pages[$id])) {
                    return $pages[$id];
                }
            }
        }

        return 1;
    }

    /**
     * Resolve indirect object references through FPDI's parser. Primitive
     * values are passed through untouched so callers can read either form.
     */
    private function resolve(mixed $value, object $parser): mixed
    {
        if (! $value instanceof PdfType) {
            return $value;
        }

        try {
            return PdfType::resolve($value, $parser);
        } catch (\Throwable) {
            return $value;
        }
    }
}
