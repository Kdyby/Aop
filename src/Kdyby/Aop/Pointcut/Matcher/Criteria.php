<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\Pointcut\Matcher;

use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;
use Nette\DI\ContainerBuilder;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Criteria extends Nette\Object
{
	const TYPE_AND = 'AND';
	const TYPE_OR = 'OR';

	const EQ = '=='; // value comparison
	const NEQ = '<>';
	const LT = '<';
	const LTE = '<=';
	const GT = '>';
	const GTE = '>=';
	const IS = '==='; // identity comparison
	const IN = 'IN';
	const NIN = 'NIN';
	const CONTAINS = 'CONTAINS';
	const MATCHES = 'MATCHES';

	/**
	 * @var string
	 */
	private $operator;

	/**
	 * @var array
	 */
	private $expressions = array();



	/**
	 * @param string $operator
	 * @throws \Kdyby\Aop\InvalidArgumentException
	 */
	public function __construct($operator = self::TYPE_AND)
	{
		if (!in_array($operator = strtoupper($operator), array(self::TYPE_AND, self::TYPE_OR), TRUE)) {
			throw new Kdyby\Aop\InvalidArgumentException("Given operator '$operator' cannot be evaluated.");
		}

		$this->operator = $operator;
	}



	/**
	 * @param string $left
	 * @param string $comparison
	 * @param string $right
	 * @throws \Kdyby\Aop\InvalidArgumentException
	 * @return Criteria
	 */
	public function where($left, $comparison = NULL, $right = NULL)
	{
		if ($left instanceof self) {
			$this->expressions[] = $left;
			return $this;
		}

		if (!self::isValidComparison($comparison = strtoupper($comparison))) {
			throw new Kdyby\Aop\InvalidArgumentException("Given comparison '$comparison' cannot be evaluated.");
		}

		$this->expressions[] = array($left, $comparison, $right);
		return $this;
	}



	/**
	 * @param string $operator
	 * @return Criteria
	 */
	public static function create($operator = self::TYPE_AND)
	{
		return new static($operator);
	}



	public function evaluate(ContainerBuilder $builder)
	{
		if (empty($this->expressions)) {
			throw new Kdyby\Aop\NoRulesExceptions();
		}

		$logical = array();
		foreach ($this->expressions as $expression) {
			$logical[] = $this->doEvaluate($builder, $expression);
			if (!$this->isMatching($logical)) {
				return FALSE;
			}
		}

		return $this->isMatching($logical);
	}



	/**
	 * @param ContainerBuilder $builder
	 * @param array|Criteria $expression
	 * @return bool
	 */
	private function doEvaluate(ContainerBuilder $builder, $expression)
	{
		if ($expression instanceof self) {
			return $expression->evaluate($builder);
		}

		if ($expression[0] instanceof Code\PhpLiteral) {
			$expression[0] = self::resolveExpression($expression[0]);

		} else {
			$expression[0] = $builder->expand('%' . $expression[0] . '%');
		}

		if ($expression[2] instanceof Code\PhpLiteral) {
			$expression[2] = self::resolveExpression($expression[2]);

		} else {
			$expression[2] = $builder->expand('%' . $expression[2] . '%');
		}

		return self::compare($expression[0], $expression[1], $expression[2]);
	}



	public function serialize(ContainerBuilder $builder = NULL)
	{
		return ''; // todo: implement
	}



	private function isMatching(array $result)
	{
		if ($this->operator === self::TYPE_AND) {
			return array_filter($result) === $result; // all values are TRUE
		}

		return (bool) array_filter($result); // at least one is TRUE
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		return get_class($this) . '(#' . spl_object_hash($this) . ')';
	}



	/**
	 * @param $comparison
	 * @return bool
	 */
	public static function isValidComparison($comparison)
	{
		return in_array(strtoupper($comparison), array(
			self::EQ, self::NEQ,
			self::LT, self::LTE,
			self::GT, self::GTE,
			self::IS, self::IN, self::NIN,
			self::CONTAINS, self::MATCHES
		), TRUE);
	}



	/**
	 * @param $expression
	 * @throws Kdyby\Aop\ParserException
	 * @return mixed
	 */
	private static function resolveExpression($expression)
	{
		set_error_handler(function ($severenity, $message) {
			restore_error_handler();
			throw new Kdyby\Aop\ParserException($message, $severenity);
		});
		$result = eval("return $expression;");
		restore_error_handler();

		return $result;
	}



	public static function compare($left, $operator, $right)
	{
		switch (strtoupper($operator)) {
			case self::EQ:
				return $left == $right;

			case self::NEQ;
			case '!=';
				return !self::compare($left, self::EQ, $right);

			case self::GT:
				return $left > $right;

			case self::GTE;
				return $left >= $right;

			case self::LT;
				return $left < $right;

			case self::LTE;
				return $left <= $right;

			case self::IS;
			case 'IS';
				return $left === $right;

			case self::IN;
				if (is_array($left)) {
					return self::compare($left, self::CONTAINS, $right);
				}

				return Nette\Utils\Strings::contains($left, $right);

			case self::NIN;
				return !self::compare($left, self::IN, $right);

			case self::MATCHES:
				return Nette\Utils\Strings::contains($left, $right); // todo: smarter!

			case self::CONTAINS:
				return in_array($left, $right, TRUE);

			default:
				throw new Kdyby\Aop\NotImplementedException();
		}
	}

}
