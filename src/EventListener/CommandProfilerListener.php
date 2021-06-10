<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\EventListener;

use Sourceability\Instrumentation\Profiler\ProfilerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandProfilerListener implements EventSubscriberInterface
{
    private ProfilerInterface $profiler;

    public function __construct(ProfilerInterface $profiler)
    {
        $this->profiler = $profiler;
    }

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'onCommand',
            ConsoleEvents::TERMINATE => 'onTerminate',
            ConsoleEvents::ERROR => 'onError',
        ];
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        $commandName = 'unknown';
        if (null !== $event->getCommand()
            && null !== $event->getCommand()
                ->getName()
        ) {
            $commandName = $event->getCommand()
                ->getName()
            ;
        }

        $this->profiler->start($commandName, 'command');
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        $this->profiler->stop();
    }

    public function onError(ConsoleErrorEvent $event): void
    {
        $this->profiler->stop($event->getError());
    }
}
