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
	 * @throws InvalidDonationIdException When the EntityId cannot be represented as a valid UUID.
	 */
	public static function from_entity_id( EntityId $id ): self {

		try {
			$id->get_as_uuid();

			return new self( $id );
		} catch ( InvalidEntityIdException $e ) {
			throw new InvalidDonationIdException(
				sprintf(
					'Donation ID must be a valid UUID. Given: %s.',
					(string) $id->get_value(),
				),
				previous: $e,
			);
		}
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
	 * @throws InvalidDonationIdException When the value cannot be represented as a valid UUID.
	 */
	public static function from_value( string $value ): self {

		try {
			return self::from_entity_id( EntityId::create( $value ) );
		} catch ( InvalidEntityIdException $e ) {
			throw new InvalidDonationIdException(
				sprintf(
					'Donation ID must be a valid UUID. Given: %s.',
					$value,
				),
				previous: $e,
			);
		}
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
}
