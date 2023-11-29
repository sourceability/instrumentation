<?php

declare(strict_types=1);

namespace Sourceability\Instrumentation\Profiler;

use function dd_trace_env_config;
use DDTrace\Contracts\Scope;
use DDTrace\GlobalTracer;
use DDTrace\Tag;
use DDTrace\Tracer;
use DDTrace\Type;
use function ddtrace_config_app_name;
use function ddtrace_config_trace_enabled;
use Psr\Log\LoggerInterface;
use function sprintf;
use Throwable;

class DatadogProfiler implements ProfilerInterface
{
    private ?Scope $scope = null;

    private LoggerInterface $logger;

    private float $sampleRate = 1.0;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $sampleRateString = getenv('DD_TRACE_SAMPLE_RATE');
        if(is_numeric($sampleRateString)) {
            $sampleRate = floatval($sampleRateString);
            $this->sampleRate = $sampleRate;
        }
    }

    public function start(string $name, ?string $kind = null): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if($this->rateLimited()) {
            return;
        }

        if (dd_trace_env_config('DD_TRACE_GENERATE_ROOT_SPAN')) {
            $this->logger->error(
                sprintf('You should set DD_TRACE_GENERATE_ROOT_SPAN=0 when using %s.', self::class)
            );
        }

        if (!dd_trace_env_config('DD_TRACE_AUTO_FLUSH_ENABLED')) {
            $this->logger->error(
                sprintf('You should set DD_TRACE_AUTO_FLUSH_ENABLED=1 when using %s.', self::class)
            );
        }

        $kind ??= 'custom';
        $operationName = sprintf('symfony.%s', $kind);

        $this->scope = GlobalTracer::get()->startRootSpan($operationName, [
            'ignore_active_span' => true,
            'finish_span_on_close' => true,
        ]);

        $this->scope->getSpan()
            ->setResource($name)
        ;
        $this->scope->getSpan()
            ->setTag(Tag::SPAN_TYPE, Type::CLI)
        ;
        $this->scope->getSpan()
            ->setTag(Tag::SERVICE_NAME, ddtrace_config_app_name($operationName))
        ;
    }

    public function stop(?Throwable $exception = null): void
    {
        if (null === $this->scope) {
            return;
        }

        if (null !== $exception) {
            $this->scope->getSpan()
                ->setError($exception)
            ;
        }

        $this->scope->close();
        $this->scope = null;
    }

    public function stopAndIgnore(): void
    {
        if (null === $this->scope) {
            return;
        }

        GlobalTracer::set(new Tracer());
    }

    private function rateLimited(): bool
    {
        $randomFloat = mt_rand() / mt_getrandmax(); // between 0 and 1
        return $randomFloat > $this->sampleRate;
    }

    private function isEnabled(): bool
    {
        return \extension_loaded('ddtrace')
            && ddtrace_config_trace_enabled()
        ;
    }
}
