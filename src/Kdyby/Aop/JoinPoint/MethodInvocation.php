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
use Nette\Reflection\ClassType;
use Nette\Reflection\Method;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class MethodInvocation extends Nette\Object
{

	/**
	 * @var object
	 */
	private $targetObject;

	/**
	 * @var string
	 */
	private $targetMethod;

	/**
	 * @var array
	 */
	protected $arguments;



	public function __construct($targetObject, $targetMethod, $arguments = array())
	{
		$this->targetObject = $targetObject;
		$this->targetMethod = $targetMethod;
		$this->arguments = $arguments;
	}



	/**
	 * @return object
	 */
	public function getTargetObject()
	{
		return $this->targetObject;
	}



	/**
	 * @return callable
	 */
	public function getTargetCallback()
	{
		$target = array($this->targetObject, $this->targetMethod);
		$args = $this->arguments;
		return function () use ($target, $args) {
			return call_user_func_array($target, $args);
		};
	}



	/**
	 * @return array
	 */
	public function getArguments()
	{
		return $this->arguments;
	}



	/**
	 * @return ClassType
	 */
	public function getTargetObjectReflection()
	{
		return ClassType::from($this->targetObject);
	}



	/**
	 * @return Method
	 */
	public function getTargetReflection()
	{
		return new Method($this->targetObject, $this->targetMethod);
	}

}
