<?php

declare(strict_types=1);

namespace BabelForge\BabelChrome\LocalViewer\Service;

/**
 * Test stub for the host source registry service.
 */
final class SourceRegistry
{
    /**
     * Registers a source and returns a stable test identifier.
     *
     * @param string $type  the source type
     * @param string $value the source value
     *
     * @return string the registered source identifier
     */
    public function register(string $type, string $value): string
    {
        return hash('sha256', $type."\n".$value);
    }
}
