<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( InvalidHookDispatcherArgumentException::class )]
final class InvalidHookDispatcherArgumentExceptionTest extends FundrikTestCase {

	#[Test]
	public function constructor_sets_context_and_uses_custom_message(): void {

		$exception = new InvalidHookDispatcherArgumentException(
			argument: 'allowed',
			hook: 'allowed_block_types_all',
			message: 'Custom validation message.',
		);

		self::assertSame( 'allowed', $exception->argument );
		self::assertSame( 'allowed_block_types_all', $exception->hook );
		self::assertSame( 'Custom validation message.', $exception->getMessage() );
	}

	#[Test]
	public function create_returns_exception_with_context_and_default_message(): void {

		$exception = InvalidHookDispatcherArgumentException::create( argument: 'post_id', hook: 'delete_post' );

		self::assertSame( 'post_id', $exception->argument );
		self::assertSame( 'delete_post', $exception->hook );
		self::assertSame( 'Invalid $post_id argument in delete_post hook.', $exception->getMessage() );
	}
}
