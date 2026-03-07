<?php

declare(strict_types=1);

namespace MxEditorJs\Service;

class HtmlMigrator
{
    public function convert(string $html): array
    {
        $html = trim($html);
        if (empty($html)) {
            return $this->envelope([]);
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div>' . $html . '</div>';
        @$doc->loadHTML(
            '<?xml encoding="UTF-8">' . $wrapped,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR
        );

        $blocks = [];
        $root = $doc->getElementsByTagName('div')->item(0);

        if (!$root) {
            return $this->envelope([$this->paragraphBlock($html)]);
        }

        foreach ($root->childNodes as $node) {
            $block = $this->nodeToBlock($node, $doc);
            if ($block !== null) {
                $blocks[] = $block;
            }
        }

        if (empty($blocks)) {
            $blocks[] = $this->paragraphBlock($html);
        }

        return $this->envelope($blocks);
    }

    private function nodeToBlock(\DOMNode $node, \DOMDocument $doc): ?array
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->textContent);
            if (empty($text)) {
                return null;
            }
            return $this->paragraphBlock($text);
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return null;
        }

        /** @var \DOMElement $node */
        $tag = strtolower($node->nodeName);

        if (preg_match('/^h([1-6])$/', $tag, $matches)) {
            return [
                'type' => 'header',
                'data' => [
                    'text' => $this->innerHtml($node, $doc),
                    'level' => (int)$matches[1],
                ],
            ];
        }

        if ($tag === 'p') {
            $inner = $this->innerHtml($node, $doc);
            if (empty(trim(strip_tags($inner)))) {
                return null;
            }
            return $this->paragraphBlock($inner);
        }

        if ($tag === 'ul' || $tag === 'ol') {
            return $this->listBlock($node, $doc, $tag === 'ol' ? 'ordered' : 'unordered');
        }

        if ($tag === 'blockquote') {
            return [
                'type' => 'quote',
                'data' => [
                    'text' => $this->innerHtml($node, $doc),
                    'caption' => '',
                    'alignment' => 'left',
                ],
            ];
        }

        if ($tag === 'hr') {
            return [
                'type' => 'delimiter',
                'data' => [],
            ];
        }

        if ($tag === 'pre') {
            $codeNode = $node->getElementsByTagName('code')->item(0);
            $code = $codeNode ? $codeNode->textContent : $node->textContent;
            return [
                'type' => 'code',
                'data' => [
                    'code' => $code,
                ],
            ];
        }

        if ($tag === 'figure') {
            return $this->figureBlock($node, $doc);
        }

        if ($tag === 'img') {
            $src = $node->getAttribute('src');
            $alt = $node->getAttribute('alt');
            if (empty($src)) {
                return null;
            }
            return [
                'type' => 'image',
                'data' => [
                    'file' => ['url' => $src],
                    'caption' => $alt,
                    'withBorder' => false,
                    'stretched' => false,
                    'withBackground' => false,
                ],
            ];
        }

        if ($tag === 'table') {
            return $this->tableBlock($node);
        }

        if (in_array($tag, ['div', 'section', 'article', 'main', 'aside'])) {
            $inner = $this->innerHtml($node, $doc);
            return $this->paragraphBlock($inner);
        }

        $inner = $this->innerHtml($node, $doc);
        if (!empty(trim(strip_tags($inner)))) {
            return $this->paragraphBlock($inner);
        }

        return null;
    }

    private function listBlock(\DOMElement $node, \DOMDocument $doc, string $style): array
    {
        $items = [];
        foreach ($node->childNodes as $li) {
            if ($li->nodeType !== XML_ELEMENT_NODE || strtolower($li->nodeName) !== 'li') {
                continue;
            }
            $items[] = $this->innerHtml($li, $doc);
        }

        return [
            'type' => 'list',
            'data' => [
                'style' => $style,
                'items' => $items,
            ],
        ];
    }

    private function figureBlock(\DOMElement $node, \DOMDocument $doc): ?array
    {
        $img = $node->getElementsByTagName('img')->item(0);
        if (!$img) {
            return null;
        }

        $src = $img->getAttribute('src');
        if (empty($src)) {
            return null;
        }

        $caption = '';
        $figcaption = $node->getElementsByTagName('figcaption')->item(0);
        if ($figcaption) {
            $caption = $this->innerHtml($figcaption, $doc);
        }

        return [
            'type' => 'image',
            'data' => [
                'file' => ['url' => $src],
                'caption' => $caption,
                'withBorder' => false,
                'stretched' => false,
                'withBackground' => false,
            ],
        ];
    }

    private function tableBlock(\DOMElement $node): array
    {
        $rows = [];
        $withHeadings = false;

        $trList = $node->getElementsByTagName('tr');
        foreach ($trList as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $cell) {
                if ($cell->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                $tag = strtolower($cell->nodeName);
                if ($tag === 'th') {
                    $withHeadings = true;
                }
                if ($tag === 'td' || $tag === 'th') {
                    $cells[] = $cell->textContent;
                }
            }
            if (!empty($cells)) {
                $rows[] = $cells;
            }
        }

        return [
            'type' => 'table',
            'data' => [
                'withHeadings' => $withHeadings,
                'content' => $rows,
            ],
        ];
    }

    private function paragraphBlock(string $text): array
    {
        return [
            'type' => 'paragraph',
            'data' => [
                'text' => $text,
            ],
        ];
    }

    private function innerHtml(\DOMNode $node, \DOMDocument $doc): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }
        return trim($html);
    }

    private function envelope(array $blocks): array
    {
        return [
            'time' => time() * 1000,
            'blocks' => $blocks,
            'version' => '2.31.0',
        ];
    }
}
