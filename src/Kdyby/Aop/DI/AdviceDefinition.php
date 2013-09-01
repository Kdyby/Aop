<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\DI;

use Kdyby;
use Kdyby\Aop\Pointcut\Method;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AdviceDefinition extends Nette\Object
{

	/**
	 * @var Method
	 */
	private $targetMethod;

	/**
	 * @var Method
	 */
	private $advice;

	/**
	 * @var string
	 */
	private $adviceType;



	public function __construct($adviceType, Method $targetMethod, Method $advice)
	{
		$this->targetMethod = $targetMethod;
		$this->advice = $advice;
		$this->adviceType = $adviceType;
	}



	/**
	 * @return string
	 */
	public function getAdviceType()
	{
		return $this->adviceType;
	}



	/**
	 * @return \Kdyby\Aop\Pointcut\Method
	 */
	public function getTargetMethod()
	{
		return $this->targetMethod;
	}



	/**
	 * @return \Kdyby\Aop\Pointcut\Method
	 */
	public function getAdvice()
	{
		return $this->advice;
	}

}
