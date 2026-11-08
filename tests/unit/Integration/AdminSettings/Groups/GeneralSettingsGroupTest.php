<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( GeneralSettingsGroup::class )]
#[UsesClass( CurrencySetting::class )]
final class GeneralSettingsGroupTest extends WordPressTestCase {

	private CurrencySetting $currency_setting;
	private GeneralSettingsGroup $group;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->currency_setting = new CurrencySetting();
		$this->group = new GeneralSettingsGroup( $this->currency_setting );
	}

	#[Test]
	public function get_id_returns_general(): void {

		self::assertSame( 'general', $this->group->get_id() );
	}

	#[Test]
	public function get_section_title_returns_general(): void {

		self::assertSame( 'General', $this->group->get_section_title() );
	}

	#[Test]
	public function render_section_description_outputs_general_description(): void {

		ob_start();
		$this->group->render_section_description();
		$output = ob_get_clean();

		self::assertSame( '<p>Configure global Fundrik settings.</p>', $output );
	}

	#[Test]
	public function get_settings_returns_currency_setting(): void {

		self::assertSame( [ $this->currency_setting ], $this->group->get_settings() );
	}
}
