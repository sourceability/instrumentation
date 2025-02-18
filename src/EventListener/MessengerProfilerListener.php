<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\EventListener;

use Sourceability\Instrumentation\Profiler\ProfilerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\WrappedExceptionsInterface;

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
            WorkerMessageReceivedEvent::class => [['onInvoke', 2048]],
            WorkerMessageHandledEvent::class => [['onAcknowledge', -2048]],
            WorkerMessageFailedEvent::class => [['onReject', -2048]],
            WorkerStartedEvent::class => [['onPing', -2048]],
        ];
    }

    public function onInvoke(WorkerMessageReceivedEvent $event): void
    {
        $transactionName = str_replace('\\', '/', \get_class($event->getEnvelope()->getMessage()));

        $this->profiler->stop();
        $this->profiler->start($transactionName, 'messenger');
    }

    public function onAcknowledge(WorkerMessageHandledEvent $event): void
    {
        $this->profiler->stop();
    }

    public function onReject(WorkerMessageFailedEvent $event): void
    {
        $throwable = $event->getThrowable();

        $nestedExceptions = [];

        if (interface_exists(WrappedExceptionsInterface::class)
            && $throwable instanceof WrappedExceptionsInterface
        ) {
            $nestedExceptions = $throwable->getWrappedExceptions();
        } elseif ($throwable instanceof HandlerFailedException
            && method_exists($throwable, 'getNestedExceptions')
        ) {
            $nestedExceptions = $throwable->getNestedExceptions();
        }

        $firstNestedException = reset($nestedExceptions);

        $throwable = false !== $firstNestedException ? $firstNestedException : $throwable;

        $this->profiler->stop($throwable);
    }

    public function onPing(WorkerStartedEvent $event): void
    {
        $this->profiler->stopAndIgnore();
    }
}
