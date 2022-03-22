<?php

declare(strict_types=1);

namespace Baraja\Console;


final class ConsoleColor
{
	private const
		FOREGROUND = 38,
		BACKGROUND = 48,
		COLOR256_REGEXP = '~^(bg_)?color_(\d{1,3})$~',
		RESET_STYLE = 0;

	private bool $forceStyle = false;

	/** @var string[]|null[] */
	private array $styles = [
		'none' => null,
		'bold' => '1',
		'dark' => '2',
		'italic' => '3',
		'underline' => '4',
		'blink' => '5',
		'reverse' => '7',
		'concealed' => '8',
		'default' => '39',
		'black' => '30',
		'red' => '31',
		'green' => '32',
		'yellow' => '33',
		'blue' => '34',
		'magenta' => '35',
		'cyan' => '36',
		'light_gray' => '37',
		'dark_gray' => '90',
		'light_red' => '91',
		'light_green' => '92',
		'light_yellow' => '93',
		'light_blue' => '94',
		'light_magenta' => '95',
		'light_cyan' => '96',
		'white' => '97',
		'bg_default' => '49',
		'bg_black' => '40',
		'bg_red' => '41',
		'bg_green' => '42',
		'bg_yellow' => '43',
		'bg_blue' => '44',
		'bg_magenta' => '45',
		'bg_cyan' => '46',
		'bg_light_gray' => '47',
		'bg_dark_gray' => '100',
		'bg_light_red' => '101',
		'bg_light_green' => '102',
		'bg_light_yellow' => '103',
		'bg_light_blue' => '104',
		'bg_light_magenta' => '105',
		'bg_light_cyan' => '106',
		'bg_white' => '107',
	];

	/** @var array<string, array<int, string>> */
	private array $themes = [];


	/**
	 * @param string|array<int, string> $style
	 */
	public function apply(string|array $style, string $text): string
	{
		if (!$this->isStyleForced() && !$this->isSupported()) {
			return $text;
		}
		if (is_string($style)) {
			$style = [$style];
		}

		$sequences = [];
		foreach ($style as $s) {
			if (isset($this->themes[$s])) {
				$sequences = array_merge($sequences, $this->themeSequence($s));
			} elseif ($this->isValidStyle($s)) {
				$sequences[] = $this->styleSequence($s);
			} else {
				throw new \InvalidArgumentException(sprintf('Invalid style "%s".', $s));
			}
		}

		$sequences = array_filter($sequences, static fn(mixed $val): bool => $val !== null);
		if ($sequences === []) {
			return $text;
		}

		return $this->escSequence(implode(';', $sequences)) . $text . $this->escSequence(self::RESET_STYLE);
	}


	public function setForceStyle(bool $forceStyle): void
	{
		$this->forceStyle = $forceStyle;
	}


	public function isStyleForced(): bool
	{
		return $this->forceStyle;
	}


	/**
	 * @param string|string[] $styles
	 */
	public function addTheme(string $name, string|array $styles): void
	{
		if (is_string($styles)) {
			$styles = [$styles];
		}
		foreach ($styles as $style) {
			if (!$this->isValidStyle($style)) {
				throw new \InvalidArgumentException('Invalid style ' . $style);
			}
		}

		$this->themes[$name] = $styles;
	}


	public function hasTheme(string $name): bool
	{
		return isset($this->themes[$name]);
	}


	public function removeTheme(string $name): void
	{
		unset($this->themes[$name]);
	}


	public function isSupported(): bool
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			if (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT)) {
				return true;
			}
			if (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON') {
				return true;
			}

			return false;
		}

		return function_exists('posix_isatty') && @posix_isatty(STDOUT);
	}


	public function are256ColorsSupported(): bool
	{
		return DIRECTORY_SEPARATOR === '\\'
			? function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT)
			: str_contains((string) getenv('TERM'), '256color');
	}


	/**
	 * @return array<int, string|null>
	 */
	private function themeSequence(string $name): array
	{
		$sequences = [];
		foreach ($this->themes[$name] as $style) {
			$sequences[] = $this->styleSequence($style);
		}

		return $sequences;
	}


	private function styleSequence(string $style): ?string
	{
		if (array_key_exists($style, $this->styles)) {
			return $this->styles[$style];
		}
		if (!$this->are256ColorsSupported()) {
			return null;
		}

		if (preg_match(self::COLOR256_REGEXP, $style, $matches) === 1) {
			$type = $matches[1] === 'bg_' ? self::BACKGROUND : self::FOREGROUND;
			$value = $matches[2];

			return $type . ';5;' . $value;
		}

		return null;
	}


	private function isValidStyle(string $style): bool
	{
		return array_key_exists($style, $this->styles) || preg_match(self::COLOR256_REGEXP, $style) === 1;
	}


	private function escSequence(string|int $value): string
	{
		return "\033[{$value}m";
	}
}
