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



	public function evaluate()
	{
		// todo: implement
	}



	public function serialize()
	{
		return ''; // todo: implement
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

}
