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
class Inverse extends Nette\Object implements Kdyby\Aop\Pointcut\Filter
{

	/**
	 * @var \Kdyby\Aop\Pointcut\Filter
	 */
	private $filter;



	public function __construct(Kdyby\Aop\Pointcut\Filter $filter)
	{
		$this->filter = $filter;
	}



	public function matches(Kdyby\Aop\Pointcut\Method $method)
	{
		return ! $this->filter->matches($method);
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return $this->filter->listAcceptedTypes();
	}

}
