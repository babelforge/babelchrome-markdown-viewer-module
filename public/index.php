<?php

declare(strict_types=1);

use BabelForge\BabelChrome\LocalViewer\Module\ModuleManifest;
use BabelForge\BabelChrome\LocalViewer\Module\ModuleRequest;
use BabelForge\BabelChrome\LocalViewer\Module\ModuleRuntimeContext;
use BabelForge\BabelChromeMarkdownViewerModule\Module\MarkdownViewerModule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require dirname(__DIR__).'/vendor/autoload.php';

$request = Request::createFromGlobals();
if ('/health' === $request->getPathInfo()) {
    $response = new Response('ok', Response::HTTP_OK);
    if ('cli-server' === PHP_SAPI) {
        $response->send();
    }

    return $response;
}

$sourceId = $_SERVER['BABELCHROME_SOURCE_ID'] ?? '';
if (is_string($sourceId) && '' !== $sourceId) {
    $request->attributes->set('sourceId', $sourceId);
}

$manifestContent = file_get_contents(dirname(__DIR__).'/manifest.json');
if (false === $manifestContent) {
    throw new RuntimeException('Unable to read module manifest.');
}

$manifestData = json_decode($manifestContent, true, flags: JSON_THROW_ON_ERROR);
if (!is_array($manifestData)) {
    throw new RuntimeException('Unable to decode module manifest.');
}

$route = $request->headers->get('X-BabelChrome-Module-Route', '');
if (!is_string($route) || '' === $route) {
    $route = trim($request->getPathInfo(), '/');
}
if ('' === $route) {
    $route = (string) ($_SERVER['BABELCHROME_MODULE_ROUTE'] ?? 'markdown');
}

$baseUrlHeader = $request->headers->get('X-BabelChrome-Local-Service-Base-Url', '');
$tokenHeader = $request->headers->get('X-BabelChrome-Local-Service-Token', '');
$sourceUrlHeader = $request->headers->get('X-BabelChrome-Source-Url', '');
$tokenValue = '' !== $tokenHeader ? $tokenHeader : $request->query->get('token', '');
$sourceUrlValue = '' !== $sourceUrlHeader ? $sourceUrlHeader : $request->query->get('sourceUrl', '');

$response = (new MarkdownViewerModule())->handle(new ModuleRequest(
    new ModuleManifest(
        is_string($manifestData['id'] ?? null) ? $manifestData['id'] : 'babelforge.markdown-viewer',
        is_string($manifestData['name'] ?? null) ? $manifestData['name'] : 'Markdown Viewer',
        is_string($manifestData['version'] ?? null) ? $manifestData['version'] : '0.0.0',
    ),
    $route,
    $request,
    new ModuleRuntimeContext(
        is_string($baseUrlHeader) && '' !== $baseUrlHeader ? rtrim($baseUrlHeader, '/') : rtrim($request->getSchemeAndHttpHost(), '/'),
        is_string($tokenValue) ? $tokenValue : '',
        is_string($sourceUrlValue) ? $sourceUrlValue : '',
    ),
));

if ('cli-server' === PHP_SAPI) {
    $response->send();
}

return $response;
