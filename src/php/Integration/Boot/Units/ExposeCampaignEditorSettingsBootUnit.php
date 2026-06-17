<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Helpers\CurrentAdminScreen;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use LogicException;
use Override;

/**
 * Exposes campaign editor settings to the campaign block editor preview.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class ExposeCampaignEditorSettingsBootUnit implements BootUnitInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.Commenting.DocCommentSpacing.IncorrectLinesCountBetweenDifferentAnnotationsTypes
	 * @param EnqueueBlockEditorAssetsActionHookDispatcher $enqueue_block_editor_assets_hook Dispatches the block editor assets action.
	 * @param AdminSettingsReader $settings_reader Provides resolved admin settings values.
	 * @param BootUnitLogger $logger Writes structured log entries.
	 */
	public function __construct(
		private EnqueueBlockEditorAssetsActionHookDispatcher $enqueue_block_editor_assets_hook,
		private AdminSettingsReader $settings_reader,
		private BootUnitLogger $logger,
	) {

		$this->logger->set_boot_unit_class( self::class );
	}

	/**
	 * Attaches the callback that exposes campaign editor settings.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function boot(): void {

		$this->enqueue_block_editor_assets_hook->attach( $this->expose_editor_settings( ... ) );
	}

	/**
	 * Exposes admin settings to the block editor scripts.
	 *
	 * @since 1.0.0
	 *
	 * @todo Decide whether editor settings should be emitted once globally instead of per script handle.
	 */
	private function expose_editor_settings(): void {

		if ( ! CurrentAdminScreen::is_post_type( CampaignPostTypeConfig::ID ) ) {
			return;
		}

		$this->add_editor_settings_inline_script(
			'fundrik-donation-form-editor-script',
			'fundrikDonationFormEditorSettings',
			$this->get_donation_form_editor_settings(),
		);

		$this->add_editor_settings_inline_script(
			'fundrik-campaign-settings-editor-script',
			'fundrikCampaignEditorSettings',
			$this->get_campaign_editor_settings(),
		);

		$this->add_editor_settings_inline_script(
			'fundrik-campaign-summary-editor-script',
			'fundrikCampaignEditorSettings',
			$this->get_campaign_editor_settings(),
		);
	}

	/**
	 * Adds the editor settings object before the target editor script.
	 *
	 * @since 1.0.0
	 *
	 * @param string $script_handle Target script handle.
	 * @param string $object_name Global settings object name.
	 * @param array<string, bool|int|string> $settings Editor settings payload.
	 *
	 * @throws LogicException When the settings payload cannot be encoded to JSON.
	 */
	private function add_editor_settings_inline_script(
		string $script_handle,
		string $object_name,
		array $settings,
	): void {

		$encoded_settings = wp_json_encode( $settings );

		if ( $encoded_settings === false ) {
			throw new LogicException(
				sprintf( 'Failed to encode campaign editor settings payload "%s".', $object_name ),
			);
		}

		wp_add_inline_script(
			$script_handle,
			sprintf(
				'window.%s = %s;',
				$object_name,
				$encoded_settings,
			),
			'before',
		);
	}

	/**
	 * Returns donation form settings for the editor preview.
	 *
	 * @since 1.0.0
	 *
	 * @return array{defaultAmount: int, defaultAmountLabel: string} Donation form preview settings.
	 */
	private function get_donation_form_editor_settings(): array {

		return [
			'defaultAmount' => $this->settings_reader->get_donation_form_default_amount(),
			'defaultAmountLabel' => $this->settings_reader->get_donation_form_default_amount_label(),
		];
	}

	/**
	 * Returns campaign defaults for the editor preview.
	 *
	 * @since 1.0.0
	 *
	 * @return array{defaultAcceptsDonations: bool, defaultHasTarget: bool} Campaign preview defaults.
	 */
	private function get_campaign_editor_settings(): array {

		return [
			'defaultAcceptsDonations' => $this->settings_reader->get_campaign_default_accepts_donations(),
			'defaultHasTarget' => $this->settings_reader->get_campaign_default_has_target(),
		];
	}
}
