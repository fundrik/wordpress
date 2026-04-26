<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Ids;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;

/**
 * Represents a campaign ID.
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
	 * @param int $value Campaign ID.
	 */
	private function __construct(
		private int $value,
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
			return new self( $id->get_as_int() );
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
			$entity_id = EntityId::create( $value );
		} catch ( InvalidEntityIdException $e ) {
			throw new CampaignIdException(
				sprintf(
					'Campaign ID must be a positive integer. Given: %s.',
					(string) $value,
				),
				previous: $e,
			);
		}

		return self::from_entity_id( $entity_id );
	}

	/**
	 * Returns the campaign ID value.
	 *
	 * @since 1.0.0
	 *
	 * @return int Campaign ID.
	 */
	public function get_value(): int {

		return $this->value;
	}
}
