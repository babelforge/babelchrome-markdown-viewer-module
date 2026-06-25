<?php

declare(strict_types=1);

namespace BabelForge\BabelChrome\LocalViewer\Module;

/**
 * Describes one BabelChrome module manifest.
 */
final readonly class ModuleManifest
{
    /**
     * @param string $id      the module identifier
     * @param string $name    the module display name
     * @param string $version the module version
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $version,
    ) {
    }
}
