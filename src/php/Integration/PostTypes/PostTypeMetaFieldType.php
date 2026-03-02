<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

/**
 * Defines the allowed data types for WordPress meta fields.
 *
 * @since 1.0.0
 *
 * @internal
 */
enum PostTypeMetaFieldType: string {

	case String = 'string';
	case Boolean = 'boolean';
	case Integer = 'integer';
	case Number = 'number';
	case Array = 'array';
	case Object = 'object';
}
