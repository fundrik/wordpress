<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Tests\FundrikTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( InvalidHookDispatcherArgumentException::class )]
final class InvalidHookDispatcherArgumentExceptionTest extends FundrikTestCase {

	#[Test]
	public function create_sets_argument_and_hook_and_uses_default_message(): void {

		$e = InvalidHookDispatcherArgumentException::create( argument: 'post_id', hook: 'delete_post' );

		self::assertInstanceOf( InvalidArgumentException::class, $e );
		self::assertSame( 'post_id', $e->argument );
		self::assertSame( 'delete_post', $e->hook );
		self::assertSame( 'Invalid $post_id argument in delete_post hook.', $e->getMessage() );
	}

	#[Test]
	public function constructor_uses_custom_message_when_provided(): void {

		$e = new InvalidHookDispatcherArgumentException(
			argument: 'allowed',
			hook: 'allowed_block_types_all',
			message: 'Custom message.',
		);

		self::assertSame( 'allowed', $e->argument );
		self::assertSame( 'allowed_block_types_all', $e->hook );
		self::assertSame( 'Custom message.', $e->getMessage() );
	}
}
