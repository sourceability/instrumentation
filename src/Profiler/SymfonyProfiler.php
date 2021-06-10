<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\Profiler;

use function microtime;
use function sprintf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Throwable;

class SymfonyProfiler implements ProfilerInterface
{
    private ?Profiler $profiler;

    private ?Stopwatch $stopwatch;

    private ?StopwatchEvent $mainEvent = null;

    private ?Request $request = null;

    public function __construct(?Profiler $profiler, ?Stopwatch $stopwatch)
    {
        $this->profiler = $profiler;
        $this->stopwatch = $stopwatch;
    }

    public function start(string $name, ?string $kind = null): void
    {
        if (null === $this->profiler
            || null === $this->stopwatch
        ) {
            return;
        }

        if (null === $kind) {
            $kind = 'custom';
        }

        $uri = sprintf('http://%s/%s', $kind, $name);

        $this->request = Request::create($uri, $kind);
        $this->request->server->set('REQUEST_TIME_FLOAT', microtime(true));

        $this->profiler->reset();
        $this->stopwatch->openSection();
        $this->mainEvent = $this->stopwatch->start($kind, 'section');
    }

    public function stop(?Throwable $exception = null): void
    {
        if (null === $this->mainEvent
            || null === $this->request
            || null === $this->profiler
            || null === $this->stopwatch
        ) {
            return;
        }

        $this->mainEvent->ensureStopped();
        $this->mainEvent = null; // make sure "nested" profiles aren't saved twice, for example messenger within command

        $responseStatus = null === $exception ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR;

        $response = new Response('', $responseStatus);

        $profile = $this->profiler->collect($this->request, $response, $exception);

        if (null === $profile) {
            return;
        }

        if ($this->stopwatch->isStarted('__section__')) {
            $this->stopwatch->stopSection($profile->getToken());
        }

        $this->profiler->saveProfile($profile);
    }

    public function stopAndIgnore(): void
    {
        $this->request = null;

        if (null !== $this->mainEvent) {
            $this->mainEvent->ensureStopped();
        }

        $this->mainEvent = null;
    }
}
