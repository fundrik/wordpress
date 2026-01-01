<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\Listeners;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Helpers\PluginPath;
use Fundrik\WordPress\Infrastructure\Integration\Events\ActionRegisterBlocksEvent;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\RegisterBlocksListener;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( RegisterBlocksListener::class )]
#[UsesClass( PluginPath::class )]
#[UsesClass( ActionRegisterBlocksEvent::class )]
final class RegisterBlocksListenerTest extends MockeryTestCase {

	private WordPressContextInterface&MockInterface $context;

	private RegisterBlocksListener $listener;

	protected function setUp(): void {

		parent::setUp();

		$this->context = Mockery::mock( WordPressContextInterface::class );

		$this->listener = new RegisterBlocksListener();
	}

	#[Test]
	public function handle_registers_blocks_from_metadata_collection(): void {

		Functions\expect( 'wp_register_block_types_from_metadata_collection' )
			->once()
			->with(
				PluginPath::Blocks->get_full_path(),
				PluginPath::BlocksManifest->get_full_path(),
			);

		$event = new ActionRegisterBlocksEvent( $this->context );

		$this->listener->handle( $event );
	}
}
