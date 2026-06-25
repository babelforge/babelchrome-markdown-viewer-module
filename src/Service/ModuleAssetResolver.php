<?php

declare(strict_types=1);

namespace BabelForge\BabelChromeMarkdownViewerModule\Service;

use BabelForge\BabelChrome\LocalViewer\Module\ModuleManifest;
use BabelForge\BabelChrome\LocalViewer\Module\ModuleRuntimeContext;

/**
 * Resolves Markdown viewer assets from this module's public directory.
 */
final class ModuleAssetResolver
{
    /**
     * @var array<string, string>|null
     */
    private ?array $manifest = null;

    /**
     * Creates the module asset resolver.
     *
     * @param ModuleManifest       $module  the module manifest
     * @param ModuleRuntimeContext $context the current runtime context
     */
    public function __construct(
        private readonly ModuleManifest $module,
        private readonly ModuleRuntimeContext $context,
    ) {
    }

    /**
     * Returns the content of a compiled module asset.
     *
     * @param string $logicalPath the module asset logical path
     *
     * @return string the asset content, or an empty string when unavailable
     */
    public function content(string $logicalPath): string
    {
        $publicPath = $this->resolve($logicalPath);
        $assetPath = $this->publicDirectory().'/'.ltrim($publicPath, '/');
        if (!is_file($assetPath) || !is_readable($assetPath)) {
            return '';
        }

        $content = file_get_contents($assetPath);
        if (false === $content) {
            return '';
        }

        return $content;
    }

    /**
     * Returns an import map whose paths target this module's asset endpoint.
     *
     * @return string the browser import map JSON
     */
    public function importMapContent(): string
    {
        $importMapPath = $this->publicDirectory().'/assets/importmap.json';
        if (!is_file($importMapPath) || !is_readable($importMapPath)) {
            return '{}';
        }

        $data = json_decode((string) file_get_contents($importMapPath), true);
        if (!is_array($data)) {
            return '{}';
        }

        $imports = [];
        foreach ($data as $specifier => $entry) {
            if (!is_string($specifier) || !is_array($entry) || !isset($entry['path']) || !is_string($entry['path'])) {
                continue;
            }

            $imports[$specifier] = $this->context->moduleAssetUrl($this->module, ltrim($entry['path'], '/'));
        }

        $browserImportMap = json_encode(['imports' => $imports], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (false === $browserImportMap) {
            return '{}';
        }

        return $browserImportMap;
    }

    /**
     * Resolves one logical asset path to a module public path.
     *
     * @param string $logicalPath the module asset logical path
     *
     * @return string the module public path
     */
    private function resolve(string $logicalPath): string
    {
        $manifest = $this->manifest();

        return $manifest[$logicalPath] ?? '/assets/'.ltrim($logicalPath, '/');
    }

    /**
     * Loads the module asset manifest.
     *
     * @return array<string, string> the manifest map
     */
    private function manifest(): array
    {
        if (null !== $this->manifest) {
            return $this->manifest;
        }

        $manifestPath = $this->publicDirectory().'/assets/manifest.json';
        if (!is_file($manifestPath)) {
            return $this->manifest = [];
        }

        $data = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($data)) {
            return $this->manifest = [];
        }

        /** @var array<string, string> $manifest */
        $manifest = array_filter($data, static fn (mixed $value): bool => is_string($value));

        return $this->manifest = $manifest;
    }

    /**
     * Returns this module's public directory.
     *
     * @return string the public directory path
     */
    private function publicDirectory(): string
    {
        return dirname(__DIR__, 2).'/public';
    }
}
