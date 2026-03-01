<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionProperty;

abstract class FundrikTestCase extends PHPUnitTestCase {

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
		} elseif ( $target_type === 'class_constant' ) {
			$reflection = new ReflectionClassConstant( $class_name, $target_name );
		} else {
			throw new InvalidArgumentException( 'Invalid target type. Use "property", "class", or "class_constant".' );
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
					'Expected value "%s" for property "%s" on attribute "%s", got "%s".',
					$this->debug_value( $expected ),
					$property,
					$attribute_class,
					$this->debug_value( $actual ),
				),
			);
		}
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

	protected function assert_class_constant_has_attribute(
		string $class_name,
		string $constant_name,
		string $attribute_class,
		?array $expected_values = null,
	): void {

		$this->assert_has_attribute_instance_of(
			class_name: $class_name,
			target_name: $constant_name,
			attribute_class: $attribute_class,
			expected_values: $expected_values,
			target_type: 'class_constant',
		);
	}

	/**
	 * Converts the value into a readable string for assertion messages.
	 *
	 * @param mixed $value The value to format.
	 *
	 * @return string The formatted value.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function debug_value( mixed $value ): string {

		if ( $value === null ) {
			return 'null';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		if ( $value instanceof \BackedEnum ) {
			return sprintf( '%s::%s(%s)', $value::class, $value->name, (string) $value->value );
		}

		if ( $value instanceof \UnitEnum ) {
			return sprintf( '%s::%s', $value::class, $value->name );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		return sprintf( '%s(%s)', get_debug_type( $value ), json_encode( $value ) ?: 'unserializable' );
	}
}
