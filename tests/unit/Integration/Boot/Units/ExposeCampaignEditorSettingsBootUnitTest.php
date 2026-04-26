<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Closure;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\ExposeCampaignEditorSettingsBootUnit;
use Fundrik\WordPress\Integration\Helpers\CurrentScreen;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use WP_Screen;

#[CoversClass( ExposeCampaignEditorSettingsBootUnit::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( EnqueueBlockEditorAssetsActionHookDispatcher::class )]
#[UsesClass( AdminSettingsReader::class )]
#[UsesClass( CampaignSettingsGroup::class )]
#[UsesClass( CampaignDefaultAcceptsDonationsSetting::class )]
#[UsesClass( CampaignDefaultHasTargetSetting::class )]
#[UsesClass( DonationFormSettingsGroup::class )]
#[UsesClass( GeneralSettingsGroup::class )]
#[UsesClass( DonationFormDefaultAmountSetting::class )]
#[UsesClass( DonationFormDefaultAmountLabelSetting::class )]
#[UsesClass( CurrencySetting::class )]
#[UsesClass( CurrentScreen::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( CampaignPostTypeConfig::class )]
final class ExposeCampaignEditorSettingsBootUnitTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'enqueue_block_editor_assets';

	private EnqueueBlockEditorAssetsActionHookDispatcher $enqueue_block_editor_assets_hook;
	private Closure $enqueue_block_editor_assets_callback;

	private LoggerInterface&MockInterface $psr_logger;
	private BootUnitLogger $logger;

	private ExposeCampaignEditorSettingsBootUnit $boot_unit;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		$hook_logger = new HookDispatcherLogger( $this->psr_logger );
		$this->enqueue_block_editor_assets_hook = new EnqueueBlockEditorAssetsActionHookDispatcher( $hook_logger );
		$this->enqueue_block_editor_assets_callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$this->enqueue_block_editor_assets_hook->register( ... ),
		);

		$this->logger = new BootUnitLogger( $this->psr_logger );

		$this->boot_unit = new ExposeCampaignEditorSettingsBootUnit(
			$this->enqueue_block_editor_assets_hook,
			$this->create_settings_reader( 25, 'Contribution', true, false ),
			$this->logger,
		);
	}

	#[Test]
	public function boot_does_not_expose_editor_settings_when_screen_is_missing(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( null );

		Functions\expect( 'wp_json_encode' )->never();
		Functions\expect( 'wp_add_inline_script' )->never();

		$this->boot_unit->boot();

		( $this->enqueue_block_editor_assets_callback )();
	}

	#[Test]
	public function boot_does_not_expose_editor_settings_for_non_campaign_post_type(): void {

		$screen = Mockery::mock( WP_Screen::class );
		$screen->post_type = 'post';

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( $screen );

		Functions\expect( 'wp_json_encode' )->never();
		Functions\expect( 'wp_add_inline_script' )->never();

		$this->boot_unit->boot();

		( $this->enqueue_block_editor_assets_callback )();
	}

	#[Test]
	public function boot_exposes_donation_form_editor_settings_for_campaign_post_type(): void {

		$screen = Mockery::mock( WP_Screen::class );
		$screen->post_type = CampaignPostTypeConfig::ID;

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( $screen );

		Functions\expect( 'wp_json_encode' )
			->once()
			->with(
				[
					'defaultAmount' => 25,
					'defaultAmountLabel' => 'Contribution',
				],
			)
			->andReturn( '{"defaultAmount":25,"defaultAmountLabel":"Contribution"}' );
		Functions\expect( 'wp_json_encode' )
			->once()
			->with(
				[
					'defaultAcceptsDonations' => true,
					'defaultHasTarget' => false,
				],
			)
			->andReturn( '{"defaultAcceptsDonations":true,"defaultHasTarget":false}' );

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

		$this->boot_unit->boot();

		( $this->enqueue_block_editor_assets_callback )();
	}

	#[Test]
	public function boot_sets_failure_message_when_editor_settings_encoding_fails(): void {

		$screen = Mockery::mock( WP_Screen::class );
		$screen->post_type = CampaignPostTypeConfig::ID;

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( $screen );

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

		( $this->enqueue_block_editor_assets_callback )();
	}

	private function create_settings_reader(
		int $default_amount,
		string $default_amount_label,
		bool $default_accepts_donations,
		bool $default_has_target,
	): AdminSettingsReader {

		$storage = Mockery::mock( StoragePort::class );
		$storage
			->shouldReceive( 'get' )
			->zeroOrMoreTimes()
			->with( 'fundrik_donation_form_default_amount_setting' )
			->andReturn( $default_amount );
		$storage
			->shouldReceive( 'get' )
			->zeroOrMoreTimes()
			->with( 'fundrik_donation_form_default_amount_label_setting' )
			->andReturn( $default_amount_label );
		$storage
			->shouldReceive( 'get' )
			->zeroOrMoreTimes()
			->with( 'fundrik_campaign_default_accepts_donations_setting' )
			->andReturn( $default_accepts_donations );
		$storage
			->shouldReceive( 'get' )
			->zeroOrMoreTimes()
			->with( 'fundrik_campaign_default_has_target_setting' )
			->andReturn( $default_has_target );

		$field_renderer = new AdminSettingsFieldRenderer();

		return new AdminSettingsReader(
			new OptionReader( $storage ),
			new GeneralSettingsGroup( new CurrencySetting( $field_renderer ) ),
			new CampaignSettingsGroup(
				new CampaignDefaultAcceptsDonationsSetting( $field_renderer ),
				new CampaignDefaultHasTargetSetting( $field_renderer ),
			),
			new DonationFormSettingsGroup(
				new DonationFormDefaultAmountSetting( $field_renderer ),
				new DonationFormDefaultAmountLabelSetting( $field_renderer ),
			),
		);
	}
}
