<?php

declare(strict_types=1);

namespace BabelForge\BabelChromeMarkdownViewerModule\Tests;

use BabelForge\BabelChrome\LocalViewer\Module\ModuleManifest;
use BabelForge\BabelChrome\LocalViewer\Module\ModuleRuntimeContext;
use BabelForge\BabelChrome\LocalViewer\Service\SourceRegistry;
use BabelForge\BabelChromeMarkdownViewerModule\Service\MarkdownDocumentRenderer;
use BabelForge\BabelChromeMarkdownViewerModule\Service\ModuleAssetResolver;
use BabelForge\BabelChromeViewerKit\ViewerSource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Markdown viewer module renderer.
 */
final class MarkdownDocumentRendererTest extends TestCase
{
    /**
     * Verifies that Markdown rendering uses module-local runtime assets.
     */
    public function testRenderUsesModuleAssets(): void
    {
        $renderer = new MarkdownDocumentRenderer($this->assetResolver(), new SourceRegistry());
        $view = $renderer->render(
            new ViewerSource('README.md', "# Title\n\nContent", '', false, 'file', '/tmp/README.md', 'text/markdown', null),
            Request::create('/markdown'),
        );

        self::assertSame('README.md', $view->title);
        self::assertStringContainsString('<h1', $view->bodyHtml);
        self::assertStringContainsString('/module/babelforge.markdown-viewer/assets/assets/', $view->importMapContent);
        self::assertStringContainsString('viewer-document', $view->stylesheetContent);
        self::assertStringContainsString('markdown-source', $view->scriptContent);
    }

    /**
     * Verifies that local Markdown links use the generic BabelChrome viewer URL.
     */
    public function testRenderUsesGenericViewerUrlForLocalMarkdownLinks(): void
    {
        $workspaceDirectory = sys_get_temp_dir().'/babelchrome-markdown-link-test-'.bin2hex(random_bytes(6));
        if (!mkdir($workspaceDirectory.'/doc', 0o775, true) && !is_dir($workspaceDirectory.'/doc')) {
            self::fail('Unable to create Markdown link test directory.');
        }

        file_put_contents($workspaceDirectory.'/doc/a.md', '# Linked');

        $renderer = new MarkdownDocumentRenderer($this->assetResolver(), new SourceRegistry());
        $view = $renderer->render(
            new ViewerSource('README.md', '[Linked](./doc/a.md#part)', 'file://'.$workspaceDirectory.'/', true, 'file', $workspaceDirectory.'/README.md', 'text/markdown', null),
            Request::create('/markdown'),
        );

        self::assertStringContainsString(
            'href="babelchrome://viewer/file/'.rawurlencode($workspaceDirectory.'/doc/a.md').'#part"',
            $view->bodyHtml,
        );
    }

    /**
     * Verifies that remote Markdown links use the generic BabelChrome viewer URL.
     */
    public function testRenderUsesGenericViewerUrlForRemoteMarkdownLinks(): void
    {
        $renderer = new MarkdownDocumentRenderer($this->assetResolver(), new SourceRegistry());
        $view = $renderer->render(
            new ViewerSource('README.md', '[Remote](./next.md#top)', 'https://example.com/docs/', false, 'url', 'https://example.com/docs/README.md', 'text/markdown', null),
            Request::create('/markdown'),
        );

        self::assertStringContainsString(
            'href="babelchrome://viewer/url/'.rawurlencode('https://example.com/docs/next.md').'#top"',
            $view->bodyHtml,
        );
    }

    /**
     * Verifies that same-document anchor links use the canonical BabelChrome viewer URL.
     */
    public function testRenderUsesGenericViewerUrlForSameDocumentAnchorLinks(): void
    {
        $renderer = new MarkdownDocumentRenderer($this->assetResolver(), new SourceRegistry());
        $sourcePath = '/tmp/README.md';
        $view = $renderer->render(
            new ViewerSource('README.md', "[Install](#install-modules)\n\n## Install Modules", 'file:///tmp/', true, 'file', $sourcePath, 'text/markdown', null),
            Request::create('/markdown'),
        );

        self::assertStringContainsString(
            'href="babelchrome://viewer/file/'.rawurlencode($sourcePath).'#install-modules"',
            $view->bodyHtml,
        );
    }

    /**
     * Verifies that the generated table of contents uses the canonical BabelChrome viewer URL.
     */
    public function testRenderUsesGenericViewerUrlForTableOfContentsLinks(): void
    {
        $renderer = new MarkdownDocumentRenderer($this->assetResolver(), new SourceRegistry());
        $sourcePath = '/tmp/README.md';
        $view = $renderer->render(
            new ViewerSource('README.md', "# Title\n\n## Install Modules\n\n## Configure", 'file:///tmp/', true, 'file', $sourcePath, 'text/markdown', null),
            Request::create('/markdown'),
        );

        self::assertStringContainsString(
            'href="babelchrome://viewer/file/'.rawurlencode($sourcePath).'#install-modules"',
            $view->tableOfContentsHtml,
        );
        self::assertStringNotContainsString(
            'href="#install-modules"',
            $view->tableOfContentsHtml,
        );
    }

    /**
     * Creates the module asset resolver used by tests.
     *
     * @return ModuleAssetResolver the module asset resolver
     */
    private function assetResolver(): ModuleAssetResolver
    {
        return new ModuleAssetResolver(
            new ModuleManifest('babelforge.markdown-viewer', 'Markdown Viewer', '1.0.0'),
            new ModuleRuntimeContext('http://127.0.0.1:12345', 'test-token', 'babelchrome://markdown/test'),
        );
    }
}
