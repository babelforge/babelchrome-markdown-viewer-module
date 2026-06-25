<?php

declare(strict_types=1);

namespace BabelForge\BabelChrome\LocalViewer\Module;

use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a BabelChrome module entrypoint.
 */
interface BabelChromeModuleInterface
{
    /**
     * Handles one module request.
     *
     * @param ModuleRequest $request the module request
     *
     * @return Response the module response
     */
    public function handle(ModuleRequest $request): Response;
}
