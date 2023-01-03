<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\Messenger;

use Sourceability\Instrumentation\Profiler\ProfilerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

class ProfilerMiddleware implements MiddlewareInterface
{
    private ProfilerInterface $profiler;

    private ?RequestStack $requestStack;

    private bool $started = false;

    public function __construct(ProfilerInterface $profiler, ?RequestStack $requestStack)
    {
        $this->profiler = $profiler;
        $this->requestStack = $requestStack;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $transactionName = \get_class($envelope->getMessage());

        $skip = false;
        if (null !== $this->requestStack
            && null !== (method_exists(
                $this->requestStack,
                'getMainRequest'
            ) ? $this->requestStack->getMainRequest() : $this->requestStack->getMasterRequest())
        ) {
            $skip = true;
        }
        if (null === $envelope->last(ReceivedStamp::class)) {
            $skip = true;
        }

        if ($skip) {
            // Do not profile if we are within a web context
            return $stack->next()
                ->handle($envelope, $stack)
                ;
        }

        $shouldStop = false;
        if (!$this->started) {
            $this->profiler->start($transactionName, 'messenger');

            $this->started = true;
            $shouldStop = true;
        }

        try {
            return $stack->next()
                ->handle($envelope, $stack)
            ;
        } catch (HandlerFailedException $exception) {
            if ($shouldStop) {
                $nestedExceptions = $exception->getNestedExceptions();
                $firstNestedException = reset($nestedExceptions);

                $this->profiler->stop(false !== $firstNestedException ? $firstNestedException : $exception);
                $this->started = false;

                $shouldStop = false;
            }

            throw $exception;
        } finally {
            if ($shouldStop) {
                $this->profiler->stop();
                $this->started = false;
            }
        }
    }
}
