<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Storage;

use Fundrik\WordPress\Infrastructure\Ports\Storage\StorageNotFoundExceptionInterface;
use RuntimeException;

/**
 * Thrown when a WordPress option is missing in storage.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class WordPressOptionNotFoundException extends RuntimeException implements StorageNotFoundExceptionInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Missing option key.
	 */
	public function __construct( string $key ) {

		parent::__construct(
			sprintf( 'Cannot read option "%s": option not found.', $key ),
		);
	}
}
