<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Container;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignUseCase;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign\SaveCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign\SaveCampaignUseCase;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\WordPress\Infrastructure\DatabasePort;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventBus;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventPublisherPort;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository;
use Fundrik\WordPress\Infrastructure\StoragePort;
use Fundrik\WordPress\Integration\Boot\BootUnitRunner;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistrar;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistry;
use Fundrik\WordPress\Integration\WordPressActionApplicationEventPublisher;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContext;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Integration\WordPressOptionsStorage;
use Fundrik\WordPress\Integration\WpdbDatabase;
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
class ContainerBindingsRegistry {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.Commenting.DocCommentSpacing.IncorrectLinesCountBetweenDifferentAnnotationsTypes
	 * @param HookDispatcherRegistry $hook_dispatcher_registry Provides the declared hook dispatcher classes for container registration.
	 */
	public function __construct(
		private HookDispatcherRegistry $hook_dispatcher_registry,
	) {}

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
			// TODO: Implement Monolog logger.
			LoggerInterface::class => NullLogger::class,

			MigrationRunnerPort::class => MigrationRunner::class,

			DatabasePort::class => WpdbDatabase::class,
			StoragePort::class => WordPressOptionsStorage::class,
			CampaignRepositoryPort::class => CampaignRepository::class,
			ApplicationEventBusPort::class => ApplicationEventBus::class,
			ApplicationEventPublisherPort::class => WordPressActionApplicationEventPublisher::class,
			SaveCampaignUseCase::class => SaveCampaignHandler::class,
			DeleteCampaignUseCase::class => DeleteCampaignHandler::class,

			HookDispatcherRegistrarPort::class => HookDispatcherRegistrar::class,
			BootUnitRunnerPort::class => BootUnitRunner::class,
			WordPressContextInterface::class => WordPressContext::class,
		] + $this->hook_dispatcher_registry->get_dispatcher_classes();
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
