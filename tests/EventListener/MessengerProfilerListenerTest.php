<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\Test\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sourceability\Instrumentation\EventListener\MessengerProfilerListener;
use Sourceability\Instrumentation\Profiler\ProfilerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * @covers \Sourceability\Instrumentation\EventListener\MessengerProfilerListener
 *
 * @internal
 */
final class MessengerProfilerListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testOnReject(): void
    {
        $profiler = $this->prophesize(ProfilerInterface::class);

        $listener = new MessengerProfilerListener($profiler->reveal());

        $error = new \Exception('not good');

        $profiler->stop($error)
            ->shouldBeCalled()
        ;

        $event = new WorkerMessageFailedEvent(new Envelope(new \stdClass()), 'receiver', $error);
        $listener->onReject($event);
    }

    public function testOnRejectUnwrapsHandlerFailedException(): void
    {
        $profiler = $this->prophesize(ProfilerInterface::class);

        $listener = new MessengerProfilerListener($profiler->reveal());

        $envelope = new Envelope(new \stdClass());

        $error = new HandlerFailedException($envelope, [$handlerError = new \Exception('not good')]);

        $profiler->stop($handlerError)
            ->shouldBeCalled()
        ;

        $event = new WorkerMessageFailedEvent($envelope, 'receiver', $error);
        $listener->onReject($event);
    }
}
