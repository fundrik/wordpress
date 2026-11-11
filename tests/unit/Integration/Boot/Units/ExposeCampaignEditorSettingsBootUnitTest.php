<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountSetting;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\ExposeCampaignEditorSettingsBootUnit;
use Fundrik\WordPress\Integration\Helpers\CurrentAdminScreen;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpScreen;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( ExposeCampaignEditorSettingsBootUnit::class )]
#[UsesClass( AdminSettingsReader::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( CampaignDefaultAcceptsDonationsSetting::class )]
#[UsesClass( CampaignDefaultHasTargetSetting::class )]
#[UsesClass( CampaignPostTypeConfig::class )]
#[UsesClass( CampaignSettingsGroup::class )]
#[UsesClass( CurrentAdminScreen::class )]
#[UsesClass( DonationFormDefaultAmountLabelSetting::class )]
#[UsesClass( DonationFormDefaultAmountSetting::class )]
#[UsesClass( DonationFormSettingsGroup::class )]
#[UsesClass( EnqueueBlockEditorAssetsActionHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( Logger::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( SettingOptionName::class )]
final class ExposeCampaignEditorSettingsBootUnitTest extends WordPressTestCase {

	private ExposeCampaignEditorSettingsBootUnit $boot_unit;
	private EnqueueBlockEditorAssetsActionHookDispatcher $hook_dispatcher;
	private FakeStorage $storage;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$psr_logger = Mockery::mock( LoggerInterface::class );
		$this->hook_dispatcher = new EnqueueBlockEditorAssetsActionHookDispatcher(
			new HookDispatcherLogger( $psr_logger ),
		);
		$this->storage = new FakeStorage(
			[
				'fundrik_donation_form_default_amount_setting' => 25,
				'fundrik_donation_form_default_amount_label_setting' => 'Contribution',
				'fundrik_campaign_default_accepts_donations_setting' => true,
				'fundrik_campaign_default_has_target_setting' => false,
			],
		);
		$this->boot_unit = new ExposeCampaignEditorSettingsBootUnit(
			$this->hook_dispatcher,
			$this->make_settings_reader(),
			new BootUnitLogger( $psr_logger ),
		);
	}

	#[Test]
	public function boot_ignores_non_campaign_screen(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( new WpScreen( 'post' ) );
		Functions\expect( 'wp_json_encode' )->never();
		Functions\expect( 'wp_add_inline_script' )->never();

		$this->boot_unit->boot();
		$this->hook_dispatcher->dispatch();
	}

	#[Test]
	public function boot_exposes_editor_settings_for_campaign_screen(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( new WpScreen( CampaignPostTypeConfig::ID ) );

		$this->stub_wp_json_encode();

		Functions\expect( 'wp_add_inline_script' )
			->once()
			->with(
				'fundrik-donation-form-editor-script',
				'window.fundrikDonationFormEditorSettings = {"defaultAmount":25,"defaultAmountLabel":"Contribution"};',
				'before',
			);

		Functions\expect( 'wp_add_inline_script' )
			->once()
			->with(
				'fundrik-campaign-settings-editor-script',
				'window.fundrikCampaignEditorSettings = {"defaultAcceptsDonations":true,"defaultHasTarget":false};',
				'before',
			);

		Functions\expect( 'wp_add_inline_script' )
			->once()
			->with(
				'fundrik-campaign-summary-editor-script',
				'window.fundrikCampaignEditorSettings = {"defaultAcceptsDonations":true,"defaultHasTarget":false};',
				'before',
			);

		$this->boot_unit->boot();
		$this->hook_dispatcher->dispatch();
	}

	#[Test]
	public function boot_reports_failure_when_editor_settings_cannot_be_encoded(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( new WpScreen( CampaignPostTypeConfig::ID ) );

		Functions\expect( 'wp_json_encode' )
			->once()
			->with(
				[
					'defaultAmount' => 25,
					'defaultAmountLabel' => 'Contribution',
				],
			)
			->andReturn( false );

		Functions\expect( 'wp_add_inline_script' )->never();

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Failed to encode campaign editor settings payload "fundrikDonationFormEditorSettings".' );

		$this->boot_unit->boot();
		$this->hook_dispatcher->dispatch();
	}

	private function make_settings_reader(): AdminSettingsReader {

		return new AdminSettingsReader(
			new OptionReader( $this->storage ),
			new CampaignSettingsGroup(
				new CampaignDefaultAcceptsDonationsSetting(),
				new CampaignDefaultHasTargetSetting(),
			),
			new DonationFormSettingsGroup(
				new DonationFormDefaultAmountSetting(),
				new DonationFormDefaultAmountLabelSetting(),
			),
		);
	}
}
