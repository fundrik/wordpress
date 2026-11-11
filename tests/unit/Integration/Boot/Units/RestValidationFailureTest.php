<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Fundrik\WordPress\Integration\Boot\Units\RestValidationFailure;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( RestValidationFailure::class )]
final class RestValidationFailureTest extends FundrikTestCase {

	#[Test]
	public function constructor_stores_validation_failure_data(): void {

		$failure = new RestValidationFailure(
			error_code: 'rest_invalid_param',
			error_message: 'Invalid parameter.',
			invalid_params: [ 'amount', 'currency' ],
		);

		self::assertSame( 'rest_invalid_param', $failure->error_code );
		self::assertSame( 'Invalid parameter.', $failure->error_message );
		self::assertSame( [ 'amount', 'currency' ], $failure->invalid_params );
	}
}
