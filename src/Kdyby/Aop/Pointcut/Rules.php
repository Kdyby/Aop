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
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Rules implements Filter, RuntimeFilter
{

	use Nette\SmartObject;

	const OP_AND = 'AND';
	const OP_OR = 'OR';

	/**
	 * @var string
	 */
	private $operator;

	/**
	 * @var array|Filter[]
	 */
	private $rules;



	public function __construct(array $rules = [], $operator = self::OP_AND)
	{
		foreach ($rules as $rule) {
			$this->addRule($rule);
		}

		$this->operator = $operator;
	}



	/**
	 * @param Filter $rule
	 */
	public function addRule(Filter $rule)
	{
		$this->rules[] = $rule;
	}



	/**
	 * @return array|\Kdyby\Aop\Pointcut\Filter[]
	 */
	public function getRules()
	{
		return $this->rules;
	}



	/**
	 * @param Method $method
	 * @return bool
	 */
	public function matches(Method $method)
	{
		if (empty($this->rules)) {
			throw new Kdyby\Aop\NoRulesExceptions();
		}

		$logical = [];
		foreach ($this->rules as $rule) {
			$logical[] = $rule->matches($method);
			if (!$this->isMatching($logical)) {
				return FALSE;
			}
		}

		return $this->isMatching($logical);
	}



	/**
	 * @return array
	 */
	public function listAcceptedTypes()
	{
		$types = [];
		foreach ($this->rules as $rule) {
			$types = array_merge($types, (array)$rule->listAcceptedTypes());
		}

		return array_filter($types);
	}



	/**
	 * @return Code\PhpLiteral|null
	 */
	public function createCondition()
	{
		$conds = [];
		foreach ($this->rules as $rule) {
			if (!$rule instanceof RuntimeFilter) {
				continue;
			}

			$conds[] = $rule->createCondition();
		}

		$conds = array_filter($conds);

		if (count($conds) > 1) {
			$conds = implode(' ' . $this->operator . ' ', $conds);

		} elseif (count($conds) == 1) {
			$conds = reset($conds);

		} else {
			return NULL;
		}

		return new Code\PhpLiteral('(' . $conds . ')');
	}



	/**
	 * @param array|string|Filter $filter
	 * @param string $operator
	 * @return Filter
	 */
	public static function unwrap($filter, $operator = self::OP_AND)
	{
		if (is_array($filter)) {
			if (count($filter) > 1) {
				return new Rules($filter, $operator);
			}

			$filter = reset($filter);
		}

		if ($filter instanceof Rules && count($filter->rules) === 1) {
			return self::unwrap($filter->rules);
		}

		return $filter;
	}



	private function isMatching(array $result)
	{
		if ($this->operator === self::OP_AND) {
			return array_filter($result) === $result; // all values are TRUE
		}

		return (bool) array_filter($result); // at least one is TRUE
	}

}
