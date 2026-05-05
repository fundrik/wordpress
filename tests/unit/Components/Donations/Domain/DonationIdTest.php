<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Components\Donations\Domain;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Components\Donations\Domain\Exceptions\InvalidDonationIdException;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( DonationId::class )]
#[CoversClass( InvalidDonationIdException::class )]
final class DonationIdTest extends FundrikTestCase {

	#[Test]
	public function from_entity_id_returns_donation_id_for_uuid_entity_id(): void {

		$id = DonationId::from_entity_id(
			EntityId::create( '123e4567-e89b-42d3-a456-426614174000' ),
		);

		self::assertSame( '123e4567-e89b-42d3-a456-426614174000', $id->get_value() );
	}

	#[Test]
	public function from_value_returns_donation_id_for_uuid_string(): void {

		$id = DonationId::from_value( '123e4567-e89b-42d3-a456-426614174001' );

		self::assertSame( '123e4567-e89b-42d3-a456-426614174001', $id->get_value() );
	}

	#[Test]
	public function generate_returns_donation_id_with_uuid_value(): void {

		$id = DonationId::generate();

		self::assertMatchesRegularExpression(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
			$id->get_value(),
		);
	}

	#[Test]
	public function from_entity_id_value_returns_donation_id_for_uuid_string(): void {

		$id = DonationId::from_entity_id_value( '123e4567-e89b-42d3-a456-426614174001' );

		self::assertSame( '123e4567-e89b-42d3-a456-426614174001', $id->get_value() );
	}

	#[Test]
	public function to_entity_id_returns_wrapped_entity_id(): void {

		$id = DonationId::from_value( '123e4567-e89b-42d3-a456-426614174001' );

		self::assertEquals( EntityId::create( '123e4567-e89b-42d3-a456-426614174001' ), $id->to_entity_id() );
	}

	#[Test]
	public function from_entity_id_throws_for_integer_entity_id(): void {

		$this->expectException( InvalidDonationIdException::class );
		$this->expectExceptionMessage( 'Donation ID must be a valid UUIDv4. Given: 42.' );

		DonationId::from_entity_id( EntityId::create( 42 ) );
	}

	#[Test]
	public function from_value_throws_for_invalid_uuid_string(): void {

		$this->expectException( InvalidDonationIdException::class );
		$this->expectExceptionMessage( 'Donation ID must be a valid UUIDv4. Given: abc.' );

		DonationId::from_value( 'abc' );
	}

	#[Test]
	public function from_entity_id_value_throws_for_integer_value(): void {

		$this->expectException( InvalidDonationIdException::class );
		$this->expectExceptionMessage( 'Donation ID must be a valid UUIDv4. Given: 42.' );

		DonationId::from_entity_id_value( 42 );
	}

	#[Test]
	public function from_value_throws_for_non_uuidv4_string(): void {

		$this->expectException( InvalidDonationIdException::class );
		$this->expectExceptionMessage( 'Donation ID must be a valid UUIDv4. Given: 123e4567-e89b-72d3-a456-426614174000.' );

		DonationId::from_value( '123e4567-e89b-72d3-a456-426614174000' );
	}
}
