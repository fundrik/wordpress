<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Container;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\WordPress\Infrastructure\DatabaseInterface;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository;
use Fundrik\WordPress\Infrastructure\StorageInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistrar;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContext;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Integration\WordPressOptionsStorage;
use Fundrik\WordPress\Integration\WpdbDatabase;
use Fundrik\WordPress\Kernel\Ports\HookDispatcherRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Illuminate\Contracts\Events\Dispatcher as LaravelEventsDispatcherInterface;
use Illuminate\Events\Dispatcher as LaravelEventsDispatcher;

/**
 * Provides the list of container bindings.
 *
 * @since 1.0.0
 *
 * @internal
 */
class ContainerBindingsRegistry {

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns the list of abstract-to-concrete singleton bindings.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string|int, string> The list of singleton bindings keyed by the abstract type.
	 *
	 * @phpstan-return array<class-string|int, class-string>
	 */
	public function get_singletons(): array {

		// phpcs:disable SlevomatCodingStandard.Arrays.DisallowPartiallyKeyed.DisallowedPartiallyKeyed
		return [
			LaravelEventsDispatcherInterface::class => LaravelEventsDispatcher::class,

			MigrationRunnerPort::class => MigrationRunner::class,

			DatabaseInterface::class => WpdbDatabase::class,
			StorageInterface::class => WordPressOptionsStorage::class,
			CampaignRepositoryPort::class => CampaignRepository::class,

			HookDispatcherRegistrarPort::class => HookDispatcherRegistrar::class,
			WordPressContextInterface::class => WordPressContext::class,
		];
		// phpcs:enable
	}
	// phpcs:enable

	/**
	 * Returns the list of abstract-to-concrete transient bindings.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> The array of [abstract => concrete] bindings.
	 *
	 * @phpstan-return array<class-string, class-string>
	 */
	public function get_bindings(): array {

		return [];
	}
}
