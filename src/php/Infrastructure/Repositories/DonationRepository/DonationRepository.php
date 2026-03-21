<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\DonationRepository;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationNotFoundExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\WordPress\Infrastructure\DatabaseDuplicateKeyExceptionInterface;
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
				sprintf( 'Failed to fetch donation "%s".', $id_value ),
				previous: $e,
			);
		}

		if ( $row === null ) {
			return null;
		}

		return $this->map_row_to_donation_or_fail( $row );
	}

	/**
	 * Returns whether any donations exist for the specified campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $campaign_id The campaign ID to check.
	 *
	 * @return bool True when at least one donation exists for the campaign.
	 *
	 * @throws DonationRepositoryExceptionInterface When the existence check fails.
	 */
	public function exists_by_campaign_id( EntityId $campaign_id ): bool {

		$campaign_id_value = $this->get_campaign_id_as_int_or_fail( $campaign_id );

		try {
			return $this->db->exists_by_column( self::TABLE_NAME, 'campaign_id', $campaign_id_value );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new DonationRepositoryException(
				sprintf( 'Failed to check donations for campaign "%s".', $campaign_id_value ),
				previous: $e,
			);
		}
	}

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
				sprintf( 'Failed to check donation "%s" existence.', $id_value ),
				previous: $e,
			);
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
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
		$version = $donation->get_version();

		if ( ! $version->equals( EntityVersion::initial() ) ) {
			throw new DonationRepositoryException(
				sprintf(
					'Cannot insert donation "%s": version must be initial. Given: %d.',
					$donation_id,
					$version->get_value(),
				),
			);
		}

		try {
			$this->db->insert( self::TABLE_NAME, $this->map_donation_to_insert_row( $donation ) );
		} catch ( DatabaseDuplicateKeyExceptionInterface $e ) {
			throw new DonationAlreadyExistsException( $donation_id, $e );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new DonationRepositoryException(
				sprintf( 'Failed to insert donation "%s".', $donation_id ),
				previous: $e,
			);
		}

		$persisted = $this->find_by_id( $donation->get_id() );

		if ( $persisted !== null ) {
			return $persisted;
		}

		throw new DonationRepositoryException(
			sprintf( 'Donation "%s" was inserted, but fetching persisted snapshot failed.', $donation_id ),
		);
	}
	// phpcs:enable

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
	 * @throws DonationNotFoundExceptionInterface When the donation does not exist.
	 * @throws DonationRepositoryExceptionInterface When the update fails for another reason.
	 */
	public function update( Donation $donation ): Donation {

		$donation_id = $this->get_donation_id_as_uuid_or_fail( $donation->get_id() );

		$expected_version = $donation->get_version();
		$new_version = $expected_version->next();

		$data = $this->map_donation_to_update_row( $donation );
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
				sprintf( 'Failed to update donation "%s".', $donation_id ),
				previous: $e,
			);
		}

		if ( $affected === 0 ) {

			if ( ! $this->exists_by_id( $donation->get_id() ) ) {
				throw new DonationNotFoundException( $donation_id );
			}

			throw new DonationRepositoryException(
				sprintf( 'Cannot update donation "%s": version mismatch.', $donation_id ),
			);
		}

		$persisted = $this->find_by_id( $donation->get_id() );

		if ( $persisted !== null ) {
			return $persisted;
		}

		throw new DonationRepositoryException(
			sprintf( 'Donation "%s" was updated, but fetching persisted snapshot failed.', $donation_id ),
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
				amount: ArrayExtractor::extract_int_required( $row, 'amount' ),
				currency_code: ArrayExtractor::extract_string_required( $row, 'currency_code' ),
				status: ArrayExtractor::extract_string_required( $row, 'status' ),
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
	 * Converts a new Donation entity into a persistence row.
	 *
	 * @since 1.0.0
	 *
	 * @param Donation $donation The donation to convert.
	 *
	 * @return array<string, int|string|bool|null> The persistence row data.
	 */
	private function map_donation_to_insert_row( Donation $donation ): array {

		$created_at = $this->current_utc_timestamp();

		return [
			'id' => $this->get_donation_id_as_uuid_or_fail( $donation->get_id() ),
			'version' => $donation->get_version()->get_value(),
			'campaign_id' => $this->get_campaign_id_as_int_or_fail( $donation->get_campaign_id() ),
			'amount' => $donation->get_money()->get_amount()->get_value(),
			'currency_code' => $donation->get_money()->get_currency()->get_code(),
			'status' => $donation->get_status()->value,
			'created_at' => $created_at,
			'updated_at' => null,
		];
	}

	/**
	 * Converts a Donation entity into persistence fields that are controlled by the domain model.
	 *
	 * @since 1.0.0
	 *
	 * @param Donation $donation The donation to convert.
	 *
	 * @return array<string, int|string|bool|null> The persistence row data.
	 */
	private function map_donation_to_update_row( Donation $donation ): array {

		return [
			'status' => $donation->get_status()->value,
			'updated_at' => $this->current_utc_timestamp(),
		];
	}

	/**
	 * Returns current UTC timestamp formatted for storage.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current UTC timestamp.
	 */
	private function current_utc_timestamp(): string {

		return UtcDateTime::now()->format( self::DATETIME_DB_FORMAT );
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
