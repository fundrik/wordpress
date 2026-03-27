<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot;

use Fundrik\WordPress\Kernel\Ports\BootUnitRunnerPort;
use Override;

/**
 * Boots all configured WordPress integration boot units.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class BootUnitRunner implements BootUnitRunnerPort {

	/**
	 * The configured boot units.
	 *
	 * @var list<BootUnitInterface>
	 */
	private array $boot_units;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param BootUnitInterface ...$boot_units The boot units to run.
	 */
	public function __construct( BootUnitInterface ...$boot_units ) {

		$this->boot_units = $boot_units;
	}

	/**
	 * Boot all configured boot units.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function boot_all(): void {

		foreach ( $this->boot_units as $boot_unit ) {
			$boot_unit->boot();
		}
	}
}
