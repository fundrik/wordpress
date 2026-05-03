<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidUtcDateTimeException;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Override;

/**
 * Retrieves campaign read models from persistence.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignReadRepository implements CampaignReadPort {

	private const string TABLE_NAME = 'fundrik_campaigns';
	private const string DATETIME_DB_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DatabasePort $db Executes database queries.
	 */
	public function __construct(
		private DatabasePort $db,
	) {}

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id Campaign ID.
	 *
	 * @return Campaign|null Campaign if found, null otherwise.
	 *
	 * @throws CampaignReadException When the lookup fails.
	 */
	#[Override]
	public function find_by_id( EntityId $id ): ?Campaign {

		$id_int = $this->require_campaign_id( $id );

		try {
			$row = $this->db->get_by_id( self::TABLE_NAME, $id_int );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new CampaignReadException(
				sprintf( 'Failed to retrieve campaign "%d".', $id_int ),
				previous: $e,
			);
		}

		if ( $row === null ) {
			return null;
		}

		return $this->map_row_to_campaign( $row );
	}

	/**
	 * Returns campaign ID as an integer.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id Campaign ID.
	 *
	 * @return int Campaign ID.
	 *
	 * @throws CampaignReadException When the ID cannot be represented as a positive integer.
	 */
	private function require_campaign_id( EntityId $id ): int {

		try {
			return CampaignId::from_entity_id( $id )->get_value();
		} catch ( InvalidCampaignIdException $e ) {
			throw new CampaignReadException( $e->getMessage(), previous: $e );
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Builds a campaign read model from a persistence row.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $row Persistence row.
	 *
	 * @return Campaign Mapped campaign.
	 *
	 * @throws CampaignReadException When the row cannot be mapped.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function map_row_to_campaign( array $row ): Campaign {

		try {
			return new Campaign(
				id: ArrayExtractor::extract_int_required( $row, 'id' ),
				title: ArrayExtractor::extract_string_required( $row, 'title' ),
				accepts_donations: ArrayExtractor::extract_bool_required( $row, 'accepts_donations' ),
				currency_code: ArrayExtractor::extract_string_required( $row, 'currency_code' ),
				target_amount: ArrayExtractor::extract_int_nullable_required( $row, 'target_amount' ),
				created_at: UtcDateTime::create_from_format(
					ArrayExtractor::extract_string_required( $row, 'created_at' ),
					self::DATETIME_DB_FORMAT,
				),
				updated_at: $this->map_updated_at( $row ),
			);
		} catch ( ArrayExtractionException | InvalidUtcDateTimeException $e ) {

			$id = $row['id'] ?? null;
			$id_for_error = is_int( $id ) || is_string( $id ) ? (string) $id : '<unavailable>';

			throw new CampaignReadException(
				sprintf( 'Failed to map campaign row "%s".', $id_for_error ),
				previous: $e,
			);
		}
	}
	// phpcs:enable

	/**
	 * Maps the optional update timestamp from persistence.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $row Persistence row.
	 *
	 * @return UtcDateTime|null Update timestamp, if available.
	 *
	 * @throws ArrayExtractionException When the row value is invalid.
	 * @throws InvalidUtcDateTimeException When the value cannot be parsed.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function map_updated_at( array $row ): ?UtcDateTime {

		$updated_at = ArrayExtractor::extract_string_nullable_required( $row, 'updated_at' );

		if ( $updated_at === null ) {
			return null;
		}

		return UtcDateTime::create_from_format( $updated_at, self::DATETIME_DB_FORMAT );
	}
}
