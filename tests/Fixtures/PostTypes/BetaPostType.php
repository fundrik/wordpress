<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeId;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSpecificBlock;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\PostTypeInterface;

#[PostTypeId( 'beta' )]
#[PostTypeSpecificBlock( 'fundrik/beta-only' )]
#[PostTypeSpecificBlock( 'fundrik/shared' )]
final class BetaPostType implements PostTypeInterface {

	public function get_labels(): array {

		return [];
	}
}
