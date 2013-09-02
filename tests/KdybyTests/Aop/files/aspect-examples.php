<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Aop;

use Nette;
use Kdyby\Aop;


class CommonService
{

	public $calls = array();

	public $throw = FALSE;

	public $return = 2;



	public function magic($argument)
	{
		$this->calls[] = func_get_args();

		if ($this->throw) {
			throw new \RuntimeException("Something's fucky");
		}

		return $this->return * $argument;
	}

}



class BeforeAspect extends Nette\Object
{

	/**
	 * @var array|Aop\JoinPoint\BeforeMethod[]
	 */
	public $calls = array();

	public $modifyArgs = FALSE;



	/**
	 * @Aop\Before("method(KdybyTests\Aop\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\BeforeMethod $before)
	{
		$this->calls[] = $before;

		if (is_array($this->modifyArgs)) {
			foreach ($this->modifyArgs as $i => $val) {
				$before->setArgument($i, $val);
			}
		}
	}

}



class AroundAspect extends Nette\Object
{

	/**
	 * @var array|Aop\JoinPoint\AroundMethod[]
	 */
	public $calls = array();

	public $modifyArgs = FALSE;

	public $modifyReturn = FALSE;



	/**
	 * @Aop\Around("method(KdybyTests\Aop\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AroundMethod $around)
	{
		$this->calls[] = $around;

		if (is_array($this->modifyArgs)) {
			foreach ($this->modifyArgs as $i => $val) {
				$around->setArgument($i, $val);
			}
		}

		$result = $around->proceed();

		if ($this->modifyReturn !== FALSE) {
			$result = $this->modifyReturn;
		}

		return $result;
	}

}



class AroundBlockingAspect extends Nette\Object
{

	/**
	 * @var array|Aop\JoinPoint\AroundMethod[]
	 */
	public $calls = array();

	public $modifyArgs = FALSE;



	/**
	 * @Aop\Around("method(KdybyTests\Aop\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AroundMethod $around)
	{
		$this->calls[] = $around;

		if (is_array($this->modifyArgs)) {
			foreach ($this->modifyArgs as $i => $val) {
				$around->setArgument($i, $val);
			}
		}

		// do not call proceed
	}

}



class AfterReturningAspect extends Nette\Object
{

	/**
	 * @var array|Aop\JoinPoint\AfterReturning[]
	 */
	public $calls = array();

	public $modifyReturn = FALSE;



	/**
	 * @Aop\AfterReturning("method(KdybyTests\Aop\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AfterReturning $after)
	{
		$this->calls[] = $after;

		if ($this->modifyReturn !== FALSE) {
			$after->setResult($this->modifyReturn);
		}
	}

}



class AfterThrowingAspect extends Nette\Object
{

	/**
	 * @var array|Aop\JoinPoint\AfterThrowing[]
	 */
	public $calls = array();



	/**
	 * @Aop\AfterThrowing("method(KdybyTests\Aop\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AfterThrowing $after)
	{
		$this->calls[] = $after;
	}

}



class AfterAspect extends Nette\Object
{

	/**
	 * @var array|Aop\JoinPoint\AfterMethod[]
	 */
	public $calls = array();



	/**
	 * @Aop\After("method(KdybyTests\Aop\CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AfterMethod $after)
	{
		$this->calls[] = $after;
	}

}
