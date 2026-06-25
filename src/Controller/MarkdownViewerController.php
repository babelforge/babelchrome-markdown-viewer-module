<?php

declare(strict_types=1);

namespace BabelForge\BabelChromeMarkdownViewerModule\Controller;

use BabelForge\BabelChrome\LocalViewer\DocumentSource;
use BabelForge\BabelChrome\LocalViewer\Service\SourceLoader;
use BabelForge\BabelChrome\LocalViewer\Service\SourceRegistry;
use BabelForge\BabelChromeMarkdownViewerModule\Service\MarkdownDocumentRenderer;
use BabelForge\BabelChromeMarkdownViewerModule\Service\ModuleAssetResolver;
use BabelForge\BabelChromeMarkdownViewerModule\View\MarkdownView;
use BabelForge\BabelChromeViewerKit\Controller\AbstractViewerController;
use BabelForge\BabelChromeViewerKit\ViewerSource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Handles Symfony HTTP rendering for Markdown viewer pages.
 */
final class MarkdownViewerController extends AbstractViewerController
{
    /**
     * @param Environment         $twig              renders viewer templates
     * @param SourceLoader        $sourceLoader      loads viewer sources
     * @param SourceRegistry      $sourceRegistry    registers rewritten links
     * @param ModuleAssetResolver $assetPathResolver resolves module assets
     */
    public function __construct(
        Environment $twig,
        private readonly SourceLoader $sourceLoader,
        private readonly SourceRegistry $sourceRegistry,
        private readonly ModuleAssetResolver $assetPathResolver,
    ) {
        parent::__construct($twig);
    }

    /**
     * Renders a Markdown document.
     *
     * @param Request $request the current request
     *
     * @return Response the rendered Markdown response
     */
    #[Route('/render', name: 'babelforge_markdown_viewer_render', methods: ['GET'])]
    public function render(Request $request): Response
    {
        return parent::render($request);
    }

    /**
     * Loads the Markdown source for the current request.
     *
     * @param Request $request the current request
     *
     * @return ViewerSource|null the loaded viewer source
     */
    protected function loadSource(Request $request): ?ViewerSource
    {
        $source = $this->sourceLoader->load($request);

        return null === $source ? null : $this->viewerSource($source);
    }

    /**
     * Renders the Markdown-specific view model.
     *
     * @param ViewerSource $source  the loaded viewer source
     * @param Request      $request the current request
     *
     * @return MarkdownView the rendered Markdown view model
     */
    protected function renderView(ViewerSource $source, Request $request): MarkdownView
    {
        return new MarkdownDocumentRenderer($this->assetPathResolver, $this->sourceRegistry)->render($source, $request);
    }

    /**
     * Returns the Twig template used by the Markdown viewer.
     *
     * @return string the template name
     */
    protected function templateName(): string
    {
        return 'markdown/show.html.twig';
    }

    /**
     * Returns the source-not-found page title.
     *
     * @return string the page title
     */
    protected function sourceNotFoundTitle(): string
    {
        return 'Unable to Load Markdown';
    }

    /**
     * Returns the source-not-found visible heading.
     *
     * @return string the visible heading
     */
    protected function sourceNotFoundHeading(): string
    {
        return 'Markdown source not found';
    }

    /**
     * Returns the source-not-found visible message.
     *
     * @return string the visible message
     */
    protected function sourceNotFoundMessage(): string
    {
        return 'The Markdown file or remote Markdown document could not be loaded.';
    }

    /**
     * Returns the stylesheet content used by shared error pages.
     *
     * @return string the safe inline stylesheet content
     */
    protected function errorStylesheetContent(): string
    {
        return str_replace('</style', '<\/style', $this->assetPathResolver->content('styles/viewer.css'));
    }

    /**
     * Converts a host document source into a kit viewer source.
     *
     * @param DocumentSource $source the host document source
     *
     * @return ViewerSource the kit viewer source
     */
    private function viewerSource(DocumentSource $source): ViewerSource
    {
        return new ViewerSource(
            $source->title,
            $source->content,
            $source->baseUri,
            $source->local,
            $source->type,
            $source->value,
            $source->mimeType,
            $source->lastModified,
        );
    }
}
