<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\Profiler;

use Throwable;

interface ProfilerInterface
{
    public function start(string $name, ?string $kind = null): void;

    public function stop(?Throwable $exception = null): void;

    public function stopAndIgnore(): void;
}
