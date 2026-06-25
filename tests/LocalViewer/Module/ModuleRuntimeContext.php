<?php

declare(strict_types=1);

namespace BabelForge\BabelChrome\LocalViewer\Module;

/**
 * Provides runtime URL helpers to a BabelChrome module.
 */
final readonly class ModuleRuntimeContext
{
    /**
     * @param string $baseUrl   the service base URL
     * @param string $token     the service access token
     * @param string $sourceUrl the source BabelChrome URL
     */
    public function __construct(
        public string $baseUrl,
        public string $token,
        public string $sourceUrl,
    ) {
    }

    /**
     * Builds a tokenized module asset URL.
     *
     * @param ModuleManifest $module the module manifest
     * @param string         $path   the module asset path
     *
     * @return string the asset URL
     */
    public function moduleAssetUrl(ModuleManifest $module, string $path): string
    {
        return $this->baseUrl.'/module/'.$module->id.'/assets/'.ltrim($path, '/').'?token='.rawurlencode($this->token);
    }
}
