<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Infrastructure\Integration\MetaFieldType;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeBlockTemplate;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeId;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeMetaField;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSlug;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSpecificBlock;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\PostTypeInterface;

#[PostTypeId( 'alpha' )]
#[PostTypeSlug( 'alphas' )]
#[PostTypeBlockTemplate( [ [ 'core/paragraph' ] ] )]
#[PostTypeSpecificBlock( 'fundrik/alpha-only' )]
#[PostTypeSpecificBlock( 'fundrik/shared' )]
final class AlphaPostType implements PostTypeInterface {

	#[PostTypeMetaField( type: MetaFieldType::Boolean, default: true )]
	public const META_HAS_NESTED = 'alpha_has_nested';

	public function get_labels(): array {

		return [];
	}
}
