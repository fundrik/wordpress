<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeId;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSpecificBlock;

#[PostTypeId( 'beta' )]
#[PostTypeSpecificBlock( 'fundrik/beta-only' )]
#[PostTypeSpecificBlock( 'fundrik/shared' )]
final class BetaPostType {}  // phpcs:ignore
