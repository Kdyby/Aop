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



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Rules extends Nette\Object implements Rule
{

	const OP_AND = 'AND';
	const OP_OR = 'OR';

	/**
	 * @var string
	 */
	private $operator;

	/**
	 * @var array|Rule[]
	 */
	private $rules;



	public function __construct(array $rules = array(), $operator = self::OP_AND)
	{
		foreach ($rules as $rule) {
			$this->addRule($rule);
		}

		$this->operator = $operator;
	}



	public function addRule(Rule $rule)
	{
		$this->rules[] = $rule;
	}



	/**
	 * @param Method $method
	 * @return bool
	 */
	public function matches(Method $method)
	{
		$logical = array();
		foreach ($this->rules as $rule) {
			$logical[] = $rule->matches($method);
			if (!$this->isMatching($logical)) {
				return FALSE;
			}
		}

		return $this->isMatching($logical);
	}



	private function isMatching(array $result)
	{
		if ($this->operator === self::OP_AND) {
			return array_filter($result) === $result; // all values are TRUE
		}

		return (bool) array_filter($result); // at least one is TRUE
	}

}
