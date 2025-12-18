<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Bootstrap\Container;

use Fundrik\WordPress\Components\Campaigns\Application\Ports\In\CampaignCommandServicePort;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\In\CampaignQueryServicePort;
use Fundrik\WordPress\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\WordPress\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\WordPress\Infrastructure\Database\DatabaseInterface;
use Fundrik\WordPress\Infrastructure\EventDispatcher\EventDispatcher;
use Fundrik\WordPress\Infrastructure\EventDispatcher\EventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\EventDispatcher\EventListenerRegistrar;
use Fundrik\WordPress\Infrastructure\Integration\Blocks\BlocksPathsProvider;
use Fundrik\WordPress\Infrastructure\Integration\Blocks\BlocksPathsProviderInterface;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookBridgeRegistrar;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookBridgeRegistry;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeBlockTemplateReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSlugReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSpecificBlockReader;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContext;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Infrastructure\Integration\WordPressOptionsStorage;
use Fundrik\WordPress\Infrastructure\Integration\WpdbDatabase;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRegistry;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\StorageInterface;
use Fundrik\WordPress\Kernel\Plugin;
use Fundrik\WordPress\Kernel\Ports\Out\EventListenerRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\Out\HookBridgeRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\Out\MigrationRunnerPort;
use Illuminate\Contracts\Events\Dispatcher as LaravelEventsDispatcherInterface;
use Illuminate\Events\Dispatcher as LaravelEventsDispatcher;

/**
 * Registers all container bindings.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class ContainerBindingsRegistrar implements ContainerBindingsRegistrarInterface {

	/**
	 * Registers all bindings into the given container.
	 *
	 * @since 1.0.0
	 *
	 * @param ContainerInterface $container Receives the service bindings for resolution at runtime.
	 */
	public function register_bindings_into_container( ContainerInterface $container ): void {

		foreach ( $this->get_singletons() as $abstract => $concrete ) {

			if ( is_int( $abstract ) ) {
				$abstract = $concrete;
			}

			$container->singleton( $abstract, $concrete );
		}

		foreach ( $this->get_bindings() as $abstract => $concrete ) {

			$container->bind( $abstract, $concrete );
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns the list of abstract-to-concrete singleton bindings.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string|int, string> The array of [abstract => concrete] bindings.
	 *
	 * @phpstan-return array<class-string|int, class-string>
	 */
	private function get_singletons(): array {

		// phpcs:disable SlevomatCodingStandard.Arrays.DisallowPartiallyKeyed.DisallowedPartiallyKeyed
		return [

			Plugin::class,

			// Campaigns Application.
			CampaignCommandServicePort::class => CampaignCommandService::class,
			CampaignQueryServicePort::class => CampaignQueryService::class,

			// Events Dispatcher.
			LaravelEventsDispatcherInterface::class => LaravelEventsDispatcher::class,
			EventDispatcherInterface::class => EventDispatcher::class,
			EventListenerRegistrarPort::class => EventListenerRegistrar::class,
			HookBridgeRegistrarPort::class => HookBridgeRegistrar::class,
			HookBridgeRegistry::class,

			// Migrations.
			MigrationRegistry::class,
			MigrationRunnerPort::class => MigrationRunner::class,

			// Storage.
			DatabaseInterface::class => WpdbDatabase::class,
			StorageInterface::class => WordPressOptionsStorage::class,

			// Blocks.
			BlocksPathsProviderInterface::class => BlocksPathsProvider::class,

			// Post type attribute readers.
			PostTypeBlockTemplateReader::class,
			PostTypeIdReader::class,
			PostTypeMetaFieldReader::class,
			PostTypeSlugReader::class,
			PostTypeSpecificBlockReader::class,

			// Context.
			WordPressContextInterface::class => WordPressContext::class,
		];
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
	private function get_bindings(): array {

		return [];
	}
}
