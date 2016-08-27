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
class AfterMethod extends MethodInvocation implements ResultAware, ExceptionAware
{

	/**
	 * @var mixed
	 */
	private $result;

	/**
	 * @var \Exception|\Throwable|NULL
	 */
	private $exception;



	/**
	 * @param $targetObject
	 * @param $targetMethod
	 * @param array $arguments
	 * @param null $result
	 * @param \Exception|\Throwable|NULL $exception
	 */
	public function __construct($targetObject, $targetMethod, $arguments = [], $result = NULL, $exception = NULL)
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
		$this->result = $result;
		$this->exception = $exception;
	}



	/**
	 * @return mixed|NULL
	 */
	public function getResult()
	{
		return $this->result;
	}



	/**
	 * @return \Exception|\Throwable|NULL
	 */
	public function getException()
	{
		return $this->exception;
	}

}
