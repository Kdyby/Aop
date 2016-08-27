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
class AfterThrowing extends MethodInvocation implements ExceptionAware
{

	/**
	 * @var \Exception|\Throwable
	 */
	private $exception;



	/**
	 * @param $targetObject
	 * @param $targetMethod
	 * @param array $arguments
	 * @param \Exception|\Throwable|NULL $exception
	 */
	public function __construct($targetObject, $targetMethod, $arguments = [], $exception = NULL)
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
		$this->exception = $exception;
	}



	/**
	 * @return \Exception|\Throwable
	 */
	public function getException()
	{
		return $this->exception;
	}

}
