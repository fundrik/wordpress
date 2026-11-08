<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\Groups\CheckoutSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutCancelUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutSuccessUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\SelectedGatewaySetting;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CheckoutSettingsGroup::class )]
#[UsesClass( CheckoutCancelUrlSetting::class )]
#[UsesClass( CheckoutSuccessUrlSetting::class )]
#[UsesClass( SelectedGatewaySetting::class )]
final class CheckoutSettingsGroupTest extends WordPressTestCase {

	private CheckoutCancelUrlSetting $cancel_url_setting;
	private CheckoutSettingsGroup $group;
	private SelectedGatewaySetting $selected_gateway_setting;
	private CheckoutSuccessUrlSetting $success_url_setting;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->selected_gateway_setting = new SelectedGatewaySetting();
		$this->success_url_setting = new CheckoutSuccessUrlSetting();
		$this->cancel_url_setting = new CheckoutCancelUrlSetting();
		$this->group = new CheckoutSettingsGroup(
			$this->selected_gateway_setting,
			$this->success_url_setting,
			$this->cancel_url_setting,
		);
	}

	#[Test]
	public function get_id_returns_checkout(): void {

		self::assertSame( 'checkout', $this->group->get_id() );
	}

	#[Test]
	public function get_section_title_returns_checkout(): void {

		self::assertSame( 'Checkout', $this->group->get_section_title() );
	}

	#[Test]
	public function render_section_description_outputs_checkout_description(): void {

		ob_start();
		$this->group->render_section_description();
		$output = ob_get_clean();

		self::assertSame( '<p>Configure checkout settings.</p>', $output );
	}

	#[Test]
	public function get_settings_returns_checkout_settings_in_registration_order(): void {

		self::assertSame(
			[
				$this->selected_gateway_setting,
				$this->success_url_setting,
				$this->cancel_url_setting,
			],
			$this->group->get_settings(),
		);
	}
}
