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
class MethodMatcher extends Nette\Object implements Kdyby\Aop\Pointcut\Rule
{

	/**
	 * @var string
	 */
	private $method;



	/**
	 * @todo visibility
	 */
	public function __construct($method)
	{
		$this->method = str_replace('\\*', '.*', preg_quote($method));
	}



	public function matches(Kdyby\Aop\Pointcut\Method $method)
	{
		return preg_match('~^' . $this->method . '\z~i', $method->getName());
	}

}
