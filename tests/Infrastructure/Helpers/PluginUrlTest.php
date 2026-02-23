<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Helpers;

use Fundrik\WordPress\Infrastructure\Helpers\PluginUrl;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( PluginUrl::class )]
final class PluginUrlTest extends FundrikTestCase {

	#[Test]
	public function assets_url_resolves_relative_to_plugin_root(): void {

		$this->assertSame(
			FUNDRIK_URL . 'assets/',
			PluginUrl::Assets->get_full_url(),
		);
	}

	#[Test]
	public function blocks_url_resolves_relative_to_plugin_root(): void {

		$this->assertSame(
			FUNDRIK_URL . 'assets/js/blocks/',
			PluginUrl::Blocks->get_full_url(),
		);
	}

	#[Test]
	public function javascripts_url_resolves_relative_to_plugin_root(): void {

		$this->assertSame(
			FUNDRIK_URL . 'assets/js/',
			PluginUrl::JavaScripts->get_full_url(),
		);
	}

	#[Test]
	public function file_url_is_resolved_within_the_resource_path(): void {

		$this->assertSame(
			FUNDRIK_URL . 'assets/js/app.js',
			PluginUrl::JavaScripts->file( 'app.js' ),
		);
	}

	#[Test]
	public function file_url_supports_nested_paths(): void {

		$this->assertSame(
			FUNDRIK_URL . 'assets/js/build/app.js',
			PluginUrl::JavaScripts->file( 'build/app.js' ),
		);
	}
}
