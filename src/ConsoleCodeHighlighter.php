<?php

declare(strict_types=1);

namespace Baraja\Console;


final class ConsoleCodeHighlighter
{
	private const
		TOKEN_DEFAULT = 'token_default',
		TOKEN_COMMENT = 'token_comment',
		TOKEN_STRING = 'token_string',
		TOKEN_HTML = 'token_html',
		TOKEN_KEYWORD = 'token_keyword';

	private const
		ACTUAL_LINE_MARK = 'actual_line_mark',
		LINE_NUMBER = 'line_number';

	private const DEFAULT_THEME = [
		self::TOKEN_STRING => 'red',
		self::TOKEN_COMMENT => 'yellow',
		self::TOKEN_KEYWORD => 'green',
		self::TOKEN_DEFAULT => 'default',
		self::TOKEN_HTML => 'cyan',
		self::ACTUAL_LINE_MARK => 'red',
		self::LINE_NUMBER => 'dark_gray',
	];

	private ConsoleColor $color;


	public function __construct()
	{
		$this->color = new ConsoleColor;
		foreach (self::DEFAULT_THEME as $name => $styles) {
			if (!$this->color->hasTheme($name)) {
				$this->color->addTheme($name, $styles);
			}
		}
	}


	public function getCodeSnippet(string $code, int $lineNumber, int $linesBefore = 2, int $linesAfter = 2): string
	{
		$tokenLines = $this->getHighlightedLines($code);

		$offset = $lineNumber - $linesBefore - 1;
		$offset = max($offset, 0);
		$length = $linesAfter + $linesBefore + 1;
		$tokenLines = array_slice($tokenLines, $offset, $length, $preserveKeys = true);
		$lines = $this->colorLines($tokenLines);

		return $this->lineNumbers($lines, $lineNumber);
	}


	public function getWholeFile(string $code): string
	{
		$tokenLines = $this->getHighlightedLines($code);
		$lines = $this->colorLines($tokenLines);

		return implode(PHP_EOL, $lines);
	}


	public function getWholeFileWithLineNumbers(string $code): string
	{
		$tokenLines = $this->getHighlightedLines($code);
		$lines = $this->colorLines($tokenLines);

		return $this->lineNumbers($lines);
	}


	/**
	 * @return array<int, array<int, array<int, string>>>
	 */
	private function getHighlightedLines(string $code): array
	{
		$code = str_replace(["\r\n", "\r"], "\n", $code);
		$tokens = $this->tokenize($code);

		return $this->splitToLines($tokens);
	}


	/**
	 * @return string[][]
	 */
	private function tokenize(string $code): array
	{
		$tokens = token_get_all($code);

		$output = [];
		$currentType = null;
		$buffer = '';

		foreach ($tokens as $token) {
			$newType = '';
			if (is_array($token)) {
				switch ($token[0]) {
					case T_WHITESPACE:
						break;

					case T_OPEN_TAG:
					case T_OPEN_TAG_WITH_ECHO:
					case T_CLOSE_TAG:
					case T_STRING:
					case T_VARIABLE:
					case T_DIR:
					case T_FILE:
					case T_METHOD_C:
					case T_DNUMBER:
					case T_LNUMBER:
					case T_NS_C:
					case T_LINE:
					case T_CLASS_C:
					case T_FUNC_C:
					case T_TRAIT_C:
						$newType = self::TOKEN_DEFAULT;
						break;

					case T_COMMENT:
					case T_DOC_COMMENT:
						$newType = self::TOKEN_COMMENT;
						break;

					case T_ENCAPSED_AND_WHITESPACE:
					case T_CONSTANT_ENCAPSED_STRING:
						$newType = self::TOKEN_STRING;
						break;

					case T_INLINE_HTML:
						$newType = self::TOKEN_HTML;
						break;

					default:
						$newType = self::TOKEN_KEYWORD;
				}
			} else {
				$newType = $token === '"' ? self::TOKEN_STRING : self::TOKEN_KEYWORD;
			}
			if ($currentType === null) {
				$currentType = $newType;
			}
			if ($currentType !== $newType) {
				$output[] = [$currentType, $buffer];
				$buffer = '';
				$currentType = $newType;
			}

			$buffer .= is_array($token) ? $token[1] : $token;
		}
		if (isset($newType)) {
			$output[] = [$newType, $buffer];
		}

		return $output;
	}


	/**
	 * @param string[][] $tokens
	 * @return array<int, array<int, array<int, string>>>
	 */
	private function splitToLines(array $tokens): array
	{
		$lines = [];
		$line = [];
		foreach ($tokens as $token) {
			foreach (explode("\n", $token[1]) as $count => $tokenLine) {
				if ($count > 0) {
					$lines[] = $line;
					$line = [];
				}
				if ($tokenLine === '') {
					continue;
				}
				$line[] = [$token[0], $tokenLine];
			}
		}

		$lines[] = $line;

		return $lines;
	}


	/**
	 * @param array<int, array<int, array<int, string>>> $tokenLines
	 * @return string[]
	 */
	private function colorLines(array $tokenLines): array
	{
		$lines = [];
		foreach ($tokenLines as $lineCount => $tokenLine) {
			$line = '';
			foreach ($tokenLine as $token) {
				[$tokenType, $tokenValue] = $token;
				if ($this->color->hasTheme($tokenType)) {
					$line .= $this->color->apply($tokenType, $tokenValue);
				} else {
					$line .= $tokenValue;
				}
			}
			$lines[$lineCount] = $line;
		}

		return $lines;
	}


	/**
	 * @param string[] $lines
	 */
	private function lineNumbers(array $lines, ?int $markLine = null): string
	{
		end($lines);
		$lineLength = strlen((string) (((int) key($lines)) + 1));

		$snippet = '';
		foreach ($lines as $i => $line) {
			if ($markLine !== null) {
				$snippet .= ($markLine === $i + 1 ? $this->color->apply(self::ACTUAL_LINE_MARK, '  > ') : '    ');
			}

			$snippet .= $this->color->apply(self::LINE_NUMBER, str_pad((string) ($i + 1), $lineLength, ' ', STR_PAD_LEFT) . '| ');
			$snippet .= $line . PHP_EOL;
		}

		return $snippet;
	}
}
