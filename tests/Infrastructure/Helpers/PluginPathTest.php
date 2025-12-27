<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Helpers;

use Fundrik\WordPress\Infrastructure\Helpers\PluginPath;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( PluginPath::class )]
final class PluginPathTest extends FundrikTestCase {

	#[Test]
	public function blocks_path_resolves_relative_to_plugin_root(): void {

		$this->assertSame(
			FUNDRIK_PATH . 'assets/js/blocks/',
			PluginPath::Blocks->get_full_path(),
		);
	}

	#[Test]
	public function blocks_manifest_path_resolves_relative_to_plugin_root(): void {

		$this->assertSame(
			FUNDRIK_PATH . 'assets/js/blocks/blocks-manifest.php',
			PluginPath::BlocksManifest->get_full_path(),
		);
	}
}
