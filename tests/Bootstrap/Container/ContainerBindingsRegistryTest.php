<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Bootstrap\Container;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\WordPress\Bootstrap\Container\ContainerBindingsRegistry;
use Fundrik\WordPress\Infrastructure\Database\DatabaseInterface;
use Fundrik\WordPress\Infrastructure\EventDispatcher\EventDispatcher;
use Fundrik\WordPress\Infrastructure\EventDispatcher\EventListenerRegistrar;
use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Integration\HookToEventBridges\HookBridgeRegistrar;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContext;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Integration\WordPressOptionsStorage;
use Fundrik\WordPress\Integration\WpdbDatabase;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository;
use Fundrik\WordPress\Infrastructure\StorageInterface;
use Fundrik\WordPress\Kernel\Ports\EventListenerRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\HookBridgeRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Fundrik\WordPress\Tests\FundrikTestCase;
use Illuminate\Contracts\Events\Dispatcher as LaravelEventsDispatcherInterface;
use Illuminate\Events\Dispatcher as LaravelEventsDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

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
	public function it_has_no_transient_bindings_yet(): void {

		$this->assertSame( [], $this->registry->get_bindings() );
	}

	private static function expected_singletons_map(): array {

		return [
			LaravelEventsDispatcherInterface::class => LaravelEventsDispatcher::class,
			InfrastructureEventDispatcherInterface::class => EventDispatcher::class,
			EventListenerRegistrarPort::class => EventListenerRegistrar::class,

			MigrationRunnerPort::class => MigrationRunner::class,

			DatabaseInterface::class => WpdbDatabase::class,
			StorageInterface::class => WordPressOptionsStorage::class,
			CampaignRepositoryPort::class => CampaignRepository::class,

			HookBridgeRegistrarPort::class => HookBridgeRegistrar::class,
			WordPressContextInterface::class => WordPressContext::class,
		];
	}
}
