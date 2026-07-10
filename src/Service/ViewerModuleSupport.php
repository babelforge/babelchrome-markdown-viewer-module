<?php

declare(strict_types=1);

namespace BabelForge\BabelChromeMarkdownViewerModule\Service;

use BabelForge\BabelChrome\LocalViewer\Module\ModuleManifest;
use BabelForge\BabelChrome\LocalViewer\Module\ModuleRuntimeContext;
use BabelForge\BabelChrome\LocalViewer\Service\SourceLoader;
use BabelForge\BabelChrome\LocalViewer\Service\SourceRegistry;
use BabelForge\BabelChromeViewerKit\ViewerKitPaths;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Provides shared infrastructure for the Markdown viewer module.
 */
abstract class ViewerModuleSupport
{
    /**
     * Creates a source registry bound to the current local viewer process state.
     *
     * @return SourceRegistry the source registry
     */
    protected function sourceRegistry(): SourceRegistry
    {
        return new SourceRegistry();
    }

    /**
     * Creates a source loader bound to the current local viewer process state.
     *
     * @param SourceRegistry $sourceRegistry the source registry
     *
     * @return SourceLoader the source loader
     */
    protected function sourceLoader(SourceRegistry $sourceRegistry): SourceLoader
    {
        return new SourceLoader($sourceRegistry);
    }

    /**
     * Creates an asset resolver for this module's public assets.
     *
     * @param ModuleManifest       $module  the module manifest
     * @param ModuleRuntimeContext $context the runtime context
     *
     * @return ModuleAssetResolver the asset resolver
     */
    protected function assetPathResolver(ModuleManifest $module, ModuleRuntimeContext $context): ModuleAssetResolver
    {
        return new ModuleAssetResolver($module, $context);
    }

    /**
     * Creates the Twig environment used by viewer module pages.
     *
     * @return Environment the Twig environment
     */
    protected function twig(): Environment
    {
        $loader = new FilesystemLoader($this->templatesDirectory());
        $loader->addPath(ViewerKitPaths::templatesDirectory(), 'BabelChromeViewerKit');

        return new Environment($loader);
    }

    /**
     * Returns this module's template directory.
     *
     * @return string the template directory
     */
    private function templatesDirectory(): string
    {
        return dirname(__DIR__, 2).'/templates';
    }
}
