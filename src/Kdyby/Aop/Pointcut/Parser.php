<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\Pointcut;

use Kdyby;
use Nette;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Utils\TokenIterator;
use Nette\Utils\Tokenizer;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Parser
{
	use Nette\SmartObject;

	const TOK_BRACKET = 'bracket';
	const TOK_VISIBILITY = 'visibility';
	const TOK_KEYWORD = 'keyword';
	const TOK_OPERATOR = 'operator';
	const TOK_LOGIC = 'logic';
	const TOK_NOT = 'not';
	const TOK_METHOD = 'method';
	const TOK_IDENTIFIER = 'identifier';
	const TOK_WHITESPACE = 'whitespace';
	const TOK_STRING = 'string';
	const TOK_WILDCARD = 'wildcard';

	/**
	 * @var Tokenizer
	 */
	private $tokenizer;

	/**
	 * @var MatcherFactory
	 */
	private $matcherFactory;



	public function __construct(MatcherFactory $matcherFactory)
	{
		$this->tokenizer = new Tokenizer([
			self::TOK_BRACKET => '[\\(\\)]',
			self::TOK_VISIBILITY => '(?:public|protected|private)(?=[\t ]+)',
			self::TOK_KEYWORD => '(?:classAnnotatedWith|class|methodAnnotatedWith|method|within|filter|setting|evaluate)(?=\\()',
			self::TOK_OPERATOR => '(?:===?|!==?|<=|>=|<|>|n?in|contains|matches)',
			self::TOK_LOGIC => '(?:\\&\\&|\\|\\||,)',
			self::TOK_NOT => '!',
			self::TOK_METHOD => '(?:-\\>|::)[_a-z0-9\\*\\[\\]\\|\\!]+(?=(?:\\(|\\)|\s|\z))', # including wildcard
			self::TOK_IDENTIFIER => '[_a-z0-9\x7F-\xFF\\*\\.\\$\\%\\\\-]+(?<!\\-)', # including wildcard
			self::TOK_WHITESPACE => '[\n\r\s]+',
			self::TOK_STRING => '\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"',
			self::TOK_WILDCARD => '\\*',
		], 'i');

		$this->matcherFactory = $matcherFactory;
	}



	public function parse($input)
	{
		try {
			$tokens = new TokenIterator($this->tokenizer->tokenize($input));
			$tokens->ignored = [self::TOK_WHITESPACE];

		} catch (Nette\Utils\TokenizerException $e) {
			throw new Kdyby\Aop\ParserException("Input contains unexpected expressions", 0, $e);
		}

		return $this->doParse($tokens);
	}



	/**
	 * @param $tokens
	 * @return Rules|mixed
	 * @throws \Kdyby\Aop\ParserException
	 */
	protected function doParse(TokenIterator $tokens)
	{
		$inverseNext = FALSE;
		$operator = NULL;
		$rules = [];
		while ($token = $tokens->nextToken()) {
			if ($tokens->isCurrent(self::TOK_KEYWORD)) {
				$rule = $this->{'parse' . $token[0]}($tokens);
				if ($inverseNext) {
					$rule = new Matcher\Inverse($rule);
					$inverseNext = FALSE;
				}

				$rules[] = $rule;

			} elseif ($tokens->isCurrent(self::TOK_IDENTIFIER)) {
				$rule = $this->parseMethod($tokens);
				if ($inverseNext) {
					$rule = new Matcher\Inverse($rule);
					$inverseNext = FALSE;
				}

				$rules[] = $rule;

			} elseif ($tokens->isCurrent('(')) {
				$rules[] = $this->doParse($tokens);

			} elseif ($tokens->isCurrent(')')) {
				break;

			} elseif ($tokens->isCurrent(self::TOK_NOT)) {
				$inverseNext = TRUE;

			} elseif ($tokens->isCurrent(self::TOK_LOGIC)) {
				if ($operator !== NULL && $operator !== $tokens->currentValue()) {
					throw new Kdyby\Aop\ParserException('Unexpected operator ' . $tokens->currentValue() . '. If you wanna combine them, you must wrap them in brackets like this `a || (b && c)`.');
				}

				$operator = $tokens->currentValue();
				continue;
			}
		}

		if ($operator === ',' || $operator === '&&') {
			$operator = Rules::OP_AND;

		} elseif ($operator === '||') {
			$operator = Rules::OP_OR;
		}

		return Rules::unwrap($rules, $operator ? : Rules::OP_AND);
	}



	protected function parseClass(TokenIterator $tokens)
	{
		$tokens->nextUntil(self::TOK_IDENTIFIER);
		$className = $tokens->nextValue();
		$tokens->nextToken(); // )

		return $this->matcherFactory->getMatcher('class', $className);
	}



	protected function parseMethod(TokenIterator $tokens)
	{
		$visibility = NULL;
		$arguments = [];

		if ($tokens->isCurrent(self::TOK_KEYWORD)) {
			self::nextValue($tokens, self::TOK_KEYWORD, [self::TOK_WHITESPACE]);
			$tokens->nextToken();
			self::nextValue($tokens, '(', [self::TOK_WHITESPACE]);
			$tokens->nextToken();
		}

		$className = self::nextValue($tokens, [self::TOK_IDENTIFIER, self::TOK_VISIBILITY], [self::TOK_WHITESPACE]);
		if ($tokens->isCurrent(self::TOK_VISIBILITY)) {
			$visibility = $className . ' ';

			$tokens->nextToken();
			$className = self::nextValue($tokens, [self::TOK_IDENTIFIER], [self::TOK_WHITESPACE]);
		}

		$tokens->nextToken();
		$method = substr(self::nextValue($tokens, [self::TOK_METHOD], [self::TOK_WHITESPACE]), 2);

		if ($tokens->isNext('(')) {
			if ($criteria = $this->parseArguments($tokens)) {
				$arguments = [$this->matcherFactory->getMatcher('arguments', $criteria)];
			}
		}
		$tokens->nextToken(); // method end )

		if ($method === '*' && empty($visibility) && !$arguments) {
			return $this->matcherFactory->getMatcher('class', $className);

		} elseif ($className === '*' && !$arguments) {
			return $this->matcherFactory->getMatcher('method', $visibility . $method);
		}

		return new Rules(array_merge([
			$this->matcherFactory->getMatcher('class', $className),
			$this->matcherFactory->getMatcher('method', $visibility . $method),
		], $arguments), Rules::OP_AND);
	}



	protected function parseWithin(TokenIterator $tokens)
	{
		$tokens->nextUntil(self::TOK_IDENTIFIER);
		$within = $tokens->nextValue();
		$tokens->nextToken(); // )

		return $this->matcherFactory->getMatcher('within', $within);
	}



	protected function parseFilter(TokenIterator $tokens)
	{
		$tokens->nextUntil(self::TOK_IDENTIFIER);
		$filter = $tokens->nextValue();
		$tokens->nextToken(); // )

		return $this->matcherFactory->getMatcher('filter', $filter);
	}



	protected function parseSetting(TokenIterator $tokens)
	{
		$tokens->nextUntil('(');
		if (!$criteria = $this->parseArguments($tokens)) {
			throw new Kdyby\Aop\ParserException('Settings criteria cannot be empty.');
		}

		return $this->matcherFactory->getMatcher('setting', $criteria);
	}



	protected function parseEvaluate(TokenIterator $tokens)
	{
		$tokens->nextUntil('(');
		if (!$criteria = $this->parseArguments($tokens)) {
			throw new Kdyby\Aop\ParserException('Evaluate expression cannot be empty.');
		}

		return $this->matcherFactory->getMatcher('evaluate', $criteria);
	}



	protected function parseClassAnnotatedWith(TokenIterator $tokens)
	{
		$tokens->nextUntil(self::TOK_IDENTIFIER);
		$annotation = $tokens->nextValue();
		$tokens->nextToken(); // )

		return $this->matcherFactory->getMatcher('classAnnotatedWith', $annotation);
	}



	protected function parseMethodAnnotatedWith(TokenIterator $tokens)
	{
		$tokens->nextUntil(self::TOK_IDENTIFIER);
		$annotation = $tokens->nextValue();
		$tokens->nextToken(); // )

		return $this->matcherFactory->getMatcher('methodAnnotatedWith', $annotation);
	}



	protected function parseArguments(TokenIterator $tokens)
	{
		$operator = NULL;
		$conditions = [];

		while ($token = $tokens->nextToken()) {
			if ($tokens->isCurrent(self::TOK_LOGIC)) {
				if ($operator !== NULL && $operator !== $tokens->currentValue()) {
					throw new Kdyby\Aop\ParserException('Unexpected operator ' . $tokens->currentValue() . '. If you wanna combine them, you must wrap them in brackets.');
				}

				$operator = $tokens->currentValue();
				continue;

			} elseif ($tokens->isCurrent('(')) {
				if ($conditions || $tokens->isNext('(')) {
					$conditions[] = $this->parseArguments($tokens);
				}
				continue;
			}

			if ($tokens->isCurrent(')')) {
				break;
			}

			$left = self::sanitizeArgumentExpression(self::nextValue(
				$tokens,
				[self::TOK_IDENTIFIER, self::TOK_STRING],
				self::TOK_WHITESPACE
			), $tokens->currentToken());

			$tokens->nextToken();
			$comparator = self::nextValue($tokens, [self::TOK_OPERATOR, self::TOK_LOGIC, ')'], self::TOK_WHITESPACE);

			if ($tokens->isCurrent(self::TOK_LOGIC, ')')) {
				$tokens->position -= 1;
				$conditions[] = [$left, Matcher\Criteria::EQ, new PhpLiteral("TRUE")];
				continue;
			}

			if ($tokens->isCurrent('in', 'nin', 'matches')) {
				$tokens->nextUntil(self::TOK_IDENTIFIER, self::TOK_STRING, '(');
				if ($tokens->isNext('(')) {
					$tokens->nextToken(); // (

					$right = [];
					while ($token = $tokens->nextToken()) {
						if ($tokens->isCurrent(')')) {
							break;

						} elseif ($tokens->isCurrent(self::TOK_IDENTIFIER, self::TOK_STRING)) {
							$right[] = self::sanitizeArgumentExpression($tokens->currentValue(), $token);

						} elseif (!$tokens->isCurrent(',', self::TOK_WHITESPACE)) {
							throw new Kdyby\Aop\ParserException('Unexpected token ' . $token[Tokenizer::TYPE]);
						}
					}

					if (empty($right)) {
						throw new Kdyby\Aop\ParserException("Argument for $comparator cannot be an empty array.");
					}

					$conditions[] = [$left, $comparator, $right];
					continue;
				}
			}

			$tokens->nextToken();
			$right = self::sanitizeArgumentExpression(self::nextValue(
				$tokens,
				[self::TOK_IDENTIFIER, self::TOK_STRING],
				self::TOK_WHITESPACE
			), $tokens->currentToken());

			$conditions[] = [$left, $comparator, $right];
		}

		if (!$conditions) {
			if ($tokens->isCurrent(')')) {
				$tokens->nextToken();
			}

			return NULL;
		}

		try {
			if ($operator === ',') {
				$operator = Matcher\Criteria::TYPE_AND;
			}

			$criteria = new Matcher\Criteria($operator ? : Matcher\Criteria::TYPE_AND);
			foreach ($conditions as $condition) {
				if ($condition instanceof Matcher\Criteria) {
					$criteria->where($condition);

				} else {
					$criteria->where($condition[0], $condition[1], $condition[2]);
				}
			}

		} catch (Kdyby\Aop\InvalidArgumentException $e) {
			throw new Kdyby\Aop\ParserException('Invalid arguments', 0, $e);
		}

		if ($tokens->isCurrent(')')) {
			$tokens->nextToken();
		}

		return $criteria;
	}



	protected static function sanitizeArgumentExpression($value, $token)
	{
		if ($token[Tokenizer::TYPE] === self::TOK_STRING || is_numeric($value) || preg_match('~^(TRUE|FALSE)\z~i', $value)) {
			return new PhpLiteral($value);
		}

		return $value;
	}



	/**
	 * @param TokenIterator $tokens
	 * @param array|string $types
	 * @param array|string $allowedToSkip
	 * @throws \Kdyby\Aop\ParserException
	 * @return NULL|string
	 */
	protected static function nextValue(TokenIterator $tokens, $types, $allowedToSkip = [])
	{
		do {
			if (call_user_func_array([$tokens, 'isCurrent'], (array)$types)) {
				return $tokens->currentValue();
			}

			if (!$allowedToSkip || !call_user_func_array([$tokens, 'isCurrent'], (array)$allowedToSkip)) {
				$type = $tokens->currentToken();
				throw new Kdyby\Aop\ParserException('Unexpected token ' . $type[Tokenizer::TYPE] . ' at offset ' . $type[Tokenizer::OFFSET]);
			}

		} while ($token = $tokens->nextToken());

		throw new Kdyby\Aop\ParserException('Expected token ' . implode(', ', (array)$types));
	}

}
