<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\JoinPoint;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AfterReturning extends MethodInvocation implements ResultAware
{

	/**
	 * @var mixed
	 */
	private $result;



	public function __construct($targetObject, $targetMethod, $arguments = [], $result = NULL)
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
		$this->result = $result;
	}



	/**
	 * @param mixed $result
	 */
	public function setResult($result)
	{
		$this->result = $result;
	}



	/**
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->result;
	}

}
