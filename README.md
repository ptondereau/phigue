# Phigue

Layered configuration for PHP, hydrated into a typed object through reflection.

You write one class with typed properties and a few attributes. Phigue reads that class and fills it from defaults, config files, environment variables, and CLI arguments, in that order, with later sources overriding earlier ones. The result is your class, fully typed, not an array you have to guess at.

The idea comes from [facet](https://facet.rs/guide/cli/) and its `figue` crate in Rust: describe the type once, let one source of truth drive the CLI, the env reader, and the file loader.

## 📦 Installation

```bash
composer require ptondereau/phigue
```

Phigue needs PHP 8.3 or newer.

## 🚀 Quick start

Describe your configuration as a class. Native property defaults are the lowest layer, so you rarely need anything else.

```php
use Phigue\Attribute\Named;
use Phigue\Layered;

final class AppConfig
{
    public function __construct(
        #[Named(short: 'H')]
        public string $host = '127.0.0.1',
        #[Named(short: 'p')]
        public int $port = 8080,
        public bool $tls = false,
    ) {}
}

$config = Layered::for(AppConfig::class)
    ->files(['/etc/myapp/config.json', getenv('HOME') . '/.config/myapp.json'])
    ->env('MYAPP')
    ->args(array_slice($argv, 1))
    ->build();

echo $config->host;  // string, never a stray array key
echo $config->port;  // int, coerced from the "8080" a CLI or env hands you
```

`build()` returns an `AppConfig`. Your IDE and static analyzer know its type, because `Layered::for()` carries it through.

## 🧩 Sources and precedence

You register sources in priority order. Each one contributes only the values it actually provides, and a later source overrides an earlier one key by key. Set `port` from a file and `host` from the CLI, and both survive.

| Method | Reads from |
|--------|-----------|
| `values(array $values)` | A plain array, handy for tests or programmatic config |
| `files(array $paths)` | JSON files, in order; a missing file is skipped, not an error |
| `env(string $prefix)` | Environment variables, prefixed and screaming-snake-cased |
| `args(array $argv)` | CLI arguments |
| `source(Source $source)` | Your own source; implement the `Source` interface |

For the class above, `env('MYAPP')` looks for `MYAPP_HOST`, `MYAPP_PORT`, and `MYAPP_TLS`. The CLI accepts `--host`, `-H`, `--port`, `-p`, and the flag `--tls`.

The default order in the quick start is files, then env, then CLI. Pick the order that fits your app; Phigue doesn't hard-code it.

## 🏷️ Attributes

| Attribute | Where | Effect |
|-----------|-------|--------|
| `#[Named(short: 'p', long: 'port')]` | property | Names the `--long` option and an optional `-p` short flag |
| `#[Positional]` | property | Fills from a bare CLI argument by position |
| `#[Counted]` | int property | Counts repeats, so `-vvv` is `3` |
| `#[Env('PORT')]` | property | Binds a specific env var instead of the derived name |
| `#[Flatten]` | nested object | Lifts the child's fields into the parent namespace |
| `#[Subcommand]` | union property | Dispatches to one of several command classes |
| `#[Help('text')]` | property or class | Help text for `help()` |
| `#[Hidden]` | property | Keeps the field out of generated help |
| `#[Secret]` | property | Redacts the default in help output |

Without `#[Named]`, a property still becomes `--kebab-cased` and `PREFIX_SCREAMING_SNAKE`. The attributes override the defaults; they don't switch the behavior on.

## 🪆 Flatten

Mark a nested object with `#[Flatten]` and its fields move up into the parent's CLI and env namespace.

```php
final class FlatConfig
{
    public function __construct(
        #[Flatten]
        public ServerConfig $server = new ServerConfig(),
        public string $name = 'app',
    ) {}
}
```

Now the CLI takes `--host` and `--port` rather than `--server.host`, env reads `MYAPP_HOST` rather than `MYAPP_SERVER_HOST`, and an array source accepts top-level `host` and `port` keys. Phigue groups them back into the nested object when it builds.

## ⌨️ Subcommands

Type a property as a union of command classes and mark it `#[Subcommand]`.

```php
use Phigue\Attribute\Subcommand;

final class Cli
{
    public function __construct(
        #[Subcommand]
        public ServeCommand|MigrateCommand $command,
    ) {}
}

$cli = Layered::for(Cli::class)->args(array_slice($argv, 1))->build();

match (true) {
    $cli->command instanceof ServeCommand   => serve($cli->command),
    $cli->command instanceof MigrateCommand => migrate($cli->command),
};
```

The first bare argument picks the command. `ServeCommand` answers to `serve`, `MigrateCommand` to `migrate` (Phigue strips the `Command` suffix and kebab-cases the rest). Everything after the command name parses into that command's own options and positionals. Global options still work before the command, so `-vv serve --workers 3` sets verbosity on the parent and workers on the command.

## 📖 Generated help

```php
echo Layered::for(AppConfig::class)->help();
```

```
Usage: [options]

Options:
  -H, --host <string>  (default: 127.0.0.1)
  -p, --port <int>  (default: 8080)
      --tls  (default: false)
```

Help is built from the same shape, so the option names, types, and defaults stay in sync with your class. `#[Help]` text appears next to its option, `#[Hidden]` fields drop out, and `#[Secret]` defaults show as `***`.

## ⚠️ Errors

Phigue collects every problem in one pass instead of stopping at the first. `build()` throws a `MappingError` whose `failures` hold the individual reasons, and `report()` formats them.

```php
use Phigue\Exception\MappingError;

try {
    $config = Layered::for(AppConfig::class)->env('MYAPP')->args($argv)->build();
} catch (MappingError $error) {
    fwrite(STDERR, $error->report() . "\n");
    echo Layered::for(AppConfig::class)->help();
    exit(64);
}
```

The exception tree is small:

- `PhigueError`: the interface every Phigue exception implements, so you can catch the whole family
- `MappingError`: a build failed; carries the per-field `failures`
- `MissingRequired`: a field with no default and no nullable type got no value
- `TypeMismatch`: a value couldn't coerce to the property's type
- `UnknownOption`: an unrecognized CLI flag or command

## 🎻 Using with Symfony

Symfony's own config stack (parameters, env var processors, the Secrets vault) covers most needs. Where Phigue helps is giving you one typed object hydrated from layered sources, injectable like any service.

### Compared to Symfony's own config

In a standard Symfony app you reference env values as `%env(int:X)%` strings across YAML and `#[Autowire]` arguments, casting one value at a time:

```php
final class ConnectionFactory
{
    public function __construct(
        #[Autowire('%env(int:APP_POOL_SIZE)%')]
        private int $poolSize,
        #[Autowire('%env(APP_REGION)%')]
        private string $region,
    ) {}
}
```

Phigue makes the class the definition and injects it whole:

```php
final class ConnectionConfig
{
    public function __construct(
        #[Env('APP_POOL_SIZE')]
        public int $poolSize = 10,
        #[Env('APP_REGION')]
        public string $region = 'eu-west-1',
    ) {}
}
```

What you get for it:

- A typed object instead of stringly-typed parameters. `$config->poolSize` is an `int` your IDE and PHPStan track, with no `%env()%` key to typo and no `TreeBuilder` Configuration tree to write for structure.
- Validation in one pass. Phigue collects every coercion failure into a single `MappingError` with field-level messages, where env processors fail one value at a time.
- The CLI as a config layer. Defaults, file, env, and CLI merge per key in the order you choose, so `--port 9000` overrides the env value without dropping a host a file set. Symfony's chain covers `.env` and real env but leaves the CLI to you.
- A class that runs anywhere. `ConnectionConfig` doesn't touch the container, so the same type works in a worker, a binary, or a test without booting the kernel.

Keep Symfony's own config for ordinary web requests, where the compiled container caches everything and the stack already does the job. Reach for Phigue when you want config as one typed object, in console tools, workers, and standalone services. The reflection cost under PHP-FPM is the thing to weigh (see the caveat below).

### As a service

Define the config class, then build it in a factory:

```php
namespace App\Config;

use Phigue\Attribute\Env;

final class AppConfig
{
    public function __construct(
        #[Env('DATABASE_POOL_SIZE')]
        public int $poolSize = 10,
        public string $region = 'eu-west-1',
        public bool $tls = true,
    ) {}
}
```

```php
namespace App\Config;

use Phigue\Layered;

final class AppConfigFactory
{
    public function build(): AppConfig
    {
        return Layered::for(AppConfig::class)
            ->files([__DIR__ . '/../../config/app.json'])
            ->env('APP', $_SERVER)
            ->build();
    }
}
```

```yaml
# config/services.yaml
services:
    App\Config\AppConfig:
        factory: ['@App\Config\AppConfigFactory', 'build']
```

The factory is autowired from `src/` already, so the one definition above is enough. Inject `AppConfig` anywhere and you get a typed object instead of `%env(...)%` strings.

### Dotenv

Let Symfony's Dotenv do the loading and have Phigue read the result. Symfony's `bootEnv()` loads `.env`, `.env.local`, and the per-environment files into `$_SERVER` and `$_ENV`, with real system variables taking precedence over `.env` values. By the time your factory runs, `$_SERVER` holds the resolved set, so pass it to `env()`:

```php
->env('MYAPP', $_SERVER)
```

Use `$_SERVER` rather than Phigue's default `getenv()`. The Symfony [docs](https://symfony.com/doc/current/configuration.html) treat `$_SERVER` and `$_ENV` as equivalent, but Dotenv skips `putenv()` by default, so `getenv()` won't see your `.env` values.

Pick a prefix that won't clash with Symfony's own variables. `$_SERVER` carries `APP_ENV`, `APP_SECRET`, `DATABASE_URL`, and the server's `HTTP_*` entries. Phigue only reads keys matching `PREFIX_FIELD`, so the rest is ignored, but a prefix of `APP` with a field named `env` would pick up `APP_ENV`. A distinct prefix like `MYAPP` keeps them apart.

Phigue coerces each value to the property's type, so you skip the `%env(int:...)%` processor for the typed object.

### Console commands

Symfony Console parses argv and renders `--help` itself, so let it own the command line and use Phigue for the env and file layers. Feeding `$argv` into Phigue's `args()` inside a command parses the input twice and fights Symfony's own input definition.

When a command just needs the app config, inject the typed service:

```php
namespace App\Command;

use App\Config\AppConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:serve')]
final class ServeCommand extends Command
{
    public function __construct(private readonly AppConfig $config)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Binding to {$this->config->host}:{$this->config->port}");

        return Command::SUCCESS;
    }
}
```

When you want a CLI flag to override env and file, define the option Symfony-side and fold only what the user passed into Phigue as the top layer:

```php
namespace App\Command;

use App\Config\ServerConfig;
use Phigue\Layered;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:serve')]
final class ServeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_REQUIRED)
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $overrides = array_filter(
            ['host' => $input->getOption('host'), 'port' => $input->getOption('port')],
            static fn (mixed $value): bool => $value !== null,
        );

        $config = Layered::for(ServerConfig::class)
            ->files(['/etc/myapp.json'])
            ->env('MYAPP', $_SERVER)
            ->values($overrides)
            ->build();

        $output->writeln("Binding to {$config->host}:{$config->port}");

        return Command::SUCCESS;
    }
}
```

The options default to `null`, and `array_filter` drops the ones nobody passed so they don't overwrite env or file. Phigue coerces `--port` to `int` on build, and you keep the full chain: defaults, then file, then env, then CLI.

Skip Phigue's subcommands, counted flags, and `help()` in this setting. They overlap with Symfony's command classes and help system. Phigue's CLI parser is for standalone binaries, not commands already inside a `Console\Application`.

One caveat: the container is cached, but this factory runs reflection on every request under PHP-FPM. For a few config classes that cost is small, and for CLI tools and workers where the process starts once it's a non-issue. When it matters, hand `Layered` a cache (see below) so Phigue reads the reflection plan once and reuses it.

## ⚡ Caching the reflection plan

`Layered` introspects your config class every time you call `build()` or `help()`. Pass a cache and Phigue computes that plan once, then reuses it on later runs. Phigue accepts either a PSR-6 pool or a PSR-16 cache; give it whichever your app already has.

```php
use Phigue\Layered;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$config = Layered::for(ServerConfig::class)
    ->cache(new FilesystemAdapter())   // PSR-6 pool, or any Psr\SimpleCache\CacheInterface
    ->files(['/etc/myapp.json'])
    ->env('MYAPP', $_SERVER)
    ->build();
```

Phigue derives the cache key from the class name and the source file's modification time, so editing your config class invalidates the old plan.

The cache is best-effort. If the backend fails on a read or a write, Phigue falls back to fresh reflection instead of raising an error, so a broken cache never breaks config loading. Pass a PSR-3 logger as the second argument to `cache()` to record those failures:

```php
Layered::for(AppConfig::class)
    ->cache(new FilesystemAdapter(), $logger)   // $logger is a Psr\Log\LoggerInterface
    ->build();
```

Install whichever contract you use:

```bash
composer require psr/cache         # PSR-6
composer require psr/simple-cache  # PSR-16
```

Treat the cache backend as trusted. Your PSR adapter deserializes stored values, usually through PHP's `unserialize()`, before Phigue ever sees them, so anyone who can write to the store can run code in your process. That's the standard PHP cache trust model, not specific to Phigue, and Phigue's `instanceof` check guards against stale or unexpected entries, not hostile ones. Keep Redis or Memcached behind authentication on a private network, and don't point `cache()` at a store untrusted parties can write to.

## 🛠️ Development

```bash
composer install
composer test       # phpunit
composer lint       # mago lint
composer analyse    # mago analyze
composer format     # mago format
```

The suite is test-driven and covers every source, precedence, coercion, flatten, subcommands, and help.

## License

MIT
