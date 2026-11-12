<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Fundrik\WordPress\Integration\Helpers\PluginUrl;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( PluginUrl::class )]
final class PluginUrlTest extends FundrikTestCase {

	#[Test]
	public function cases_define_expected_plugin_resource_urls(): void {

		self::assertSame( 'assets/', PluginUrl::Assets->value );
		self::assertSame( 'assets/js/', PluginUrl::JavaScripts->value );
		self::assertSame( 'assets/js/blocks/', PluginUrl::Blocks->value );
	}

	#[Test]
	public function get_full_url_resolves_resource_relative_to_plugin_root(): void {

		self::assertSame(
			FUNDRIK_URL . 'assets/js/',
			PluginUrl::JavaScripts->get_full_url(),
		);
	}

	#[Test]
	public function file_resolves_file_within_resource_url(): void {

		self::assertSame(
			FUNDRIK_URL . 'assets/js/app.js',
			PluginUrl::JavaScripts->file( 'app.js' ),
		);
	}
}
