<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Ids;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\Ids\DonationId;
use Fundrik\WordPress\Infrastructure\Ids\DonationIdException;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( DonationId::class )]
#[CoversClass( DonationIdException::class )]
final class DonationIdTest extends FundrikTestCase {

	#[Test]
	public function from_entity_id_returns_donation_id_for_uuid_entity_id(): void {

		$id = DonationId::from_entity_id(
			EntityId::create( '123e4567-e89b-12d3-a456-426614174000' ),
		);

		self::assertSame( '123e4567-e89b-12d3-a456-426614174000', $id->get_value() );
	}

	#[Test]
	public function from_value_returns_donation_id_for_uuid_string(): void {

		$id = DonationId::from_value( '123e4567-e89b-12d3-a456-426614174001' );

		self::assertSame( '123e4567-e89b-12d3-a456-426614174001', $id->get_value() );
	}

	#[Test]
	public function from_entity_id_throws_for_integer_entity_id(): void {

		$this->expectException( DonationIdException::class );
		$this->expectExceptionMessage( 'Donation ID must be a valid UUID. Given: 42.' );

		DonationId::from_entity_id( EntityId::create( 42 ) );
	}

	#[Test]
	public function from_value_throws_for_invalid_uuid_string(): void {

		$this->expectException( DonationIdException::class );
		$this->expectExceptionMessage( 'Donation ID must be a valid UUID. Given: abc.' );

		DonationId::from_value( 'abc' );
	}
}
