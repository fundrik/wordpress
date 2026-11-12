<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( SettingOptionName::class )]
final class SettingOptionNameTest extends FundrikTestCase {

	#[Test]
	public function create_returns_option_name_for_scope_and_setting(): void {

		self::assertSame(
			'fundrik_donation_form_default_amount_setting',
			SettingOptionName::create( 'donation_form', 'default_amount' ),
		);
	}
}
