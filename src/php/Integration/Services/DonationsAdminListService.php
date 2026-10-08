<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign as CoreCampaign;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation as CoreDonation;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Components\Donations\Domain\Exceptions\InvalidDonationIdException;
use Fundrik\WordPress\Integration\ReadModels\DonationAdminListItem;
use Fundrik\WordPress\Integration\ReadModels\PaginatedDonationsAdminList;
use Fundrik\WordPress\Presentation\Formatters\DateTimeFormatter;
use Fundrik\WordPress\Presentation\Formatters\MoneyFormatter;
use Psr\Log\LoggerInterface;

/**
 * Provides donation rows for the admin donations list.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationsAdminListService {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationQueryService $donation_query Reads paginated donations.
	 * @param CampaignReadPort $campaign_read Reads campaigns in batches.
	 * @param LoggerInterface $logger Writes structured log entries for donations list operations.
	 * @param DateTimeFormatter $date_time_formatter Formats timestamps for display.
	 * @param MoneyFormatter $money_formatter Formats money amounts for display.
	 */
	public function __construct(
		private DonationQueryService $donation_query,
		private CampaignReadPort $campaign_read,
		private LoggerInterface $logger,
		private DateTimeFormatter $date_time_formatter,
		private MoneyFormatter $money_formatter,
	) {}

	/**
	 * Returns a paginated list of donation rows.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page Page number.
	 * @param int $per_page Rows per page.
	 *
	 * @return PaginatedDonationsAdminList Paginated donation rows.
	 */
	public function paginate( int $page, int $per_page ): PaginatedDonationsAdminList {

		$paginated_donations = $this->donation_query->paginate( $page, $per_page );

		$rows = $this->prepare_rows( $paginated_donations );

		$campaigns = $this->find_campaigns_for_rows( $rows );

		return new PaginatedDonationsAdminList(
			$this->build_admin_list_items( $rows, $campaigns ),
			$paginated_donations->get_page(),
			$paginated_donations->get_per_page(),
			$paginated_donations->get_total(),
		);
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, SlevomatCodingStandard.Files.LineLength.LineTooLong
	/**
	 * Normalizes donation rows for campaign lookup and display mapping.
	 *
	 * @since 1.0.0
	 *
	 * @param PaginatedDonations $paginated_donations Paginated donations.
	 *
	 * @return list<array{donation: CoreDonation, donation_id: DonationId, campaign_id: CampaignId}> Prepared donation rows.
	 */
	private function prepare_rows( PaginatedDonations $paginated_donations ): array {

		$rows = [];

		foreach ( $paginated_donations->get_items() as $donation ) {

			try {
				$donation_id = DonationId::from_entity_id_value( $donation->get_id() );
			} catch ( InvalidDonationIdException ) {
				$this->log_invalid_donation_id( $donation );
				continue;
			}

			try {
				$campaign_id = CampaignId::from_entity_id_value( $donation->get_campaign_id() );
			} catch ( InvalidCampaignIdException ) {
				$this->log_invalid_campaign_id( $donation );
				continue;
			}

			$rows[] = [
				'donation' => $donation,
				'donation_id' => $donation_id,
				'campaign_id' => $campaign_id,
			];
		}

		return $rows;
	}
	// phpcs:enable

	// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
	/**
	 * Finds campaigns referenced by prepared donation rows.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{donation: CoreDonation, donation_id: DonationId, campaign_id: CampaignId}> $rows Prepared donation rows.
	 *
	 * @return array<int, CoreCampaign> Campaigns keyed by ID.
	 */
	private function find_campaigns_for_rows( array $rows ): array {

		$campaign_ids = [];

		foreach ( $rows as $row ) {
			$campaign_ids[ $row['campaign_id']->get_value() ] = $row['campaign_id'];
		}

		$campaign_entity_ids = array_map(
			static fn ( CampaignId $campaign_id ): EntityId => $campaign_id->to_entity_id(),
			array_values( $campaign_ids ),
		);

		return $this->campaign_read->find_by_ids( $campaign_entity_ids );
	}
	// phpcs:enable

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, SlevomatCodingStandard.Files.LineLength.LineTooLong
	/**
	 * Maps donation read models into display rows.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{donation: CoreDonation, donation_id: DonationId, campaign_id: CampaignId}> $rows Prepared donation rows.
	 * @param array<int, CoreCampaign> $campaigns Campaigns keyed by ID.
	 *
	 * @return list<DonationAdminListItem> Donation rows.
	 */
	private function build_admin_list_items( array $rows, array $campaigns ): array {

		$items = [];

		foreach ( $rows as $row ) {

			$donation = $row['donation'];
			$donation_id = $row['donation_id'];
			$campaign_id = $row['campaign_id']->get_value();
			$campaign = $campaigns[ $campaign_id ] ?? null;

			if ( $campaign === null ) {
				$this->log_missing_campaign( $donation, $campaign_id );
				continue;
			}

			$items[] = new DonationAdminListItem(
				id: $donation_id->get_value(),
				campaign_title: $campaign->get_title(),
				campaign_edit_url: admin_url( sprintf( 'post.php?post=%d&action=edit', $campaign->get_id() ) ),
				amount: $this->money_formatter->format( $donation->get_amount(), $donation->get_currency_code() ),
				status: $donation->get_status(),
				created_at: $this->date_time_formatter->format( $donation->get_created_at()->format( 'Y-m-d H:i:s' ) ),
				updated_at: $donation->get_updated_at() === null
					? null
					: $this->date_time_formatter->format( $donation->get_updated_at()->format( 'Y-m-d H:i:s' ) ),
			);
		}

		return $items;
	}
	// phpcs:enable

	/**
	 * Logs a donation row skipped because its donation ID is invalid.
	 *
	 * @since 1.0.0
	 *
	 * @param CoreDonation $donation Donation read model.
	 */
	private function log_invalid_donation_id( CoreDonation $donation ): void {

		$this->logger->error(
			'Donation row skipped because donation ID is invalid.',
			[
				'service_class' => self::class,
				'component' => 'donations_list',
				'layer' => 'integration',
				'system' => 'wordpress',
				'operation' => 'normalize_donation_id',
				'outcome' => 'invalid',
				'donation_id' => $donation->get_id(),
			],
		);
	}

	/**
	 * Logs a donation row skipped because its campaign ID is invalid.
	 *
	 * @since 1.0.0
	 *
	 * @param CoreDonation $donation Donation read model.
	 */
	private function log_invalid_campaign_id( CoreDonation $donation ): void {

		$this->logger->error(
			'Donation row skipped because campaign ID is invalid.',
			[
				'service_class' => self::class,
				'component' => 'donations_list',
				'layer' => 'integration',
				'system' => 'wordpress',
				'operation' => 'normalize_campaign_id',
				'outcome' => 'invalid',
				'donation_id' => (string) $donation->get_id(),
				'campaign_id' => $donation->get_campaign_id(),
			],
		);
	}

	/**
	 * Logs a donation row skipped because its campaign could not be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param CoreDonation $donation Donation read model.
	 * @param int $campaign_id Campaign ID.
	 */
	private function log_missing_campaign( CoreDonation $donation, int $campaign_id ): void {

		$this->logger->error(
			'Donation row skipped because campaign could not be loaded.',
			[
				'service_class' => self::class,
				'component' => 'donations_list',
				'layer' => 'integration',
				'system' => 'wordpress',
				'operation' => 'resolve_campaign',
				'outcome' => 'missing',
				'donation_id' => (string) $donation->get_id(),
				'campaign_id' => $campaign_id,
			],
		);
	}
}
