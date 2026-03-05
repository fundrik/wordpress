<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\WordPress\Infrastructure\DatabaseExceptionInterface;
use Fundrik\WordPress\Infrastructure\DatabasePort;

/**
 * Persists and retrieves donations in storage.
 *
 * @since 1.0.0
 */
final readonly class DonationRepository implements DonationRepositoryPort {

	private const string TABLE_NAME = 'fundrik_donations';
	private const string DATETIME_DB_FORMAT = 'Y-m-d H:i:s.u';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DatabasePort $db Executes database queries.
	 * @param DonationFactory $donation_factory Builds Donation entities from persistence values.
	 */
	public function __construct(
		private DatabasePort $db,
		private DonationFactory $donation_factory,
	) {}

	/**
	 * Retrieves a donation by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The donation ID to retrieve.
	 *
	 * @return Donation|null The donation if found, null otherwise.
	 *
	 * @throws DonationRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?Donation {

		$id_value = $this->get_donation_id_as_uuid_or_fail( $id );

		try {
			$row = $this->db->get_by_id( self::TABLE_NAME, $id_value );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new DonationRepositoryException(
				sprintf( 'Failed to fetch donation "%s".', (string) $id_value ),
				previous: $e,
			);
		}

		if ( $row === null ) {
			return null;
		}

		return $this->map_row_to_donation_or_fail( $row );
	}

	/**
	 * Retrieves all donations.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, Donation> The list of donations.
	 *
	 * @phpstan-return list<Donation>
	 *
	 * @throws DonationRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_all(): array {

		try {
			$rows = $this->db->get_all( self::TABLE_NAME );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new DonationRepositoryException( 'Failed to fetch donations.', previous: $e );
		}

		return array_map(
			$this->map_row_to_donation_or_fail( ... ),
			$rows,
		);
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Retrieves all donations for the specified campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $campaign_id The campaign ID to filter by.
	 *
	 * @return array<int, Donation> The list of campaign donations.
	 *
	 * @phpstan-return list<Donation>
	 *
	 * @throws DonationRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_all_by_campaign_id( EntityId $campaign_id ): array {

		$campaign_id_value = $this->get_campaign_id_as_int_or_fail( $campaign_id );

		try {
			$rows = $this->db->get_all_by_column( self::TABLE_NAME, 'campaign_id', $campaign_id_value );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new DonationRepositoryException(
				sprintf( 'Failed to fetch donations for campaign "%s".', (string) $campaign_id_value ),
				previous: $e,
			);
		}

		return array_map(
			$this->map_row_to_donation_or_fail( ... ),
			$rows,
		);
	}
	// phpcs:enable

	/**
	 * Returns whether a donation exists in storage by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The donation ID to check.
	 *
	 * @return bool True if the donation exists.
	 *
	 * @throws DonationRepositoryExceptionInterface When the existence check fails.
	 */
	public function exists_by_id( EntityId $id ): bool {

		$id_value = $this->get_donation_id_as_uuid_or_fail( $id );

		try {
			return $this->db->exists_by_id( self::TABLE_NAME, $id_value );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new DonationRepositoryException(
				sprintf( 'Failed to check donation "%s" existence.', (string) $id_value ),
				previous: $e,
			);
		}
	}

	/**
	 * Inserts a new donation into storage.
	 *
	 * @since 1.0.0
	 *
	 * @param Donation $donation The donation to insert.
	 *
	 * @return Donation The persisted donation snapshot.
	 *
	 * @throws DonationRepositoryExceptionInterface When the insert fails.
	 */
	public function insert( Donation $donation ): Donation {

		$donation_id = $this->get_donation_id_as_uuid_or_fail( $donation->get_id() );

		try {
			$this->db->insert( self::TABLE_NAME, $this->map_donation_to_row( $donation ) );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new DonationRepositoryException(
				sprintf( 'Failed to insert donation "%s".', (string) $donation_id ),
				previous: $e,
			);
		}

		$persisted = $this->find_by_id( $donation->get_id() );

		if ( $persisted !== null ) {
			return $persisted;
		}

		throw new DonationRepositoryException(
			sprintf( 'Donation "%s" was inserted, but fetching persisted snapshot failed.', (string) $donation_id ),
		);
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Updates an existing donation in storage.
	 *
	 * @since 1.0.0
	 *
	 * @param Donation $donation The donation to update.
	 *
	 * @return Donation The persisted donation snapshot.
	 *
	 * @throws DonationRepositoryExceptionInterface When the update fails.
	 */
	public function update( Donation $donation ): Donation {

		$donation_id = $this->get_donation_id_as_uuid_or_fail( $donation->get_id() );

		$expected_version = $donation->get_version();
		$new_version = $expected_version->next();

		$data = $this->map_donation_to_row( $donation );
		$data['version'] = $new_version->get_value();

		try {
			$affected = $this->db->update(
				self::TABLE_NAME,
				$data,
				[
					'id' => $donation_id,
					'version' => $expected_version->get_value(),
				],
			);
		} catch ( DatabaseExceptionInterface $e ) {
			throw new DonationRepositoryException(
				sprintf( 'Failed to update donation "%s".', (string) $donation_id ),
				previous: $e,
			);
		}

		if ( $affected === 0 ) {

			if ( ! $this->exists_by_id( $donation->get_id() ) ) {
				throw new DonationRepositoryException(
					sprintf( 'Cannot update donation "%s": persisted record not found.', (string) $donation_id ),
				);
			}

			throw new DonationRepositoryException(
				sprintf( 'Cannot update donation "%s": version mismatch.', (string) $donation_id ),
			);
		}

		$persisted = $this->find_by_id( $donation->get_id() );

		if ( $persisted !== null ) {
			return $persisted;
		}

		throw new DonationRepositoryException(
			sprintf( 'Donation "%s" was updated, but fetching persisted snapshot failed.', (string) $donation_id ),
		);
	}
	// phpcs:enable

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, Universal.WhiteSpace.DisallowInlineTabs.NonIndentTabsUsed, SlevomatCodingStandard.Files.LineLength.LineTooLong
	/**
	 * Builds a Donation entity from a persistence row.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $row The persistence row.
	 *
	 * @return Donation The mapped donation entity.
	 *
	 * @throws DonationRepositoryExceptionInterface When the row cannot be mapped.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function map_row_to_donation_or_fail( array $row ): Donation {

		try {
			return $this->donation_factory->create_from_primitives(
				id: ArrayExtractor::extract_string_required( $row, 'id' ),
				version: ArrayExtractor::extract_int_required( $row, 'version' ),
				campaign_id: ArrayExtractor::extract_int_required( $row, 'campaign_id' ),
				amount_minor: ArrayExtractor::extract_int_required( $row, 'amount_minor' ),
				currency: ArrayExtractor::extract_string_required( $row, 'currency' ),
				status: ArrayExtractor::extract_string_required( $row, 'status' ),
				created_at: ArrayExtractor::extract_datetime_required( $row, 'created_at', self::DATETIME_DB_FORMAT ),
				captured_at: ArrayExtractor::extract_datetime_nullable_required( $row, 'captured_at', self::DATETIME_DB_FORMAT ),
				status_changed_at: ArrayExtractor::extract_datetime_nullable_required( $row, 'status_changed_at', self::DATETIME_DB_FORMAT ),
			);
		} catch ( DonationFactoryException | ArrayExtractionException $e ) {

			$id = $row['id'] ?? null;
			$id_for_error = is_string( $id ) ? $id : '<unavailable>';

			throw new DonationRepositoryException(
				sprintf( 'Failed to map donation row "%s".', $id_for_error ),
				previous: $e,
			);
		}
	}
	// phpcs:enable

	/**
	 * Converts a Donation entity into a persistence row.
	 *
	 * @since 1.0.0
	 *
	 * @param Donation $donation The donation to convert.
	 *
	 * @return array<string, int|string|bool|null> The persistence row data.
	 */
	private function map_donation_to_row( Donation $donation ): array {

		return [
			'id' => $this->get_donation_id_as_uuid_or_fail( $donation->get_id() ),
			'version' => $donation->get_version()->get_value(),
			'campaign_id' => $this->get_campaign_id_as_int_or_fail( $donation->get_campaign_id() ),
			'amount_minor' => $donation->get_money()->get_amount_minor(),
			'currency' => $donation->get_money()->get_currency(),
			'status' => $donation->get_status()->value,
			'created_at' => $donation->get_created_at()->format( self::DATETIME_DB_FORMAT ),
			'captured_at' => $donation->get_captured_at()?->format( self::DATETIME_DB_FORMAT ),
			'status_changed_at' => $donation->get_status_changed_at()?->format( self::DATETIME_DB_FORMAT ),
		];
	}

	/**
	 * Converts a donation ID to UUID string.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The donation ID to convert.
	 *
	 * @return string The UUID value.
	 *
	 * @throws DonationRepositoryExceptionInterface When the ID cannot be represented as UUID.
	 */
	private function get_donation_id_as_uuid_or_fail( EntityId $id ): string {

		try {
			return $id->get_as_uuid();
		} catch ( InvalidEntityIdException $e ) {

			throw new DonationRepositoryException(
				sprintf(
					'Donation ID must be UUID-compatible. Given: %s.',
					(string) $id->get_value(),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Converts a campaign ID to integer campaign ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The campaign ID to convert.
	 *
	 * @return int The integer campaign ID.
	 *
	 * @throws DonationRepositoryExceptionInterface When the ID cannot be represented as int.
	 */
	private function get_campaign_id_as_int_or_fail( EntityId $id ): int {

		try {
			return $id->get_as_int();
		} catch ( InvalidEntityIdException $e ) {

			throw new DonationRepositoryException(
				sprintf(
					'Campaign ID must be int-compatible. Given: %s.',
					(string) $id->get_value(),
				),
				previous: $e,
			);
		}
	}
}
