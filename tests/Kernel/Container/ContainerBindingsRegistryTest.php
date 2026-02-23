<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Kernel\Container;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\WordPress\Infrastructure\DatabaseInterface;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository;
use Fundrik\WordPress\Infrastructure\StorageInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitRunner;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistrar;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistry;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContext;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Integration\WordPressOptionsStorage;
use Fundrik\WordPress\Integration\WpdbDatabase;
use Fundrik\WordPress\Kernel\Container\ContainerBindingsRegistry;
use Fundrik\WordPress\Kernel\Ports\BootUnitRunnerPort;
use Fundrik\WordPress\Kernel\Ports\HookDispatcherRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

#[CoversClass( ContainerBindingsRegistry::class )]
#[UsesClass( HookDispatcherRegistry::class )]
final class ContainerBindingsRegistryTest extends FundrikTestCase {

	private HookDispatcherRegistry $hook_dispatcher_registry;
	private ContainerBindingsRegistry $registry;

	protected function setUp(): void {

		parent::setUp();

		$this->hook_dispatcher_registry = new HookDispatcherRegistry();
		$this->registry = new ContainerBindingsRegistry( $this->hook_dispatcher_registry );
	}

	#[Test]
	public function it_exposes_expected_singletons(): void {

		$this->assertSame(
			self::expected_singletons_map( $this->hook_dispatcher_registry ),
			$this->registry->get_singletons(),
		);
	}

	#[Test]
	public function it_has_no_transient_bindings_yet(): void {

		$this->assertSame( [], $this->registry->get_bindings() );
	}

	private static function expected_singletons_map( HookDispatcherRegistry $hook_dispatcher_registry ): array {

		return [
			LoggerInterface::class => NullLogger::class,

			MigrationRunnerPort::class => MigrationRunner::class,

			DatabaseInterface::class => WpdbDatabase::class,
			StorageInterface::class => WordPressOptionsStorage::class,
			CampaignRepositoryPort::class => CampaignRepository::class,

			HookDispatcherRegistrarPort::class => HookDispatcherRegistrar::class,
			BootUnitRunnerPort::class => BootUnitRunner::class,
			WordPressContextInterface::class => WordPressContext::class,
		] + $hook_dispatcher_registry->get_dispatcher_classes();
	}
}
