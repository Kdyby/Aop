<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\Pointcut\Matcher;

use Doctrine\Common\Collections\Collection;
use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;
use Nette\DI\ContainerBuilder;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Criteria
{

	use Nette\SmartObject;

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
	private $expressions = [];



	/**
	 * @param string $operator
	 * @throws \Kdyby\Aop\InvalidArgumentException
	 */
	public function __construct($operator = self::TYPE_AND)
	{
		if (!in_array($operator = strtoupper($operator), [self::TYPE_AND, self::TYPE_OR], TRUE)) {
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

		$this->expressions[] = [$left, $comparison, $right];
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

		$logical = [];
		foreach ($this->expressions as $expression) {
			$logical[] = $this->doEvaluate($builder, $expression);
			if (!$this->isMatching($logical)) {
				return FALSE;
			}
		}

		return $this->isMatching($logical);
	}



	private function isMatching(array $result)
	{
		if ($this->operator === self::TYPE_AND) {
			return array_filter($result) === $result; // all values are TRUE
		}

		return (bool) array_filter($result); // at least one is TRUE
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

		return self::compare(
			$this->doEvaluateValueResolve($builder, $expression[0]),
			$expression[1],
			$this->doEvaluateValueResolve($builder, $expression[2])
		);
	}



	private function doEvaluateValueResolve(ContainerBuilder $builder, $expression)
	{
		if ($expression instanceof Code\PhpLiteral) {
			$expression = self::resolveExpression($expression);

		} else {
			$expression = $builder->expand('%' . $expression . '%');
		}

		return $expression;
	}



	public function serialize(ContainerBuilder $builder)
	{
		if (empty($this->expressions)) {
			throw new Kdyby\Aop\NoRulesExceptions();
		}

		$serialised = [];
		foreach ($this->expressions as $expression) {
			$serialised[] = $this->doSerialize($builder, $expression);
		}

		return new Code\PhpLiteral('(' . implode(' ' . $this->operator . ' ', array_filter($serialised)) . ')');
	}



	/**
	 * @param ContainerBuilder $builder
	 * @param array|Criteria $expression
	 * @return bool
	 */
	private function doSerialize(ContainerBuilder $builder, $expression)
	{
		if ($expression instanceof self) {
			return $expression->serialize($builder);
		}

		return Code\Helpers::format(
			'Criteria::compare(?, ?, ?)',
			$this->doSerializeValueResolve($builder, $expression[0]),
			$expression[1],
			$this->doSerializeValueResolve($builder, $expression[2])
		);
	}



	private function doSerializeValueResolve(ContainerBuilder $builder, $expression)
	{
		if ($expression instanceof Code\PhpLiteral) {
			$expression = self::resolveExpression($expression);

		} elseif (substr($expression, 0, 1) === '%') {
			$expression = $builder->expand($expression);

		} elseif (substr($expression, 0, 1) === '$') {
			$expression = new Code\PhpLiteral($expression);

		} else {
			if (!$m = self::shiftAccessPath($expression)) {
				return $expression; // it's probably some kind of expression

			} else {
				if ($m['context'] === 'this') {
					$targetObject = '$this';

				} elseif ($m['context'] === 'context' && ($p = self::shiftAccessPath($m['path']))) {
					if (class_exists($p['context']) || interface_exists($p['context'])) {
						$targetObject = Code\Helpers::format('$this->_kdyby_aopContainer->getByType(?)', $p['context']);

					} else {
						$targetObject = Code\Helpers::format('$this->_kdyby_aopContainer->getService(?)', $p['context']);
					}

					$m['path'] = $p['path'];

				} else {
					throw new Kdyby\Aop\NotImplementedException();
				}

				$expression = Code\Helpers::format('PropertyAccess::createPropertyAccessor()->getValue(?, ?)', new Code\PhpLiteral($targetObject), $m['path']);
			}

			$expression = new Code\PhpLiteral($expression);
		}

		return $expression;
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
		return in_array(strtoupper($comparison), [
			self::EQ, self::NEQ, '!=',
			self::LT, self::LTE,
			self::GT, self::GTE,
			self::IS, 'IS', self::IN, self::NIN,
			self::CONTAINS, self::MATCHES
		], TRUE);
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



	/**
	 * @param string $path
	 * @return array|NULL
	 */
	private static function shiftAccessPath($path)
	{
		$shifted = Nette\Utils\Strings::match($path, '~^(?P<context>[^\\[\\]\\.]+)(?P<path>(\\[|\\.).*)\z~i');
		if ($shifted && substr($shifted['path'], 0, 1) === '.') {
			$shifted['path'] = substr($shifted['path'], 1);
		}

		return $shifted;
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

			case self::NIN;
				return !self::compare($left, self::IN, $right);

			case self::IN;
				if ($right instanceof \SplObjectStorage || $right instanceof Collection) {
					/** @var Collection $right */
					return $left !== NULL && $right->contains($left);

				} else {
					if ($right instanceof \Traversable) {
						$right = iterator_to_array($right);

					} elseif (!is_array($right)) {
						throw new Kdyby\Aop\InvalidArgumentException('Right value is expected to be array or instance of Traversable');
					}

					return in_array($left, $right, TRUE);
				}

			case self::CONTAINS:
				return self::compare($right, self::IN, $left);

			case self::MATCHES:
				if ($right instanceof \Traversable) {
					$right = iterator_to_array($right);

				} elseif (!is_array($right)) {
					throw new Kdyby\Aop\InvalidArgumentException('Right value is expected to be array or Traversable');
				}

				if ($left instanceof \Traversable) {
					$left = iterator_to_array($left);

				} elseif (!is_array($left)) {
					throw new Kdyby\Aop\InvalidArgumentException('Left value is expected to be array or Traversable');
				}

				return (bool)array_filter(array_intersect($left, $right));

			default:
				throw new Kdyby\Aop\NotImplementedException();
		}
	}

}
