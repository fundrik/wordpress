<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigRegistry;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( PostTypeConfigRegistry::class )]
final class PostTypeConfigRegistryTest extends FundrikTestCase {

	#[Test]
	public function it_exposes_expected_post_type_config_classes(): void {

		$registry = new PostTypeConfigRegistry();

		self::assertSame(
			[
				CampaignPostTypeConfig::class,
			],
			$registry->get_post_type_config_classes(),
		);
	}
}
