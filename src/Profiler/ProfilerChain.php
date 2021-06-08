<?php

declare(strict_types=1);

namespace Sourceability\InstrumentationBundle\Profiler;

use Throwable;

class ProfilerChain implements ProfilerInterface
{
    /**
     * @var iterable<ProfilerInterface>
     */
    private iterable $profilers;

    /**
     * @param iterable<ProfilerInterface> $profilers
     */
    public function __construct(iterable $profilers)
    {
        $this->profilers = $profilers;
    }

    public function start(string $name, ?string $kind = null): void
    {
        foreach ($this->profilers as $profiler) {
            $profiler->start($name, $kind);
        }
    }

    public function stop(?Throwable $exception = null): void
    {
        foreach ($this->profilers as $profiler) {
            $profiler->stop($exception);
        }
    }

    public function stopAndIgnore(): void
    {
        foreach ($this->profilers as $profiler) {
            $profiler->stopAndIgnore();
        }
    }
}
