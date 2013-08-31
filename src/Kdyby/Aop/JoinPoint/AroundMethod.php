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
class AroundMethod extends MethodInvocation
{

	/**
	 * @var array
	 */
	private $aroundChain = array();



	public function __construct($targetObject, $targetMethod, $arguments = array(), $aroundChain = array())
	{
		parent::__construct($targetObject, $targetMethod, $arguments);
		$this->aroundChain = $aroundChain;
	}



	public function setArgument($index, $value)
	{
		$this->arguments[$index] = $value;
	}


	public function proceed()
	{
		if ($nextAround = array_shift($this->aroundChain)) {
			return call_user_func(array($nextAround[0], $nextAround[1]), $this);
		}

		return call_user_func($this->getTargetCallback());
	}

}
