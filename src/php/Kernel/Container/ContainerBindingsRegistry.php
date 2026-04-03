<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Container;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventBus;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventPublisherPort;
use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationDefinitions;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignRepository;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository\DonationRepository;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Fundrik\WordPress\Integration\AdminPages\AdminPageRegistrar;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupRegistrar;
use Fundrik\WordPress\Integration\Boot\BootUnitDefinitions;
use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitRunner;
use Fundrik\WordPress\Integration\Boot\Units\FilterAllowedBlocksByPostTypeBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterPostTypesBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterRestApiRoutesBootUnit;
use Fundrik\WordPress\Integration\Database\WpdbDatabase;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherDefinitions;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistrar;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigDefinitions;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\RestRouteInterface;
use Fundrik\WordPress\Integration\WordPressActionApplicationEventPublisher;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContext;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Integration\Storage\WordPressOptionsStorage;
use Fundrik\WordPress\Kernel\Ports\BootUnitRunnerPort;
use Fundrik\WordPress\Kernel\Ports\HookDispatcherRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Provides the list of container bindings.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class ContainerBindingsRegistry {

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns the list of abstract-to-concrete singleton bindings.
	 *
	 * @since 1.0.0
	 *
	 * @return array<class-string|int, class-string> The list of singleton bindings keyed by the abstract type.
	 */
	public function get_singletons(): array {

		// phpcs:disable SlevomatCodingStandard.Arrays.DisallowPartiallyKeyed.DisallowedPartiallyKeyed
		return [
			// TODO: Implement Monolog logger.
			LoggerInterface::class => NullLogger::class,

			MigrationRunnerPort::class => MigrationRunner::class,

			DatabasePort::class => WpdbDatabase::class,
			StoragePort::class => WordPressOptionsStorage::class,

			CampaignRepositoryPort::class => CampaignRepository::class,
			DonationRepositoryPort::class => DonationRepository::class,

			ApplicationEventBusPort::class => ApplicationEventBus::class,
			ApplicationEventPublisherPort::class => WordPressActionApplicationEventPublisher::class,

			HookDispatcherRegistrarPort::class => HookDispatcherRegistrar::class,
			BootUnitRunnerPort::class => BootUnitRunner::class,
			WordPressContextInterface::class => WordPressContext::class,
		] + HookDispatcherDefinitions::classes();
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

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns the list of contextual binding definitions.
	 *
	 * @since 1.0.0
	 *
	 * @return list<ContextualBindingDefinition> The contextual binding definitions.
	 */
	public function get_contextual_bindings(): array {

		return [
			new ContextualBindingDefinition(
				BootUnitRunner::class,
				BootUnitInterface::class,
				BootUnitDefinitions::classes(),
			),
			new ContextualBindingDefinition(
				HookDispatcherRegistrar::class,
				HookDispatcherInterface::class,
				HookDispatcherDefinitions::classes(),
			),
			new ContextualBindingDefinition(
				AdminPageRegistrar::class,
				AdminPageInterface::class,
				AdminPageDefinitions::classes(),
			),
			new ContextualBindingDefinition(
				AdminSettingsReader::class,
				AdminSettingsGroupInterface::class,
				AdminSettingsGroupDefinitions::classes(),
			),
			new ContextualBindingDefinition(
				AdminSettingsGroupRegistrar::class,
				AdminSettingsGroupInterface::class,
				AdminSettingsGroupDefinitions::classes(),
			),
			new ContextualBindingDefinition(
				RegisterRestApiRoutesBootUnit::class,
				RestRouteInterface::class,
				RestRouteDefinitions::classes(),
			),
			new ContextualBindingDefinition(
				RegisterPostTypesBootUnit::class,
				PostTypeConfigInterface::class,
				PostTypeConfigDefinitions::classes(),
			),
			new ContextualBindingDefinition(
				FilterAllowedBlocksByPostTypeBootUnit::class,
				PostTypeConfigInterface::class,
				PostTypeConfigDefinitions::classes(),
			),
			new ContextualBindingDefinition(
				MigrationRunner::class,
				AbstractMigration::class,
				MigrationDefinitions::classes(),
			),
		];
	}
	// phpcs:enable
}
