<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration;

/**
 * Defines the allowed WordPress schema types.
 *
 * @since 1.0.0
 *
 * @internal
 */
enum WpSchemaType: string {

	case String = 'string';
	case Boolean = 'boolean';
	case Integer = 'integer';
	case Number = 'number';
	case Array = 'array';
	case Object = 'object';
}
