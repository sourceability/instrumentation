# sourceability/instrumentation-bundle

This bundle provides a simple interface to start and stop instrumenting code with APMs.

Symfony commands and messenger workers have built in integrations, which is convenient because most
APMs usually don't support profiling workers out of the box.

Bundle configuration reference:
```yaml
# Default configuration for extension with alias: "sourceability_instrumentation"
sourceability_instrumentation:
    profilers:

        # See https://support.tideways.com/documentation/features/application-monitoring/application-performance-overview.html
        tideways:
            enabled:              false

        # See https://docs.newrelic.com/docs/agents/php-agent/getting-started/introduction-new-relic-php/
        # This requires https://github.com/ekino/EkinoNewRelicBundle
        newrelic:
            enabled:              false

        # See https://docs.datadoghq.com/tracing/setup_overview/setup/php/
        datadog:
            enabled:              false

        # This "hacks" the symfony web profiler to create profiles in non web contexts like workers, commands.
        # This is really useful for development along with https://github.com/sourceability/console-toolbar-bundle
        symfony:
            enabled:              false
    listeners:

        # Automatically instrument commands
        command:
            enabled:              false

        # Automatically instrument messenger workers
        messenger:
            enabled:              false
```

## Instrumenting a long running command

```php
<?php

namespace App\Command;

use Sourceability\InstrumentationBundle\Profiler\ProfilerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCommand extends Command
{
    /**
     * @var ProfilerInterface
     */
    private $profiler;

    public function __construct(ProfilerInterface $profiler)
    {
        parent::__construct();

        $this->profiler = $profiler;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->profiler->stop();

        $pager = new Pagerfanta(...);

        foreach ($pager as $pageResults) {
            $this->profiler->start('index_batch');

            $this->indexer->index($pageResults);

            $this->profiler->stop();
        };

        return 0;
    }
}
```
