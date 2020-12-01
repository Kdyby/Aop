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



	public function __construct(string $adviceType, Method $targetMethod, Method $advice, Filter $filter)
	{
		$this->targetMethod = $targetMethod;
		$this->advice = $advice;
		$this->adviceType = $adviceType;
		$this->filter = $filter;
	}



	public function getAdviceType(): string
	{
		return $this->adviceType;
	}



	public function getTargetMethod(): Method
	{
		return $this->targetMethod;
	}



	public function getAdvice(): Method
	{
		return $this->advice;
	}



	public function getFilter(): Filter
	{
		return $this->filter;
	}

}
