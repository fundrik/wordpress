<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Donations\Domain;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\WordPress\Components\Donations\Domain\Exceptions\InvalidDonationIdException;

/**
 * Represents a donation ID.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationId {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $entity_id Wrapped donation EntityId.
	 */
	private function __construct(
		private EntityId $entity_id,
	) {}

	/**
	 * Creates a donation ID from EntityId.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id Donation EntityId.
	 *
	 * @return self Donation ID.
	 *
	 * @throws InvalidDonationIdException When the EntityId cannot be represented as a valid UUIDv4.
	 */
	public static function from_entity_id( EntityId $id ): self {

		try {
			$uuid = $id->get_as_uuid();
		} catch ( InvalidEntityIdException $e ) {
			throw new InvalidDonationIdException(
				sprintf(
					'Donation ID must be a valid UUIDv4. Given: %s.',
					(string) $id->get_value(),
				),
				previous: $e,
			);
		}

		self::assert_uuid_v4( $uuid );

		return new self( $id );
	}

	/**
	 * Generates a donation ID.
	 *
	 * @since 1.0.0
	 *
	 * @return self Donation ID.
	 */
	public static function generate(): self {

		return self::from_entity_id( EntityId::uuid4() );
	}

	/**
	 * Creates a donation ID from raw value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Raw donation ID value.
	 *
	 * @return self Donation ID.
	 *
	 * @throws InvalidDonationIdException When the value cannot be represented as a valid UUIDv4.
	 */
	public static function from_value( string $value ): self {

		try {
			$entity_id = EntityId::create( $value );
			$uuid = $entity_id->get_as_uuid();
		} catch ( InvalidEntityIdException $e ) {
			throw new InvalidDonationIdException(
				sprintf(
					'Donation ID must be a valid UUIDv4. Given: %s.',
					$value,
				),
				previous: $e,
			);
		}

		self::assert_uuid_v4( $uuid );

		return new self( $entity_id );
	}

	/**
	 * Creates a donation ID from an EntityId value.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $value EntityId value.
	 *
	 * @return self Donation ID.
	 *
	 * @throws InvalidDonationIdException When the value cannot be represented as a valid UUIDv4.
	 */
	public static function from_entity_id_value( int|string $value ): self {

		if ( ! is_string( $value ) ) {
			throw new InvalidDonationIdException(
				sprintf(
					'Donation ID must be a valid UUIDv4. Given: %s.',
					(string) $value,
				),
			);
		}

		return self::from_value( $value );
	}

	/**
	 * Returns the donation ID value.
	 *
	 * @since 1.0.0
	 *
	 * @return string Donation ID.
	 */
	public function get_value(): string {

		// from_entity_id() already rejects non-UUID EntityId values.
		return $this->entity_id->get_as_uuid();
	}

	/**
	 * Returns the donation ID as EntityId.
	 *
	 * @since 1.0.0
	 *
	 * @return EntityId Donation EntityId.
	 */
	public function to_entity_id(): EntityId {

		return $this->entity_id;
	}

	/**
	 * Checks whether the value is a UUIDv4.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value UUID string.
	 *
	 * @throws InvalidDonationIdException When the UUID is not version 4.
	 */
	private static function assert_uuid_v4( string $value ): void {

		if ( preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value ) === 1 ) {
			return;
		}

		throw new InvalidDonationIdException(
			sprintf(
				'Donation ID must be a valid UUIDv4. Given: %s.',
				$value,
			),
		);
	}
}
