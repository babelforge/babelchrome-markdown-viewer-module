<?php

declare(strict_types=1);

namespace BabelForge\BabelChromeMarkdownViewerModule\Module;

use BabelForge\BabelChrome\LocalViewer\Module\BabelChromeModuleInterface;
use BabelForge\BabelChrome\LocalViewer\Module\ModuleRequest;
use BabelForge\BabelChromeMarkdownViewerModule\Controller\MarkdownViewerController;
use BabelForge\BabelChromeMarkdownViewerModule\Service\ViewerModuleSupport;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders Markdown documents as a BabelChrome viewer module.
 */
final class MarkdownViewerModule extends ViewerModuleSupport implements BabelChromeModuleInterface
{
    /**
     * Handles one Markdown viewer module request.
     *
     * @param ModuleRequest $request the module request context
     *
     * @return Response the rendered Markdown response
     */
    public function handle(ModuleRequest $request): Response
    {
        $sourceRegistry = $this->sourceRegistry();

        return new MarkdownViewerController(
            $this->twig(),
            $this->sourceLoader($sourceRegistry),
            $sourceRegistry,
            $this->assetPathResolver($request->module, $request->context),
        )->render($request->request);
    }
}
