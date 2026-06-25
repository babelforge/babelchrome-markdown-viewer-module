<?php

declare(strict_types=1);

namespace BabelForge\BabelChromeMarkdownViewerModule;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Symfony kernel used by the Markdown viewer module development workspace.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Returns the module-local cache directory.
     *
     * @return string the cache directory path
     */
    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }

    /**
     * Returns the module-local log directory.
     *
     * @return string the log directory path
     */
    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/log';
    }
}
