<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\Profiler;

use function getenv;
use Throwable;

class SpxProfiler implements ProfilerInterface
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) getenv('SPX_ENABLED');
    }

    public function start(string $name, ?string $kind = null): void
    {
        if ($this->enabled && \function_exists('spx_profiler_start')) {
            spx_profiler_start();
        }
    }

    public function stop(?Throwable $exception = null): void
    {
        if ($this->enabled && \function_exists('spx_profiler_stop')) {
            spx_profiler_stop();
        }
    }

    public function stopAndIgnore(): void
    {
        if ($this->enabled && \function_exists('spx_profiler_stop')) {
            spx_profiler_stop();
        }
    }
}
