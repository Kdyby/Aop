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
use Kdyby\Aop\PhpGenerator\AdvisedClassType;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AroundMethod extends MethodInvocation
{

	/**
	 * @var array|callable[]
	 */
	private $callChain = [];



	public function __construct($targetObject, $targetMethod, $arguments = [])
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
	}



	public function setArgument($index, $value)
	{
		$this->arguments[$index] = $value;
	}



	public function addChainLink($object, $method)
	{
		return $this->callChain[] = [$object, $method];
	}



	/**
	 * @return mixed
	 */
	public function proceed()
	{
		if ($callback = array_shift($this->callChain)) {
			return call_user_func([$callback[0], $callback[1]], $this);
		}

		return call_user_func_array([$this->targetObject, AdvisedClassType::CG_PUBLIC_PROXY_PREFIX . $this->targetMethod], $this->getArguments());
	}

}
