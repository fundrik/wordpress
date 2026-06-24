<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\DonationReadRepository;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidUtcDateTimeException;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Components\Donations\Domain\Exceptions\InvalidDonationIdException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Override;

/**
 * Retrieves donation read models from persistence.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationReadRepository implements DonationReadPort {

	private const string TABLE_NAME = 'fundrik_donations';
	private const string DATETIME_DB_FORMAT = 'Y-m-d H:i:s.u';

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
	 * Retrieves a donation by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id Donation ID.
	 *
	 * @return Donation|null Donation if found, null otherwise.
	 *
	 * @throws DonationReadException When the lookup fails.
	 */
	#[Override]
	public function find_by_id( EntityId $id ): ?Donation {

		$id_value = $this->require_donation_id( $id );

		try {
			$row = $this->db->get_by_id( self::TABLE_NAME, $id_value );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new DonationReadException(
				sprintf( 'Failed to fetch donation "%s".', $id_value ),
				previous: $e,
			);
		}

		if ( $row === null ) {
			return null;
		}

		return $this->map_row_to_donation( $row );
	}

	/**
	 * Returns a paginated list of donations.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page Page number.
	 * @param int $per_page Donations per page.
	 *
	 * @return PaginatedDonations Paginated list of donation read models.
	 *
	 * @throws DonationReadException When the lookup fails.
	 */
	#[Override]
	public function paginate( int $page, int $per_page ): PaginatedDonations {

		try {
			[ $rows, $total ] = $this->db->paginate( self::TABLE_NAME, $page, $per_page );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new DonationReadException( 'Failed to retrieve paginated donations.', previous: $e );
		}

		return new PaginatedDonations(
			array_map(
				fn ( array $row ): Donation => $this->map_row_to_donation( $row ),
				$rows,
			),
			$page,
			$per_page,
			$total,
		);
	}

	/**
	 * Returns donation ID as a UUID string.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id Donation ID.
	 *
	 * @return string Donation ID.
	 *
	 * @throws DonationReadException When the ID cannot be represented as a valid UUIDv4.
	 */
	private function require_donation_id( EntityId $id ): string {

		try {
			return DonationId::from_entity_id( $id )->get_value();
		} catch ( InvalidDonationIdException $e ) {
			throw new DonationReadException( $e->getMessage(), previous: $e );
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Builds a donation read model from a persistence row.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $row Persistence row.
	 *
	 * @return Donation Mapped donation.
	 *
	 * @throws DonationReadException When the row cannot be mapped.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function map_row_to_donation( array $row ): Donation {

		try {
			return new Donation(
				id: ArrayExtractor::extract_string_required( $row, 'id' ),
				campaign_id: ArrayExtractor::extract_int_required( $row, 'campaign_id' ),
				amount: ArrayExtractor::extract_int_required( $row, 'amount' ),
				currency_code: ArrayExtractor::extract_string_required( $row, 'currency_code' ),
				status: ArrayExtractor::extract_string_required( $row, 'status' ),
				created_at: UtcDateTime::create_from_format(
					ArrayExtractor::extract_string_required( $row, 'created_at' ),
					self::DATETIME_DB_FORMAT,
				),
				updated_at: $this->map_updated_at( $row ),
			);
		} catch ( ArrayExtractionException | InvalidUtcDateTimeException $e ) {

			$id = $row['id'] ?? null;
			$id_for_error = is_string( $id ) ? $id : '<unavailable>';

			throw new DonationReadException(
				sprintf( 'Failed to map donation row "%s".', $id_for_error ),
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
	 * @return UtcDateTime|null Update timestamp, null otherwise.
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
