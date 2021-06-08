<?php

declare(strict_types=1);

namespace Sourceability\InstrumentationBundle\Profiler;

use Ekino\NewRelicBundle\NewRelic\Config;
use Ekino\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use function sprintf;
use Throwable;

class NewRelicProfiler implements ProfilerInterface
{
    private NewRelicInteractorInterface $newRelicInteractor;

    private Config $newRelicConfig;

    public function __construct(NewRelicInteractorInterface $newRelicInteractor, Config $newRelicConfig)
    {
        $this->newRelicInteractor = $newRelicInteractor;
        $this->newRelicConfig = $newRelicConfig;
    }

    public function start(string $name, ?string $kind = null): void
    {
        if (null !== $kind) {
            $transactionName = sprintf('%s_%s', $kind, $name);
        } else {
            $transactionName = $name;
        }

        $this->newRelicInteractor->endTransaction();
        $this->newRelicInteractor->startTransaction($this->newRelicConfig->getName());
        $this->newRelicInteractor->enableBackgroundJob();
        $this->newRelicInteractor->setTransactionName($transactionName);
    }

    public function stop(?Throwable $exception = null): void
    {
        if (null !== $exception) {
            $this->newRelicInteractor->noticeThrowable($exception);
        }

        $this->newRelicInteractor->endTransaction();
    }

    public function stopAndIgnore(): void
    {
        $this->newRelicInteractor->endTransaction(true);
    }
}
