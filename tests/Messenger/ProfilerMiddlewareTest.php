<?php

namespace Sourceability\Instrumentation\Test\Messenger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sourceability\Instrumentation\Messenger\ProfilerMiddleware;
use Sourceability\Instrumentation\Profiler\ProfilerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * @covers ProfilerMiddleware
 */
class ProfilerMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    public function testHandlesNesting(): void
    {
        // Messenger supports nesting message handling, which \Sourceability\Instrumentation\Profiler\SymfonyProfiler
        // is unhappy about

        $profiler = $this->prophesize(ProfilerInterface::class);
        $profiler->start(Argument::cetera())->shouldBeCalledOnce();
        $profiler->stop(Argument::cetera())->shouldBeCalledOnce();

        $middleware = new ProfilerMiddleware($profiler->reveal(), null);

        $envelope = new Envelope(new \stdClass(), [new ReceivedStamp('foo')]);

        $nestedStack = $this->prophesize(StackInterface::class);
        $nestedStack->next()->willReturn(new class() implements MiddlewareInterface {
            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                // noop
                return $envelope;
            }
        });

        $stack = $this->prophesize(StackInterface::class);
        $stack->next()->willReturn(new class($middleware, $nestedStack->reveal()) implements MiddlewareInterface {
            private ProfilerMiddleware $middleware;
            private StackInterface $stack;

            public function __construct(ProfilerMiddleware $middleware, StackInterface $stack)
            {
                $this->middleware = $middleware;
                $this->stack = $stack;
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                // this might be our handler, that ends up having other handlers involved
                // ... triggering a nested \Sourceability\Instrumentation\Messenger\ProfilerMiddleware::handle

                return $this->middleware->handle($envelope, $this->stack);
            }
        });

        $middleware->handle(
            $envelope,
            $stack->reveal()
        );
    }
}
