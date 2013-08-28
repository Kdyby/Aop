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
	 * @var string
	 */
	private $visibility;



	/**
	 * @todo visibility
	 */
	public function __construct($method)
	{
		if (strpos($method, ' ') !== FALSE) {
			list($this->visibility, $method) = explode(' ', $method, 2);
			$this->visibility = strtolower($this->visibility);
			if (!defined('\Kdyby\Aop\Pointcut\Method::VISIBILITY_' . strtoupper($this->visibility))) {
				throw new Kdyby\Aop\InvalidArgumentException("Invalid visibility '{$this->visibility}'.");
			}
		}

		$this->method = str_replace('\\*', '.*', preg_quote($method));
	}



	public function matches(Kdyby\Aop\Pointcut\Method $method)
	{
		if ($this->visibility !== NULL && $this->visibility !== $method->getVisibility()) {
			return FALSE;
		}

		return preg_match('~^' . $this->method . '\z~i', $method->getName());
	}

}
