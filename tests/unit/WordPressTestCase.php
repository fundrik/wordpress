<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class WordPressTestCase extends FundrikTestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {

		parent::setUp();

		Monkey\setUp();

		Monkey\Functions\stubEscapeFunctions();
		Monkey\Functions\stubTranslationFunctions();
	}

	protected function tearDown(): void {

		Monkey\tearDown();

		parent::tearDown();
	}
}
