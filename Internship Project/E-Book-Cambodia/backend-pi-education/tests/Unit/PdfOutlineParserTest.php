<?php

namespace Tests\Unit;

use App\Services\PdfOutlineParser;
use PHPUnit\Framework\TestCase;

class PdfOutlineParserTest extends TestCase
{
    private function parser(): PdfOutlineParser
    {
        return new PdfOutlineParser;
    }

    public function test_returns_empty_for_non_pdf_bytes(): void
    {
        $this->assertSame([], $this->parser()->extract('not a pdf'));
    }

    public function test_returns_empty_when_pdf_has_no_outline(): void
    {
        $this->assertSame([], $this->parser()->extract($this->pdf(['Page 1', 'Page 2'], null)));
    }

    public function test_extracts_flat_outline_with_page_numbers(): void
    {
        $items = $this->parser()->extract($this->pdf(
            ['Cover', 'Chapter A', 'Chapter B'],
            [
                ['title' => 'Cover', 'page' => 1, 'children' => []],
                ['title' => 'Chapter A', 'page' => 2, 'children' => []],
                ['title' => 'Chapter B', 'page' => 3, 'children' => []],
            ]
        ));

        $this->assertSame([
            ['title' => 'Cover', 'title_en' => null, 'page' => 1, 'depth' => 1],
            ['title' => 'Chapter A', 'title_en' => null, 'page' => 2, 'depth' => 1],
            ['title' => 'Chapter B', 'title_en' => null, 'page' => 3, 'depth' => 1],
        ], $items);
    }

    public function test_extracts_nested_outline_with_depths(): void
    {
        $items = $this->parser()->extract($this->pdf(
            ['Cover', 'Chapter', 'Section', 'Subsection'],
            [
                ['title' => 'Cover', 'page' => 1, 'children' => []],
                ['title' => 'Chapter', 'page' => 2, 'children' => [
                    ['title' => 'Section', 'page' => 3, 'children' => [
                        ['title' => 'Subsection', 'page' => 4, 'children' => []],
                    ]],
                ]],
            ]
        ));

        $this->assertSame([
            ['title' => 'Cover', 'title_en' => null, 'page' => 1, 'depth' => 1],
            ['title' => 'Chapter', 'title_en' => null, 'page' => 2, 'depth' => 1],
            ['title' => 'Section', 'title_en' => null, 'page' => 3, 'depth' => 2],
            ['title' => 'Subsection', 'title_en' => null, 'page' => 4, 'depth' => 3],
        ], $items);
    }

    public function test_handles_khmer_utf16_titles(): void
    {
        $khmer = 'ជំពូកទី ១';
        $items = $this->parser()->extract($this->pdf(
            ['Page'],
            [['title' => $khmer, 'page' => 1, 'children' => [], 'utf16' => true]]
        ));

        $this->assertCount(1, $items);
        $this->assertSame($khmer, $items[0]['title']);
        $this->assertSame(1, $items[0]['page']);
    }

    /**
     * Build a minimal valid PDF with the given pages and, optionally, a linked
     * outline. Not a general-purpose generator - just enough structure for the
     * parser (catalog, page tree, outline with /Dest page references).
     *
     * @param  array<int, string>  $pages
     * @param  array<int, array{title: string, page: int, children: array, utf16?: bool}>|null  $outline
     */
    private function pdf(array $pages, ?array $outline): string
    {
        $objects = [];
        $kids = [];
        $pageIds = [];
        $nextId = 2;

        foreach ($pages as $index => $text) {
            $nextId++;
            $pageIds[$index + 1] = $nextId;
            $kids[] = "{$nextId} 0 R";
            $objects[$nextId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>';
        }

        $catalog = '<< /Type /Catalog /Pages 2 0 R';

        if ($outline) {
            $outlineId = ++$nextId;
            $tree = $this->flattenOutline($outline, $pageIds, $nextId, $outlineId);
            $nextId = $tree['nextId'];

            foreach ($tree['entries'] as $entry) {
                $objects[$entry['id']] = $entry['body'];
            }

            $catalog .= " /Outlines {$outlineId} 0 R";
            $objects[$outlineId] = '<< /Type /Outlines /Count '.count($tree['entries'])
                ." /First {$tree['first']} 0 R /Last {$tree['last']} 0 R >>";
        }

        $catalog .= ' >>';

        $objects[2] = '<< /Type /Pages /Count '.count($pages).' /Kids ['.implode(' ', $kids).'] >>';
        $objects[1] = $catalog;

        ksort($objects);

        $body = "%PDF-1.7\n";
        $offsets = [];

        foreach ($objects as $id => $value) {
            $offsets[$id] = strlen($body);
            $body .= "{$id} 0 obj\n{$value}\nendobj\n";
        }

        $maxId = max(array_keys($objects));
        $xref = strlen($body);
        $body .= "xref\n0 ".($maxId + 1)."\n0000000000 65535 f \n";

        for ($id = 1; $id <= $maxId; $id++) {
            $body .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }

        $body .= "trailer\n<< /Size ".($maxId + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $body;
    }

    /**
     * Turn a nested outline definition into flat objects linked with
     * /First, /Last, /Next, /Prev and /Parent, matching the PDF spec.
     *
     * @param  array<int, array{title: string, page: int, children: array, utf16?: bool}>  $nodes
     * @param  array<int, int>  $pageIds
     * @return array{entries: array<int, array{id: int, body: string}>, first: ?int, last: ?int, nextId: int}
     */
    private function flattenOutline(array $nodes, array $pageIds, int $nextId, ?int $parentId = null): array
    {
        $entries = [];
        $ids = [];
        $children = [];

        foreach ($nodes as $node) {
            $id = ++$nextId;
            $ids[] = $id;
            $children[$id] = $node['children'] ?? [];
            $entries[] = ['id' => $id, 'title' => $node['title'], 'page' => $node['page'], 'utf16' => $node['utf16'] ?? false];
        }

        $bodies = [];

        foreach ($entries as $index => $entry) {
            $id = $entry['id'];
            $prev = $ids[$index - 1] ?? null;
            $next = $ids[$index + 1] ?? null;
            $first = $last = null;

            if (! empty($children[$id])) {
                $sub = $this->flattenOutline($children[$id], $pageIds, $nextId, $id);
                $nextId = $sub['nextId'];
                $bodies = array_merge($bodies, $sub['entries']);
                $first = $sub['first'];
                $last = $sub['last'];
            }

            $pageId = $pageIds[$entry['page']];
            $body = '<< /Title '.$this->titleToken($entry['title'], $entry['utf16'])
                ." /Dest [{$pageId} 0 R /Fit] /Parent {$parentId} 0 R"
                .($prev ? " /Prev {$prev} 0 R" : '')
                .($next ? " /Next {$next} 0 R" : '')
                .($first ? " /First {$first} 0 R /Last {$last} 0 R" : '')
                .' >>';

            $bodies[] = ['id' => $id, 'body' => $body];
        }

        return [
            'entries' => $bodies,
            'first' => $ids[0] ?? null,
            'last' => end($ids) ?: null,
            'nextId' => $nextId,
        ];
    }

    private function titleToken(string $title, bool $utf16): string
    {
        if (! $utf16) {
            return '('.str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $title).')';
        }

        $bytes = "\xFE\xFF";
        foreach (mb_str_split($title) as $char) {
            $bytes .= mb_convert_encoding($char, 'UTF-16BE', 'UTF-8');
        }

        return '<'.bin2hex($bytes).'>';
    }
}
