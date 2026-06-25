<?php

declare(strict_types=1);

use BabelForge\BabelChrome\LocalViewer\Module\ModuleManifest;
use BabelForge\BabelChrome\LocalViewer\Module\ModuleRequest;
use BabelForge\BabelChrome\LocalViewer\Module\ModuleRuntimeContext;
use BabelForge\BabelChromeMarkdownViewerModule\Module\MarkdownViewerModule;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

$request = Request::createFromGlobals();
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

return (new MarkdownViewerModule())->handle(new ModuleRequest(
    ModuleManifest::fromArray($manifestData, dirname(__DIR__)),
    (string) ($_SERVER['BABELCHROME_MODULE_ROUTE'] ?? 'markdown'),
    $request,
    ModuleRuntimeContext::fromRequest($request),
));
