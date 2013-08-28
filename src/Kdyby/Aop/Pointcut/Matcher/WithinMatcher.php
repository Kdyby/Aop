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
class WithinMatcher extends Nette\Object implements Kdyby\Aop\Pointcut\Filter
{

	/**
	 * @var string
	 */
	private $type;



	public function __construct($type)
	{
		$this->type = Nette\Reflection\ClassType::from($type)->getName();
	}



	public function matches(Kdyby\Aop\Pointcut\Method $method)
	{
		return isset($method->typesWithin[$this->type]);
	}

}
