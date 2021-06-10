<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\EventListener;

use Sourceability\Instrumentation\Profiler\ProfilerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

class MessengerProfilerListener implements EventSubscriberInterface
{
    private ProfilerInterface $profiler;

    public function __construct(ProfilerInterface $profiler)
    {
        $this->profiler = $profiler;
    }

    public static function getSubscribedEvents()
    {
        return [
            WorkerMessageReceivedEvent::class => 'onInvoke',
            WorkerMessageHandledEvent::class => 'onAcknowledge',
            WorkerMessageFailedEvent::class => 'onReject',
            WorkerStartedEvent::class => 'onPing',
        ];
    }

    public function onInvoke(WorkerMessageReceivedEvent $event): void
    {
        $transactionName = \get_class($event->getEnvelope()->getMessage());

        $this->profiler->stop();
        $this->profiler->start($transactionName, 'messenger');
    }

    public function onAcknowledge(WorkerMessageHandledEvent $event): void
    {
        $this->profiler->stop();
    }

    public function onReject(WorkerMessageFailedEvent $event): void
    {
        $this->profiler->stop($event->getThrowable());
    }

    public function onPing(WorkerStartedEvent $event): void
    {
        $this->profiler->stopAndIgnore();
    }
}
