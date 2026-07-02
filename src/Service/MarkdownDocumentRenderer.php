<?php

declare(strict_types=1);

namespace BabelForge\BabelChromeMarkdownViewerModule\Service;

use BabelForge\BabelChrome\LocalViewer\Service\SourceRegistry;
use BabelForge\BabelChromeMarkdownViewerModule\View\MarkdownView;
use BabelForge\BabelChromeViewerKit\OpenWithViewFactory;
use BabelForge\BabelChromeViewerKit\ViewerSource;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Renders Markdown documents with backend CommonMark and frontend Markdown-It metadata.
 */
final readonly class MarkdownDocumentRenderer
{
    private GithubFlavoredMarkdownConverter $converter;

    /**
     * Creates the Markdown renderer.
     *
     * @param ModuleAssetResolver $assetPathResolver resolves module asset paths
     * @param SourceRegistry      $sourceRegistry    registers rewritten document and asset links
     */
    public function __construct(
        private ModuleAssetResolver $assetPathResolver,
        private SourceRegistry $sourceRegistry,
    ) {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Renders a Markdown source as an HTML document.
     *
     * @param ViewerSource $source  the document source
     * @param Request      $request the current request
     *
     * @return MarkdownView the rendered Markdown view data
     */
    public function render(ViewerSource $source, Request $request): MarkdownView
    {
        $renderedBody = $this->isMermaidDocument($source) ?
            $this->renderMermaidDocument($source->content) :
            (string) $this->converter->convert($source->content);
        [$body, $toc] = $this->decorateDocumentBody($renderedBody, $source, $request);
        $markdownJson = json_encode($source->content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $stylesheetContent = $this->styleContent('vendor/github-markdown-css/github-markdown.min.css').
            "\n".$this->styleContent('vendor/highlight.js/styles/github.css').
            "\n".$this->styleContent('styles/viewer.css');
        $shellClass = '' === trim($toc) ? 'viewer-shell viewer-shell-without-toc' : 'viewer-shell';
        $sourceId = $this->sourceId($request);
        $openWithViewFactory = new OpenWithViewFactory();
        if (false === $markdownJson) {
            $markdownJson = '""';
        }

        return new MarkdownView(
            $source->title,
            $openWithViewFactory->create($sourceId, $source->value, $source->local),
            $body,
            $toc,
            $shellClass,
            $markdownJson,
            $this->importMapContent(),
            $stylesheetContent."\n".$this->styleContent('babel-chrome-viewer-kit/viewer-shell.css'),
            $this->scriptContent('app/viewer.ts')."\n".$this->isolatedScriptContent('babel-chrome-viewer-kit/open-with.ts'),
            $this->theme($request),
            $sourceId,
            $source->local && null !== $source->lastModified && '' !== $sourceId,
            $source->lastModified,
        );
    }

    /**
     * Returns the requested Markdown theme.
     *
     * @param Request $request the current request
     *
     * @return string the supported theme name
     */
    private function theme(Request $request): string
    {
        $theme = $request->query->get('theme', 'github-light');
        $supportedThemes = ['github-light', 'github-dark', 'reader', 'compact'];

        return in_array($theme, $supportedThemes, true) ? $theme : 'github-light';
    }

    /**
     * Returns the current registered source identifier.
     *
     * @param Request $request the current request
     *
     * @return string the source identifier
     */
    private function sourceId(Request $request): string
    {
        $sourceIdValue = $request->attributes->get('sourceId', '');

        return is_string($sourceIdValue) ? $sourceIdValue : '';
    }

    /**
     * Returns inline import map content.
     *
     * @return string the safe inline import map content
     */
    private function importMapContent(): string
    {
        return str_replace('</script', '<\/script', $this->assetPathResolver->importMapContent());
    }

    /**
     * Returns inline stylesheet content.
     *
     * @param string $logicalPath the module asset logical path
     *
     * @return string the safe inline stylesheet content
     */
    private function styleContent(string $logicalPath): string
    {
        return str_replace('</style', '<\/style', $this->assetPathResolver->content($logicalPath));
    }

    /**
     * Returns inline script content.
     *
     * @param string $logicalPath the module asset logical path
     *
     * @return string the safe inline script content
     */
    private function scriptContent(string $logicalPath): string
    {
        return str_replace('</script', '<\/script', $this->assetPathResolver->content($logicalPath));
    }

    /**
     * Returns inline script content isolated from other compiled bundles.
     *
     * @param string $logicalPath the module asset logical path
     *
     * @return string the isolated safe inline script content
     */
    private function isolatedScriptContent(string $logicalPath): string
    {
        return "(function () {\n".$this->scriptContent($logicalPath)."\n})();";
    }

    /**
     * Decorates the document body with rewritten links and heading anchors.
     *
     * @param string       $html    the rendered HTML
     * @param ViewerSource $source  the document source
     * @param Request      $request the current request
     *
     * @return array{0: string, 1: string} the decorated body and table of contents
     */
    private function decorateDocumentBody(string $html, ViewerSource $source, Request $request): array
    {
        $document = new \DOMDocument();
        $previousUseErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);

        foreach (['a' => 'href', 'img' => 'src'] as $tagName => $attributeName) {
            foreach ($document->getElementsByTagName($tagName) as $element) {
                if (!$element->hasAttribute($attributeName)) {
                    continue;
                }

                $element->setAttribute(
                    $attributeName,
                    $this->resolvedUri($element->getAttribute($attributeName), $source, $request, $tagName),
                );
            }
        }

        $headings = $this->decorateHeadings($document);
        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body instanceof \DOMElement) {
            return [$html, ''];
        }

        $rewritten = '';
        foreach ($body->childNodes as $childNode) {
            $rewritten .= $document->saveHTML($childNode);
        }

        return [
            $rewritten,
            $this->renderTableOfContents($headings, $this->babelChromeDocumentUri($source->value, $source)),
        ];
    }

    /**
     * Adds heading identifiers and returns heading metadata.
     *
     * @param \DOMDocument $document the rendered document
     *
     * @return list<array{level: int, id: string, title: string}> the headings
     */
    private function decorateHeadings(\DOMDocument $document): array
    {
        $headings = [];
        $usedIds = [];
        $xpath = new \DOMXPath($document);
        $headingNodes = $xpath->query('//*[self::h1 or self::h2 or self::h3]');
        if (false === $headingNodes) {
            return [];
        }

        foreach ($headingNodes as $heading) {
            if (!$heading instanceof \DOMElement) {
                continue;
            }

            $title = trim($heading->textContent);
            if ('' === $title) {
                continue;
            }

            $id = $heading->getAttribute('id');
            if ('' === $id) {
                $id = $this->uniqueHeadingId($title, $usedIds);
                $heading->setAttribute('id', $id);
            } else {
                $usedIds[$id] = ($usedIds[$id] ?? 0) + 1;
            }

            $headings[] = [
                'level' => (int) substr($heading->tagName, 1),
                'id' => $id,
                'title' => $title,
            ];
        }

        return $headings;
    }

    /**
     * Returns a unique heading identifier.
     *
     * @param string             $title   the heading title
     * @param array<string, int> $usedIds already used IDs
     *
     * @return string the unique identifier
     */
    private function uniqueHeadingId(string $title, array &$usedIds): string
    {
        $baseId = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $this->asciiText($title)) ?? '', '-'));
        if ('' === $baseId) {
            $baseId = 'section';
        }

        $count = $usedIds[$baseId] ?? 0;
        $usedIds[$baseId] = $count + 1;

        return 0 === $count ? $baseId : $baseId.'-'.(string) $count;
    }

    /**
     * Converts text to an ASCII-like string for anchors.
     *
     * @param string $text the input text
     *
     * @return string the converted text
     */
    private function asciiText(string $text): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return false === $converted ? $text : $converted;
    }

    /**
     * Renders the table of contents.
     *
     * @param list<array{level: int, id: string, title: string}> $headings           the headings
     * @param string                                             $currentDocumentUri the canonical BabelChrome URI for the current document
     *
     * @return string the table of contents HTML
     */
    private function renderTableOfContents(array $headings, string $currentDocumentUri): string
    {
        if (count($headings) < 2) {
            return '';
        }

        $items = '';
        foreach ($headings as $heading) {
            $level = max(1, min(3, $heading['level']));
            $title = htmlspecialchars($heading['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $href = htmlspecialchars($currentDocumentUri.'#'.$heading['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $items .= '<li class="viewer-toc-level-'.$level.'"><a href="'.$href.'">'.$title.'</a></li>';
        }

        return '<nav class="viewer-toc" aria-label="Table of contents"><div class="viewer-toc-title">Contents</div><ol>'.$items.'</ol></nav>';
    }

    /**
     * Resolves a link or asset URI.
     *
     * @param string       $uri     the original URI
     * @param ViewerSource $source  the document source
     * @param Request      $request the current request
     * @param string       $tagName the HTML tag name
     *
     * @return string the resolved URI
     */
    private function resolvedUri(string $uri, ViewerSource $source, Request $request, string $tagName): string
    {
        if ('' === trim($uri)) {
            return $uri;
        }

        if ('a' === $tagName && str_starts_with($uri, '#')) {
            return $this->babelChromeDocumentUri($source->value, $source).$uri;
        }

        if (str_starts_with($uri, '#') || str_starts_with($uri, '//')) {
            return $uri;
        }

        [$uriWithoutFragment, $fragment] = $this->splitFragment($uri);
        if (1 === preg_match('/^[a-z][a-z0-9+.-]*:/i', $uriWithoutFragment)) {
            return $this->resolvedAbsoluteUri($uriWithoutFragment, $fragment, $source, $request, $tagName);
        }

        $resolved = $this->joinUri($source->baseUri, $uriWithoutFragment);
        if ('img' === $tagName) {
            return $this->assetViewerUri($resolved, $source, $request);
        }

        if ($this->isMarkdownPath($resolved)) {
            return $this->withFragment($this->babelChromeDocumentUri($resolved, $source), $fragment);
        }

        return $this->withFragment($resolved, $fragment);
    }

    /**
     * Resolves an absolute link or asset URI.
     *
     * @param string       $uri      the absolute URI without fragment
     * @param string       $fragment the URI fragment including the hash, when present
     * @param ViewerSource $source   the document source
     * @param Request      $request  the current request
     * @param string       $tagName  the HTML tag name
     *
     * @return string the resolved URI
     */
    private function resolvedAbsoluteUri(
        string $uri,
        string $fragment,
        ViewerSource $source,
        Request $request,
        string $tagName,
    ): string {
        $scheme = strtolower((string) parse_url($uri, PHP_URL_SCHEME));
        if ('img' === $tagName && 'file' === $scheme) {
            return $this->assetViewerUri($uri, $source, $request);
        }

        if ('a' === $tagName && $this->isMarkdownPath($uri)) {
            return $this->withFragment($this->babelChromeDocumentUri($uri, $source), $fragment);
        }

        return $this->withFragment($uri, $fragment);
    }

    /**
     * Returns a stable BabelChrome URI for a document source.
     *
     * @param string       $resolvedUri the resolved URI
     * @param ViewerSource $source      the current source
     *
     * @return string the BabelChrome document URI
     */
    private function babelChromeDocumentUri(string $resolvedUri, ViewerSource $source): string
    {
        if ($source->local || str_starts_with($resolvedUri, 'file://')) {
            $path = $this->filePathFromUri($resolvedUri);

            return 'babelchrome://viewer/file/'.rawurlencode($path);
        }

        return 'babelchrome://viewer/url/'.rawurlencode($resolvedUri);
    }

    /**
     * Returns a viewer URI for an asset source.
     *
     * @param string       $resolvedUri the resolved URI
     * @param ViewerSource $source      the current source
     * @param Request      $request     the current request
     *
     * @return string the asset URI
     */
    private function assetViewerUri(string $resolvedUri, ViewerSource $source, Request $request): string
    {
        $sourceId = $this->registerResolvedSource($resolvedUri, $source);

        return '/asset/'.rawurlencode($sourceId).'?token='.rawurlencode($this->token($request));
    }

    /**
     * Registers a resolved URI.
     *
     * @param string       $resolvedUri the resolved URI
     * @param ViewerSource $source      the current source
     *
     * @return string the source identifier
     */
    private function registerResolvedSource(string $resolvedUri, ViewerSource $source): string
    {
        if ($source->local || str_starts_with($resolvedUri, 'file://')) {
            return $this->sourceRegistry->register('file', $this->filePathFromUri($resolvedUri));
        }

        return $this->sourceRegistry->register('url', $resolvedUri);
    }

    /**
     * Extracts a decoded local file path from a file URI.
     *
     * @param string $uri the file URI
     *
     * @return string the local file path
     */
    private function filePathFromUri(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) {
            return '';
        }

        return rawurldecode($path);
    }

    /**
     * Returns the current token.
     *
     * @param Request $request the current request
     *
     * @return string the token
     */
    private function token(Request $request): string
    {
        return $request->query->get('token', '');
    }

    /**
     * Returns whether the path points to a Markdown-like document.
     *
     * @param string $uri the URI to inspect
     *
     * @return bool true when the URI uses a supported Markdown extension
     */
    private function isMarkdownPath(string $uri): bool
    {
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) {
            return false;
        }

        $path = strtolower($path);

        return 1 === preg_match('/\\.(md|markdown|mdown|mkd|mmd|mermaid)$/', $path);
    }

    /**
     * Returns whether the source is a standalone Mermaid document.
     *
     * @param ViewerSource $source the document source
     *
     * @return bool true when the source is a Mermaid document
     */
    private function isMermaidDocument(ViewerSource $source): bool
    {
        $path = parse_url($source->value, PHP_URL_PATH);
        if (!is_string($path)) {
            return false;
        }

        $path = strtolower($path);

        return 1 === preg_match('/\\.(mmd|mermaid)$/', $path);
    }

    /**
     * Renders a standalone Mermaid document.
     *
     * @param string $content the Mermaid source
     *
     * @return string the HTML body
     */
    private function renderMermaidDocument(string $content): string
    {
        $escapedContent = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<pre><code class="language-mermaid">'.$escapedContent.'</code></pre>';
    }

    /**
     * Resolves a relative URI against a base URI.
     *
     * @param string $baseUri     the source base URI
     * @param string $relativeUri the relative URI
     *
     * @return string the resolved URI
     */
    private function joinUri(string $baseUri, string $relativeUri): string
    {
        [$relativePath, $query] = $this->splitQuery($relativeUri);
        if (str_starts_with($relativePath, '/')) {
            $parts = parse_url($baseUri);
            $parts = is_array($parts) ? $parts : [];
            $scheme = $parts['scheme'] ?? 'file';
            $host = $parts['host'] ?? '';
            $port = isset($parts['port']) ? ':'.(string) $parts['port'] : '';

            return ('file' === $scheme ? 'file://'.$relativePath : $scheme.'://'.$host.$port.$relativePath).$query;
        }

        $baseParts = parse_url($baseUri);
        $baseParts = is_array($baseParts) ? $baseParts : [];
        $scheme = $baseParts['scheme'] ?? 'file';
        $host = $baseParts['host'] ?? '';
        $port = isset($baseParts['port']) ? ':'.(string) $baseParts['port'] : '';
        $basePath = $baseParts['path'] ?? '/';
        $path = $this->normalizePath(rtrim($basePath, '/').'/'.$relativePath);

        if ('file' === $scheme) {
            return 'file://'.$path.$query;
        }

        return $scheme.'://'.$host.$port.$path.$query;
    }

    /**
     * Splits a URI fragment from the URI.
     *
     * @param string $uri the URI to split
     *
     * @return array{0: string, 1: string} the URI without fragment and the fragment
     */
    private function splitFragment(string $uri): array
    {
        $position = strpos($uri, '#');
        if (false === $position) {
            return [$uri, ''];
        }

        return [substr($uri, 0, $position), substr($uri, $position)];
    }

    /**
     * Splits a query string from a URI.
     *
     * @param string $uri the URI to split
     *
     * @return array{0: string, 1: string} the URI without query and the query
     */
    private function splitQuery(string $uri): array
    {
        $position = strpos($uri, '?');
        if (false === $position) {
            return [$uri, ''];
        }

        return [substr($uri, 0, $position), substr($uri, $position)];
    }

    /**
     * Adds a fragment to a URI.
     *
     * @param string $uri      the URI without fragment
     * @param string $fragment the fragment including the hash, when present
     *
     * @return string the URI with fragment
     */
    private function withFragment(string $uri, string $fragment): string
    {
        return '' === $fragment ? $uri : $uri.$fragment;
    }

    /**
     * Normalizes a URI path.
     *
     * @param string $path the path to normalize
     *
     * @return string the normalized path
     */
    private function normalizePath(string $path): string
    {
        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ('' === $part || '.' === $part) {
                continue;
            }

            if ('..' === $part) {
                array_pop($parts);
                continue;
            }

            $parts[] = $part;
        }

        return '/'.implode('/', $parts);
    }
}
