<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Settings\Campaign;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignDefaultAcceptsDonationsSetting::class )]
#[UsesClass( SettingFieldRenderer::class )]
final class CampaignDefaultAcceptsDonationsSettingTest extends WordPressTestCase {

	private CampaignDefaultAcceptsDonationsSetting $setting;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->setting = new CampaignDefaultAcceptsDonationsSetting();
	}

	#[Test]
	public function get_id_returns_default_accepts_donations(): void {

		self::assertSame( 'default_accepts_donations', $this->setting->get_id() );
	}

	#[Test]
	public function get_label_returns_default_accepts_donations_label(): void {

		self::assertSame( 'Default accepts donations', $this->setting->get_label() );
	}

	#[Test]
	public function get_default_value_returns_true(): void {

		self::assertTrue( $this->setting->get_default_value() );
	}

	#[Test]
	public function get_value_type_returns_boolean_type(): void {

		self::assertSame( WpSchemaType::Boolean, $this->setting->get_value_type() );
	}

	#[Test]
	#[DataProvider( 'supported_value_cases' )]
	public function sanitize_value_returns_boolean_for_supported_value(
		bool|int|string $value,
		bool $expected,
	): void {

		self::assertSame( $expected, $this->setting->sanitize_value( $value ) );
	}

	#[Test]
	#[DataProvider( 'unsupported_value_cases' )]
	public function sanitize_value_throws_for_unsupported_value( int|string $value ): void {

		$this->expectException( InvalidArgumentException::class );

		$this->setting->sanitize_value( $value );
	}

	#[Test]
	#[DataProvider( 'render_value_cases' )]
	public function render_outputs_accepts_donations_checkbox( bool $value, string $checked_attribute ): void {

		Functions\expect( 'checked' )
			->once()
			->with( $value, true, false )
			->andReturn( $checked_attribute );

		ob_start();
		$this->setting->render(
			[
				'field_name' => 'fundrik_campaign_default_accepts_donations',
				'input_id' => 'fundrik_campaign_default_accepts_donations',
				'value' => $value,
			],
		);
		$output = ob_get_clean();

		self::assertSame(
			'<input type="hidden" name="fundrik_campaign_default_accepts_donations" value="0">'
			. '<label for="fundrik_campaign_default_accepts_donations">'
			. '<input type="checkbox" name="fundrik_campaign_default_accepts_donations"'
			. ' id="fundrik_campaign_default_accepts_donations" value="1"'
			. $checked_attribute
			. '> New campaigns accept donations.</label>',
			$output,
		);
	}

	/**
	 * Returns supported boolean value cases.
	 *
	 * @return iterable<string, array{bool|int|string, bool}> Supported value cases.
	 */
	public static function supported_value_cases(): iterable {

		yield 'true' => [ true, true ];
		yield 'false' => [ false, false ];
		yield 'integer one' => [ 1, true ];
		yield 'integer zero' => [ 0, false ];
		yield 'string one' => [ '1', true ];
		yield 'string zero' => [ '0', false ];
	}

	/**
	 * Returns unsupported boolean value cases.
	 *
	 * @return iterable<string, array{int|string}> Unsupported value cases.
	 */
	public static function unsupported_value_cases(): iterable {

		yield 'integer outside boolean range' => [ 2 ];
		yield 'boolean word' => [ 'true' ];
	}

	/**
	 * Returns checkbox rendering cases.
	 *
	 * @return iterable<string, array{bool, string}> Checkbox rendering cases.
	 */
	public static function render_value_cases(): iterable {

		yield 'checked' => [ true, ' checked="checked"' ];
		yield 'unchecked' => [ false, '' ];
	}
}
