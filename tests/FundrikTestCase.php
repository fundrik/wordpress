<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionClass;
use ReflectionProperty;

abstract class FundrikTestCase extends PHPUnitTestCase {

	/**
	 * Returns a valid Campaign for use in tests.
	 * Allows overriding fields to simulate variations.
	 */
	protected function make_campaign(
		int|string $id = 1,
		int $version = 1,
		string $title = 'Test Campaign',
		bool $is_active = true,
		bool $is_open = true,
		bool $has_target = true,
		int $target_amount = 100,
	): Campaign {

		return new Campaign(
			id: EntityId::create( $id ),
			version: EntityVersion::create( $version ),
			title: CampaignTitle::create( $title ),
			is_active: $is_active,
			is_open: $is_open,
			target: CampaignTarget::create( $has_target, $target_amount ),
		);
	}

	protected function assert_has_attribute_instance_of(
		string $class_name,
		string $target_name,
		string $attribute_class,
		?array $expected_values = null,
		string $target_type = 'property',
	): void {

		if ( $target_type === 'property' ) {
			$reflection = new ReflectionProperty( $class_name, $target_name );
		} elseif ( $target_type === 'class' ) {
			$reflection = new ReflectionClass( $class_name );
		} else {
			throw new InvalidArgumentException( 'Invalid target type. Use "property" or "class".' );
		}

		$attributes = $reflection->getAttributes( $attribute_class );

		Assert::assertCount(
			1,
			$attributes,
			sprintf(
				'%s "%s" of class "%s" must have the "%s" attribute.',
				ucfirst( $target_type ),
				$target_name,
				$class_name,
				$attribute_class,
			),
		);

		$instance = $attributes[0]->newInstance();

		Assert::assertInstanceOf(
			$attribute_class,
			$instance,
			sprintf(
				'Expected instance of "%s" for %s "%s", got "%s"',
				$attribute_class,
				$target_type,
				$target_name,
				$instance::class,
			),
		);

		if ( ! $expected_values ) {
			return;
		}

		foreach ( $expected_values as $property => $expected ) {
			$actual = $instance->$property ?? null;

			Assert::assertSame(
				$expected,
				$actual,
				sprintf(
					'Expected value "%s" for property "%s" on attribute "%s", got "%s"',
					$expected,
					$property,
					$attribute_class,
					$actual,
				),
			);
		}
	}

	protected function assert_property_has_attribute(
		string $class_name,
		string $property_name,
		string $attribute_class,
		?array $expected_values = null,
	): void {

		$this->assert_has_attribute_instance_of(
			class_name: $class_name,
			target_name: $property_name,
			attribute_class: $attribute_class,
			expected_values: $expected_values,
			target_type: 'property',
		);
	}

	protected function assert_class_has_attribute(
		string $class_name,
		string $attribute_class,
		?array $expected_values = null,
	): void {

		$this->assert_has_attribute_instance_of(
			class_name: $class_name,
			target_name: $class_name,
			attribute_class: $attribute_class,
			expected_values: $expected_values,
			target_type: 'class',
		);
	}
}
