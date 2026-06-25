<?php

declare(strict_types=1);

namespace BabelForge\BabelChrome\LocalViewer\Service;

use BabelForge\BabelChrome\LocalViewer\DocumentSource;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test stub for the host source loader service.
 */
final readonly class SourceLoader
{
    /**
     * Creates the source loader.
     *
     * @param SourceRegistry $sourceRegistry the source registry
     */
    public function __construct(
        private SourceRegistry $sourceRegistry,
    ) {
    }

    /**
     * Loads a test document source from the request query.
     *
     * @param Request $request the HTTP request
     *
     * @return DocumentSource|null the loaded source
     */
    public function load(Request $request): ?DocumentSource
    {
        $content = $request->query->get('content');
        if (!is_string($content)) {
            return null;
        }

        $sourceId = $this->sourceRegistry->register('inline', 'inline');

        return new DocumentSource('Test Markdown', $content, '', false, 'inline', $sourceId, 'text/markdown', null);
    }
}
