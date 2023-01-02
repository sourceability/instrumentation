<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\Profiler;

use function microtime;
use function sprintf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Throwable;

class SymfonyProfiler implements ProfilerInterface
{
    private ?Profiler $profiler;

    private ?Stopwatch $stopwatch;

    private ?RequestStack $requestStack;

    private ?StopwatchEvent $mainEvent = null;

    private ?Request $request = null;

    public function __construct(?Profiler $profiler, ?Stopwatch $stopwatch, ?RequestStack $requestStack)
    {
        $this->profiler = $profiler;
        $this->stopwatch = $stopwatch;
        $this->requestStack = $requestStack;
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

        $requestStackToCleanUp = null;
        if (null !== $this->requestStack
            && null === (method_exists(
                $this->requestStack,
                'getMainRequest'
            ) ? $this->requestStack->getMainRequest() : $this->requestStack->getMasterRequest())
        ) {
            // Fixes: Notice: Trying to get property 'attributes' of non-object
            // See https://github.com/symfony/symfony/blob/e34cd7dd2c6d0b30d24cad443b8f964daa841d71/src/Symfony/Component/HttpKernel/DataCollector/RequestDataCollector.php#L109

            $this->requestStack->push($this->request);
            $requestStackToCleanUp = $this->requestStack;
        }

        $profile = $this->profiler->collect($this->request, $response, $exception);

        if (null !== $profile) {
            if ($this->stopwatch->isStarted('__section__')) {
                $this->stopwatch->stopSection($profile->getToken());
            }

            $this->profiler->saveProfile($profile);
        }

        if (null !== $requestStackToCleanUp) {
            $poppedRequest = $requestStackToCleanUp->pop();

            \assert($this->request === $poppedRequest);
        }
        $this->request = null;
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
