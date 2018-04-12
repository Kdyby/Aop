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
class WithinMatcher implements Kdyby\Aop\Pointcut\Filter
{

	use Nette\SmartObject;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $pattern;



	public function __construct($type)
	{
		if (strpos($type, '*') !== FALSE) {
			$this->pattern = str_replace('\\*', '.*', preg_quote($type));

		} else {
			$this->type = Nette\Reflection\ClassType::from($type)->getName();
		}
	}



	public function matches(Kdyby\Aop\Pointcut\Method $method)
	{
		if ($this->type !== NULL) {
			return isset($method->typesWithin[$this->type]);
		}

		foreach ($method->typesWithin as $within) {
			if (preg_match('~^' . $this->pattern . '\z~i', $within)) {
				return TRUE;
			}
		}

		return FALSE;
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		if ($this->type) {
			return [$this->type];
		}

		return FALSE;
	}

}
