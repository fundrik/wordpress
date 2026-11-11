<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\FilterAllowedBlocksByPostTypeBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AllowedBlockTypesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntimeInterface;
use Fundrik\WordPress\Tests\Fixtures\TestPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpBlockEditorContext;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpPost;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use stdClass;

#[CoversClass( FilterAllowedBlocksByPostTypeBootUnit::class )]
#[UsesClass( AllowedBlockTypesAllFilterHookDispatcher::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( Logger::class )]
final class FilterAllowedBlocksByPostTypeBootUnitTest extends WordPressTestCase {

	private const string POST_TYPE = 'campaign';
	private const string RESTRICTED_BLOCK = 'fundrik/restricted';

	private FilterAllowedBlocksByPostTypeBootUnit $boot_unit;
	private AllowedBlockTypesAllFilterHookDispatcher $hook_dispatcher;
	private LoggerInterface&MockInterface $psr_logger;
	private WordPressRuntimeInterface&MockInterface $wp_runtime;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->wp_runtime = Mockery::mock( WordPressRuntimeInterface::class );
		$this->hook_dispatcher = new AllowedBlockTypesAllFilterHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
		$this->boot_unit = new FilterAllowedBlocksByPostTypeBootUnit(
			$this->hook_dispatcher,
			$this->wp_runtime,
			new BootUnitLogger( $this->psr_logger ),
			new TestPostTypeConfig( self::POST_TYPE, [ self::RESTRICTED_BLOCK ] ),
		);
	}

	#[Test]
	public function boot_returns_original_allowed_blocks_when_editor_context_has_no_post(): void {

		$allowed = [ self::RESTRICTED_BLOCK ];

		$this->boot_unit->boot();

		self::assertSame(
			$allowed,
			$this->hook_dispatcher->dispatch( $allowed, new WpBlockEditorContext() ),
		);
	}

	#[Test]
	public function boot_returns_false_and_logs_when_upstream_disallows_all_blocks(): void {

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'All blocks are disallowed by upstream filter.',
				[
					'service_class' => FilterAllowedBlocksByPostTypeBootUnit::class,
					'logger_class' => BootUnitLogger::class,
					'component' => 'boot_units',
					'layer' => 'integration',
					'system' => 'wordpress',
					'post_type' => self::POST_TYPE,
				],
			);

		$this->boot_unit->boot();

		self::assertFalse(
			$this->hook_dispatcher->dispatch(
				false,
				new WpBlockEditorContext( new WpPost( self::POST_TYPE ) ),
			),
		);
	}

	#[Test]
	#[DataProvider( 'explicit_allowed_blocks_cases' )]
	public function boot_filters_explicit_allowed_blocks_for_current_post_type(
		string $post_type,
		array $expected,
	): void {

		$this->boot_unit->boot();

		self::assertSame(
			$expected,
			$this->hook_dispatcher->dispatch(
				[ self::RESTRICTED_BLOCK, 'core/paragraph' ],
				new WpBlockEditorContext( new WpPost( $post_type ) ),
			),
		);
	}

	#[Test]
	public function boot_expands_true_to_registered_blocks_before_filtering(): void {

		$this->wp_runtime
			->shouldReceive( 'get_registered_block_types' )
			->once()
			->andReturn(
				[
					'core/paragraph' => new stdClass(),
					self::RESTRICTED_BLOCK => new stdClass(),
					'fundrik/unrestricted' => new stdClass(),
				],
			);

		$this->boot_unit->boot();

		self::assertSame(
			[ 'core/paragraph', 'fundrik/unrestricted' ],
			$this->hook_dispatcher->dispatch(
				true,
				new WpBlockEditorContext( new WpPost() ),
			),
		);
	}

	/**
	 * Returns explicit allowed block filtering cases.
	 *
	 * @return iterable<string, array{string, list<string>}> Allowed block filtering cases.
	 */
	public static function explicit_allowed_blocks_cases(): iterable {

		yield 'configured post type' => [
			self::POST_TYPE,
			[ self::RESTRICTED_BLOCK, 'core/paragraph' ],
		];
		yield 'other post type' => [
			'post',
			[ 'core/paragraph' ],
		];
	}
}
