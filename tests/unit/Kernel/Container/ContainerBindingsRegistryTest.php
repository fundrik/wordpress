<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Kernel\Container;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventBus;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventListenerInterface;
use Fundrik\WordPress\Infrastructure\EventBus\CampaignMetricsProjectionApplicationEventListener;
use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationDefinitions;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository\CampaignReadRepository;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignRepository;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository\DonationRepository;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Fundrik\WordPress\Integration\AdminPages\AdminPageRegistrar;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupRegistrar;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
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
use Fundrik\WordPress\Integration\Storage\WordPressOptionsStorage;
use Fundrik\WordPress\Integration\WordPressActionApplicationEventListener;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntime;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntimeInterface;
use Fundrik\WordPress\Kernel\Container\ContainerBindingsRegistry;
use Fundrik\WordPress\Kernel\Container\ContextualBindingDefinition;
use Fundrik\WordPress\Kernel\Ports\BootUnitRunnerPort;
use Fundrik\WordPress\Kernel\Ports\HookDispatcherRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

#[CoversClass( ContainerBindingsRegistry::class )]
final class ContainerBindingsRegistryTest extends FundrikTestCase {

	private ContainerBindingsRegistry $registry;

	protected function setUp(): void {

		parent::setUp();

		$this->registry = new ContainerBindingsRegistry();
	}

	#[Test]
	public function it_exposes_expected_singletons(): void {

		$this->assertSame(
			self::expected_singletons_map(),
			$this->registry->get_singletons(),
		);
	}

	#[Test]
	public function it_exposes_expected_transient_bindings(): void {

		$this->assertSame(
			[],
			$this->registry->get_bindings(),
		);
	}

	#[Test]
	public function it_exposes_expected_contextual_bindings(): void {

		$this->assertSame(
			self::expected_contextual_bindings(),
			array_map(
				static fn ( ContextualBindingDefinition $definition ): array => [
					'consumer' => $definition->consumer,
					'dependency' => $definition->dependency,
					'implementation' => $definition->implementation,
				],
				$this->registry->get_contextual_bindings(),
			),
		);
	}

	private static function expected_singletons_map(): array {

		return [
			LoggerInterface::class => NullLogger::class,

			MigrationRunnerPort::class => MigrationRunner::class,

			DatabasePort::class => WpdbDatabase::class,
			StoragePort::class => WordPressOptionsStorage::class,
			CampaignReadPort::class => CampaignReadRepository::class,
			CampaignRepositoryPort::class => CampaignRepository::class,
			DonationRepositoryPort::class => DonationRepository::class,
			ApplicationEventBusPort::class => ApplicationEventBus::class,

			HookDispatcherRegistrarPort::class => HookDispatcherRegistrar::class,
			BootUnitRunnerPort::class => BootUnitRunner::class,
			WordPressRuntimeInterface::class => WordPressRuntime::class,
			...HookDispatcherDefinitions::classes(),
		];
	}

	private static function expected_contextual_bindings(): array {

		return [
			[
				'consumer' => ApplicationEventBus::class,
				'dependency' => ApplicationEventListenerInterface::class,
				'implementation' => [
					WordPressActionApplicationEventListener::class,
					CampaignMetricsProjectionApplicationEventListener::class,
				],
			],
			[
				'consumer' => BootUnitRunner::class,
				'dependency' => BootUnitInterface::class,
				'implementation' => BootUnitDefinitions::classes(),
			],
			[
				'consumer' => HookDispatcherRegistrar::class,
				'dependency' => HookDispatcherInterface::class,
				'implementation' => HookDispatcherDefinitions::classes(),
			],
			[
				'consumer' => AdminPageRegistrar::class,
				'dependency' => AdminPageInterface::class,
				'implementation' => AdminPageDefinitions::classes(),
			],
			[
				'consumer' => AdminSettingsReader::class,
				'dependency' => AdminSettingsGroupInterface::class,
				'implementation' => AdminSettingsGroupDefinitions::classes(),
			],
			[
				'consumer' => AdminSettingsGroupRegistrar::class,
				'dependency' => AdminSettingsGroupInterface::class,
				'implementation' => AdminSettingsGroupDefinitions::classes(),
			],
			[
				'consumer' => RegisterRestApiRoutesBootUnit::class,
				'dependency' => RestRouteInterface::class,
				'implementation' => RestRouteDefinitions::classes(),
			],
			[
				'consumer' => RegisterPostTypesBootUnit::class,
				'dependency' => PostTypeConfigInterface::class,
				'implementation' => PostTypeConfigDefinitions::classes(),
			],
			[
				'consumer' => FilterAllowedBlocksByPostTypeBootUnit::class,
				'dependency' => PostTypeConfigInterface::class,
				'implementation' => PostTypeConfigDefinitions::classes(),
			],
			[
				'consumer' => MigrationRunner::class,
				'dependency' => AbstractMigration::class,
				'implementation' => MigrationDefinitions::classes(),
			],
		];
	}
}
