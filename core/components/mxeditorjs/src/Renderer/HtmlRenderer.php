<?php

declare(strict_types=1);

namespace MxEditorJs\Renderer;

class HtmlRenderer
{
    private array $blockRenderers = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    public function render(array $editorJsData): string
    {
        $blocks = $editorJsData['blocks'] ?? [];
        $html = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';
            $data = $block['data'] ?? [];

            if (!isset($this->blockRenderers[$type])) {
                continue;
            }

            $blockHtml = call_user_func($this->blockRenderers[$type], $data, $block);
            $blockHtml = $this->wrapWithAlignment($blockHtml, $block, $type);
            $html[] = $blockHtml;
        }

        return implode("\n", $html);
    }

    private function wrapWithAlignment(string $html, array $block, string $type): string
    {
        $alignmentBlocks = ['paragraph', 'header', 'list', 'quote'];
        if (!in_array($type, $alignmentBlocks, true)) {
            return $html;
        }

        $alignment = $block['tunes']['alignmentTune']['alignment'] ?? 'left';
        if ($alignment === 'left') {
            return $html;
        }

        return '<div style="text-align: ' . htmlspecialchars($alignment, ENT_QUOTES, 'UTF-8') . '">' . $html . '</div>';
    }

    public function registerBlockRenderer(string $type, callable $renderer): void
    {
        $this->blockRenderers[$type] = $renderer;
    }

    private function registerDefaults(): void
    {
        $this->registerBlockRenderer('paragraph', [$this, 'renderParagraph']);
        $this->registerBlockRenderer('header', [$this, 'renderHeader']);
        $this->registerBlockRenderer('list', [$this, 'renderList']);
        $this->registerBlockRenderer('image', [$this, 'renderImage']);
        $this->registerBlockRenderer('attaches', [$this, 'renderAttaches']);
        $this->registerBlockRenderer('embed', [$this, 'renderEmbed']);
        $this->registerBlockRenderer('delimiter', [$this, 'renderDelimiter']);
        $this->registerBlockRenderer('quote', [$this, 'renderQuote']);
        $this->registerBlockRenderer('code', [$this, 'renderCode']);
        $this->registerBlockRenderer('raw', [$this, 'renderRaw']);
        $this->registerBlockRenderer('table', [$this, 'renderTable']);
        $this->registerBlockRenderer('warning', [$this, 'renderWarning']);
        $this->registerBlockRenderer('checklist', [$this, 'renderChecklistBlock']);
        $this->registerBlockRenderer('gallery', [$this, 'renderGallery']);
    }

    private function renderParagraph(array $data, array $block = []): string
    {
        $text = $data['text'] ?? '';
        return '<p>' . $this->sanitizeInlineHtml($text) . '</p>';
    }

    private function renderHeader(array $data, array $block = []): string
    {
        $text = $data['text'] ?? '';
        $level = (int)($data['level'] ?? 2);
        $level = max(1, min(6, $level));

        return "<h{$level}>" . $this->sanitizeInlineHtml($text) . "</h{$level}>";
    }

    private function renderList(array $data, array $block = []): string
    {
        $style = $data['style'] ?? 'unordered';
        $tag = $style === 'ordered' ? 'ol' : 'ul';
        $items = $data['items'] ?? [];
        $isChecklist = $style === 'checklist';

        $classes = $isChecklist ? ' class="mxeditorjs-checklist"' : '';
        $html = "<{$tag}{$classes}>";
        foreach ($items as $item) {
            $content = is_array($item) ? ($item['content'] ?? '') : (string)$item;
            $checked = $isChecklist && (!empty($item['meta']['checked']) || !empty($item['checked']));
            $checkbox = $isChecklist ? '<input type="checkbox" disabled' . ($checked ? ' checked' : '') . '> ' : '';
            $html .= '<li>' . $checkbox . $this->sanitizeInlineHtml($content) . '</li>';
        }
        $html .= "</{$tag}>";

        return $html;
    }

    private function renderImage(array $data, array $block = []): string
    {
        $url = htmlspecialchars($data['file']['url'] ?? '', ENT_QUOTES, 'UTF-8');
        $caption = $this->sanitizeInlineHtml($data['caption'] ?? '');
        $withBorder = !empty($data['withBorder']);
        $stretched = !empty($data['stretched']);
        $withBackground = !empty($data['withBackground']);

        if (empty($url)) {
            return '';
        }

        $classes = ['mxeditorjs-image'];
        if ($withBorder) {
            $classes[] = 'mxeditorjs-image--bordered';
        }
        if ($stretched) {
            $classes[] = 'mxeditorjs-image--stretched';
        }
        if ($withBackground) {
            $classes[] = 'mxeditorjs-image--background';
        }

        $classAttr = implode(' ', $classes);
        $html = '<figure class="' . $classAttr . '">';
        $html .= '<img src="' . $url . '" alt="' . strip_tags($caption) . '" loading="lazy">';

        if (!empty($caption)) {
            $html .= '<figcaption>' . $caption . '</figcaption>';
        }

        $html .= '</figure>';
        return $html;
    }

    private function renderAttaches(array $data, array $block = []): string
    {
        $file = $data['file'] ?? [];
        $url = $file['url'] ?? '';
        $title = $data['title'] ?? $file['name'] ?? 'Download';

        if (empty($url)) {
            return '';
        }

        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        return '<p><a href="' . $url . '" download>' . $title . '</a></p>';
    }

    private function renderEmbed(array $data, array $block = []): string
    {
        $embed = $data['embed'] ?? '';

        if (empty($embed)) {
            return '';
        }

        // embed URL is a trusted iframe src from Editor.js Embed tool (e.g. YouTube, Vimeo).
        // We wrap it in an iframe rather than injecting raw HTML to prevent XSS.
        $url = htmlspecialchars($embed, ENT_QUOTES, 'UTF-8');
        return '<div class="mxeditorjs-embed"><iframe src="' . $url . '" frameborder="0" allowfullscreen loading="lazy"></iframe></div>';
    }

    private function renderWarning(array $data, array $block = []): string
    {
        $title = htmlspecialchars($data['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($data['message'] ?? '', ENT_QUOTES, 'UTF-8');

        return '<div class="mxeditorjs-warning"><strong>' . $title . '</strong><p>' . $message . '</p></div>';
    }

    private function renderChecklistBlock(array $data, array $block = []): string
    {
        // Delegate to renderList with the checklist style for consistent output.
        $data['style'] = 'checklist';
        $data['items'] = array_map(
            static fn($item) => is_array($item) ? $item : ['content' => $item, 'meta' => []],
            $data['items'] ?? []
        );
        return $this->renderList($data, $block);
    }

    private function renderDelimiter(array $data, array $block = []): string
    {
        return '<hr>';
    }

    private function renderQuote(array $data, array $block = []): string
    {
        $text = $this->sanitizeInlineHtml($data['text'] ?? '');
        $caption = $this->sanitizeInlineHtml($data['caption'] ?? '');

        $html = '<blockquote>' . $text;
        if (!empty($caption)) {
            $html .= '<cite>' . $caption . '</cite>';
        }
        $html .= '</blockquote>';

        return $html;
    }

    private function renderCode(array $data, array $block = []): string
    {
        $code = htmlspecialchars($data['code'] ?? '', ENT_QUOTES, 'UTF-8');
        return '<pre><code>' . $code . '</code></pre>';
    }

    private function renderRaw(array $data, array $block = []): string
    {
        // raw block deliberately allows arbitrary HTML — intended for advanced users only.
        // Sanitisation must be applied at the policy level, not here.
        return $data['html'] ?? '';
    }

    private function renderTable(array $data, array $block = []): string
    {
        $rows = $data['content'] ?? [];
        $withHeadings = !empty($data['withHeadings']);

        if (empty($rows)) {
            return '';
        }

        $html = '<table>';

        foreach ($rows as $index => $row) {
            $tag = ($withHeadings && $index === 0) ? 'th' : 'td';
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= "<{$tag}>" . $this->sanitizeInlineHtml((string)$cell) . "</{$tag}>";
            }
            $html .= '</tr>';
        }

        $html .= '</table>';
        return $html;
    }

    private function renderGallery(array $data, array $block = []): string
    {
        $files = $data['files'] ?? [];
        if (!is_array($files) || $files === []) {
            return '';
        }

        $style = (($data['style'] ?? '') === 'slider') ? 'slider' : 'fit';
        $caption = $this->sanitizeInlineHtml($data['caption'] ?? '');

        $html = '<figure class="mxeditorjs-gallery mxeditorjs-gallery--' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<div class="mxeditorjs-gallery__track">';

        $hasImage = false;
        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }
            $url = $file['url'] ?? '';
            if ($url === '') {
                continue;
            }
            $hasImage = true;
            $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $html .= '<img src="' . $url . '" alt="" loading="lazy">';
        }

        $html .= '</div>';

        if (!$hasImage) {
            return '';
        }

        if ($caption !== '') {
            $html .= '<figcaption>' . $caption . '</figcaption>';
        }

        $html .= '</figure>';

        return $html;
    }

    private function sanitizeInlineHtml(string $html): string
    {
        $allowed = '<b><i><em><strong><a><code><mark><u><br>';
        return strip_tags($html, $allowed);
    }
}
