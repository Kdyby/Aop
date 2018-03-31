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
class FilterMatcher implements Kdyby\Aop\Pointcut\Filter
{

	use Nette\SmartObject;

	/**
	 * @var \Kdyby\Aop\Pointcut\Filter
	 */
	private $filter;



	public function __construct($filterClass)
	{
		if (!in_array('Kdyby\Aop\Pointcut\Filter', class_implements($filterClass), TRUE)) {
			throw new Kdyby\Aop\InvalidArgumentException("Given class '$filterClass' must implement Kdyby\\Aop\\Pointcut\\Filter.");
		}

		$this->filter = new $filterClass();
	}



	public function matches(Kdyby\Aop\Pointcut\Method $method)
	{
		return $this->filter->matches($method);
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return $this->filter->listAcceptedTypes();
	}

}
