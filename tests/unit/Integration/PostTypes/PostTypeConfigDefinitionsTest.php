<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigDefinitions;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( PostTypeConfigDefinitions::class )]
final class PostTypeConfigDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function classes_returns_configured_post_type_classes(): void {

		self::assertSame(
			[ CampaignPostTypeConfig::class ],
			PostTypeConfigDefinitions::classes(),
		);
	}
}
