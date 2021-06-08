<?php

declare(strict_types=1);

namespace Sourceability\InstrumentationBundle\Profiler;

use function class_exists;
use function sprintf;
use Throwable;
use Tideways\Profiler;

class TidewaysProfiler implements ProfilerInterface
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = class_exists('Tideways\Profiler');
    }

    public function start(string $name, ?string $kind = null): void
    {
        if (!$this->enabled) {
            return;
        }

        if (null !== $kind) {
            $transactionName = sprintf('%s_%s', $kind, $name);
        } else {
            $transactionName = $name;
        }

        Profiler::start();
        Profiler::setTransactionName($transactionName);
    }

    public function stop(?Throwable $exception = null): void
    {
        if (!$this->enabled) {
            return;
        }

        if (null !== $exception) {
            Profiler::logException($exception);
        }

        Profiler::stop();
    }

    public function stopAndIgnore(): void
    {
        if (!$this->enabled) {
            return;
        }

        Profiler::ignoreTransaction();
        Profiler::stop();
    }
}
