<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Ids;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;

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
	 * @param string $value Donation ID.
	 */
	private function __construct(
		private string $value,
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
	 * @throws DonationIdException When the EntityId cannot be represented as a valid UUID.
	 */
	public static function from_entity_id( EntityId $id ): self {

		try {
			return new self( $id->get_as_uuid() );
		} catch ( InvalidEntityIdException $e ) {
			throw new DonationIdException(
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
	 * @throws DonationIdException When the value cannot be represented as a valid UUID.
	 */
	public static function from_value( string $value ): self {

		try {
			return self::from_entity_id( EntityId::create( $value ) );
		} catch ( InvalidEntityIdException $e ) {
			throw new DonationIdException(
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

		return $this->value;
	}
}
