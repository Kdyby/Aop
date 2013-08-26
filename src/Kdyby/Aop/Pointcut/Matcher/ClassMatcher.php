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
class ClassMatcher extends Nette\Object implements Kdyby\Aop\Pointcut\Rule
{

	/**
	 * @var string
	 */
	private $class;



	public function __construct($class)
	{
		$this->class = str_replace('\\*', '.*', preg_quote($class));
	}



	public function matches(Kdyby\Aop\Pointcut\Method $method)
	{
		return preg_match('~^' . $this->class . '\z~i', $method->getClassName());
	}

}
