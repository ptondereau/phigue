<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture\Console;

use Phigue\Layered;
use Phigue\Tests\Fixture\ServerConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:serve')]
final class ServeCommand extends Command
{
    /** @param array<string, string> $env */
    public function __construct(
        private readonly array $env = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('host', null, InputOption::VALUE_REQUIRED)->addOption(
            'port',
            'p',
            InputOption::VALUE_REQUIRED,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $overrides = array_filter(
            ['host' => $input->getOption('host'), 'port' => $input->getOption('port')],
            static fn(mixed $value): bool => $value !== null,
        );

        $config = Layered::for(ServerConfig::class)->env('APP', $this->env)->values($overrides)->build();

        $output->writeln(sprintf('%s:%d tls=%s', $config->host, $config->port, $config->tls ? 'on' : 'off'));

        return Command::SUCCESS;
    }
}
