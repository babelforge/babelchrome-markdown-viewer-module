<?php

declare(strict_types=1);

namespace BabelForge\BabelChromeMarkdownViewerModule\View;

use BabelForge\BabelChromeViewerKit\OpenWithView;

/**
 * Carries all data needed to render a Markdown viewer page.
 */
final readonly class MarkdownView
{
    /**
     * @param string       $title               the document title
     * @param OpenWithView $openWithView        the shared Open With control view model
     * @param string       $bodyHtml            the rendered Markdown body HTML
     * @param string       $tableOfContentsHtml the generated table of contents HTML
     * @param string       $shellClass          the viewer shell CSS class
     * @param string       $markdownJson        the JSON-encoded Markdown source
     * @param string       $importMapContent    the inline import map content
     * @param string       $stylesheetContent   the inline stylesheet content
     * @param string       $scriptContent       the inline module script content
     * @param string       $theme               the viewer theme
     * @param string       $sourceId            the registered source identifier
     * @param bool         $autoRefreshEnabled  whether auto refresh is enabled
     * @param int|null     $lastModified        the source last modification timestamp
     */
    public function __construct(
        public string $title,
        public OpenWithView $openWithView,
        public string $bodyHtml,
        public string $tableOfContentsHtml,
        public string $shellClass,
        public string $markdownJson,
        public string $importMapContent,
        public string $stylesheetContent,
        public string $scriptContent,
        public string $theme,
        public string $sourceId,
        public bool $autoRefreshEnabled,
        public ?int $lastModified,
    ) {
    }
}
