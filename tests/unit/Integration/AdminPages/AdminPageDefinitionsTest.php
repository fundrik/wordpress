<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\Pages\SettingsAdminPage;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AdminPageDefinitions::class )]
final class AdminPageDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function it_exposes_expected_admin_page_classes(): void {

		$this->assertSame(
			[
				SettingsAdminPage::class,
			],
			AdminPageDefinitions::classes(),
		);
	}
}
