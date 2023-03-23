# ExamplePlugin

This repository contains a basic example of a modern PocketMine-MP plugin, and a handful of the API features.

## PHPStan analysis
This repository shows an example setup for standalone local analysis of a plugin using [PHPStan](https://phpstan.org).

It uses [Composer](https://getcomposer.org) for autoloading, allowing you to install PHPStan extensions such as [phpstan-strict-rules](https://github.com/phpstan/phpstan-strict-rules). The configuration for this can be seen in [`phpstan/composer.json`](/phpstan/composer.json).

### Setting up PHPStan
Assuming you have Composer and a compatible PHP binary available in your PATH, run:
```
cd phpstan
composer install
```

Then you can run PHPStan exactly as you would with any other project:
```
vendor/bin/phpstan analyze
```

### Updating the dependencies
```
composer update
```

### GitHub Actions
You can find a workflow suitable for analysing most plugins using this system in [`.github/workflows/main.yml`](/.github/workflows/main.yml).
