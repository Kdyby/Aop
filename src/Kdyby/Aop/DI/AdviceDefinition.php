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
use Kdyby\Aop\Pointcut\Filter;
use Kdyby\Aop\Pointcut\Method;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AdviceDefinition
{

	use Nette\SmartObject;

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

	/**
	 * @var \Kdyby\Aop\Pointcut\Filter
	 */
	private $filter;



	public function __construct($adviceType, Method $targetMethod, Method $advice, Filter $filter)
	{
		$this->targetMethod = $targetMethod;
		$this->advice = $advice;
		$this->adviceType = $adviceType;
		$this->filter = $filter;
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



	/**
	 * @return \Kdyby\Aop\Pointcut\Filter
	 */
	public function getFilter()
	{
		return $this->filter;
	}

}
