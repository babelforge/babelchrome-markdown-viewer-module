<?php

declare(strict_types=1);

namespace BabelForge\BabelChrome\LocalViewer\Module;

use Symfony\Component\HttpFoundation\Request;

/**
 * Carries a request dispatched to one BabelChrome module.
 */
final readonly class ModuleRequest
{
    /**
     * @param ModuleManifest       $module  the module manifest
     * @param string               $route   the module route
     * @param Request              $request the HTTP request
     * @param ModuleRuntimeContext $context the runtime context
     */
    public function __construct(
        public ModuleManifest $module,
        public string $route,
        public Request $request,
        public ModuleRuntimeContext $context,
    ) {
    }
}
