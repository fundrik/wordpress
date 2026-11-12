<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Brain\Monkey\Functions;

/**
 * Records WordPress Settings API registrations for tests.
 */
final class FakeWordPressSettingsApi {

	/**
	 * Registered settings sections keyed by ID.
	 *
	 * @var array<string, array{title: string, callback: callable, page: string}>
	 */
	private array $sections = [];

	/**
	 * Registered settings fields keyed by ID.
	 *
	 * @var array<string, array{title: string, callback: callable, page: string, section: string, args: array<string, mixed>}>
	 */
	private array $fields = [];

	/**
	 * Registered settings keyed by option name.
	 *
	 * @var array<string, array{group: string, args: array<string, mixed>}>
	 */
	private array $settings = [];

	/**
	 * Registered settings errors.
	 *
	 * @var list<array{setting: string, code: string, message: string, type: string}>
	 */
	private array $errors = [];

	/**
	 * Stubs the WordPress Settings API functions.
	 */
	public function stub(): void {

		Functions\when( 'add_settings_section' )->alias( $this->add_section( ... ) );
		Functions\when( 'add_settings_field' )->alias( $this->add_field( ... ) );
		Functions\when( 'register_setting' )->alias( $this->register_setting( ... ) );
		Functions\when( 'add_settings_error' )->alias( $this->add_error( ... ) );
	}

	/**
	 * Returns a registered settings section.
	 *
	 * @return array{title: string, callback: callable, page: string} Section registration.
	 */
	public function get_section( string $id ): array {

		return $this->sections[ $id ];
	}

	/**
	 * Returns a registered settings field.
	 *
	 * @return array{title: string, callback: callable, page: string, section: string, args: array<string, mixed>} Field registration.
	 */
	public function get_field( string $id ): array {

		return $this->fields[ $id ];
	}

	/**
	 * Returns a registered setting.
	 *
	 * @return array{group: string, args: array<string, mixed>} Setting registration.
	 */
	public function get_setting( string $option_name ): array {

		return $this->settings[ $option_name ];
	}

	/**
	 * Returns registered settings errors.
	 *
	 * @return list<array{setting: string, code: string, message: string, type: string}> Settings errors.
	 */
	public function get_errors(): array {

		return $this->errors;
	}

	/**
	 * Records a settings section.
	 */
	private function add_section( string $id, string $title, callable $callback, string $page ): void {

		$this->sections[ $id ] = [
			'title' => $title,
			'callback' => $callback,
			'page' => $page,
		];
	}

	/**
	 * Records a settings field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	private function add_field(
		string $id,
		string $title,
		callable $callback,
		string $page,
		string $section,
		array $args,
	): void {

		$this->fields[ $id ] = [
			'title' => $title,
			'callback' => $callback,
			'page' => $page,
			'section' => $section,
			'args' => $args,
		];
	}

	/**
	 * Records a setting.
	 *
	 * @param array<string, mixed> $args Setting arguments.
	 */
	private function register_setting( string $group, string $option_name, array $args ): void {

		$this->settings[ $option_name ] = [
			'group' => $group,
			'args' => $args,
		];
	}

	/**
	 * Records a settings error.
	 */
	private function add_error( string $setting, string $code, string $message, string $type = 'error' ): void {

		$this->errors[] = [
			'setting' => $setting,
			'code' => $code,
			'message' => $message,
			'type' => $type,
		];
	}
}
