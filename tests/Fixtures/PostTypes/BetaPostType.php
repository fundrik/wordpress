<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeId;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSpecificBlock;
use Fundrik\WordPress\Integration\PostTypes\PostTypeInterface;

#[PostTypeId( 'beta' )]
#[PostTypeSpecificBlock( 'fundrik/beta-only' )]
#[PostTypeSpecificBlock( 'fundrik/shared' )]
final class BetaPostType implements PostTypeInterface {

	public function get_labels(): array {

		return [];
	}
}
