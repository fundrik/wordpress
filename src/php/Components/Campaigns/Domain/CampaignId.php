<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\CampaignIdException;

/**
 * Represents a WordPress campaign ID.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignId {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $entity_id Wrapped campaign EntityId.
	 */
	private function __construct(
		private EntityId $entity_id,
	) {}

	/**
	 * Creates a campaign ID from EntityId.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id Campaign EntityId.
	 *
	 * @return self Campaign ID.
	 *
	 * @throws CampaignIdException When the EntityId cannot be represented as a positive integer.
	 */
	public static function from_entity_id( EntityId $id ): self {

		try {
			$id->get_as_int();

			return new self( $id );
		} catch ( InvalidEntityIdException $e ) {
			throw new CampaignIdException(
				sprintf(
					'Campaign ID must be a positive integer. Given: %s.',
					(string) $id->get_value(),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Creates a campaign ID from raw value.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $value Raw campaign ID value.
	 *
	 * @return self Campaign ID.
	 *
	 * @throws CampaignIdException When the value cannot be represented as a positive integer.
	 */
	public static function from_value( int|string $value ): self {

		try {
			return self::from_entity_id( EntityId::create( $value ) );
		} catch ( InvalidEntityIdException $e ) {
			throw new CampaignIdException(
				sprintf(
					'Campaign ID must be a positive integer. Given: %s.',
					(string) $value,
				),
				previous: $e,
			);
		}
	}

	/**
	 * Returns the campaign ID value.
	 *
	 * @since 1.0.0
	 *
	 * @return int Campaign ID.
	 */
	public function get_value(): int {

		// from_entity_id() already rejects non-integer EntityId values.
		return $this->entity_id->get_as_int();
	}

	/**
	 * Returns the campaign ID as EntityId.
	 *
	 * @since 1.0.0
	 *
	 * @return EntityId Campaign EntityId.
	 */
	public function to_entity_id(): EntityId {

		return $this->entity_id;
	}
}
