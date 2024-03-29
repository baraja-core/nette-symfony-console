<?php

declare(strict_types=1);

namespace Baraja\Console;


/** @internal */
final class Helpers
{
	/** @throws \Error */
	public function __construct()
	{
		throw new \Error('Class ' . static::class . ' is static and cannot be instantiated.');
	}


	/** Render code snippet to Terminal. */
	public static function terminalRenderCode(
		string $path,
		?int $markLine = null,
		int $lineContext = 8,
		bool $colorize = true,
	): void {
		if (PHP_SAPI !== 'cli') {
			throw new \RuntimeException('Terminal: This method is available only in CLI mode.');
		}
		echo "\n" . $path . ($markLine === null ? '' : ' [on line ' . $markLine . ']') . "\n\n";
		if (\is_file($path) === true) {
			echo '----- file -----' . "\n";
			$content = (string) file_get_contents($path);
			if ($colorize === true) {
				$content = (new ConsoleCodeHighlighter)->getWholeFile($content);
			}
			$fileParser = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));

			$limit = $lineContext * 2 - 1;
			for ($i = ($start = $markLine > $lineContext ? ($markLine ?? 0) - $lineContext : 0); $i <= $start + $limit; $i++) {
				if (isset($fileParser[$i]) === false) {
					break;
				}

				$currentLine = $i + 1;
				if ($markLine === $currentLine) { // highlight line
					$windowSize = (int) getenv('COLUMNS');
					echo "\e[1;37m\e[41m" . str_pad(' ' . $currentLine . ': ', 6, ' ');
					$line = str_replace("\t", '    ', (string) preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', '', $fileParser[$i]));
					if ($windowSize > 10) {
						echo str_pad($line, $windowSize > 500 ? 500 : $windowSize - 10, ' ');
					} else {
						echo $line;
					}
					echo "\e[0m\n";
				} else {
					echo "\e[100m" . str_pad(' ' . $currentLine . ': ', 6, ' ') . "\e[0m";
					echo str_replace("\t", '    ', $fileParser[$i]);
					echo "\n";
				}
			}

			echo '----- file -----' . "\n\n";
		}
	}


	/** Render red block with error message. */
	public static function terminalRenderError(string $message): void
	{
		self::terminalRenderBadge($message, "\033[1;37m\033[41m");
	}


	/** Render green block with message. */
	public static function terminalRenderSuccess(string $message): void
	{
		self::terminalRenderBadge($message, "\033[1;30m\033[42m");
	}


	public static function terminalRenderLabel(string $label): string
	{
		return "\e[33m" . $label . "\033[0m";
	}


	private static function terminalRenderBadge(string $message, string $color): void
	{
		if (PHP_SAPI !== 'cli') {
			throw new \RuntimeException('Terminal: This method is available only in CLI mode.');
		}
		echo "\n" . $color . str_repeat(' ', 100) . "\n";

		foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $message)) as $line) {
			while (true) {
				if (preg_match('/^(.{85,}?)[\s\n](.*)$/', $line, $match) === 0) {
					echo self::formatTerminalLine($line);
					break;
				}

				$line = $match[2];
				echo self::formatTerminalLine($match[1]);
			}
		}

		echo str_repeat(' ', 100) . "\033[0m" . "\n";
	}


	private static function formatTerminalLine(string $line): string
	{
		return '      ' . $line . (($repeat = 88 - self::length($line)) > 0 ? str_repeat(' ', $repeat) : '') . '      ' . "\n";
	}


	/**
	 * Returns number of characters (not bytes) in UTF-8 string.
	 * That is the number of Unicode code points which may differ from the number of graphemes.
	 */
	private static function length(string $s): int
	{
		return function_exists('mb_strlen')
			? mb_strlen($s, 'UTF-8')
			: strlen(utf8_decode($s));
	}
}
