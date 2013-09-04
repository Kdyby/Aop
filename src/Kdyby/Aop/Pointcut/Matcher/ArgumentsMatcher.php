<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\Pointcut\Matcher;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ArgumentsMatcher extends Nette\Object implements Kdyby\Aop\Pointcut\Filter, Kdyby\Aop\Pointcut\RuntimeFilter
{

	/**
	 * @var Criteria
	 */
	private $arguments;

	/**
	 * @var \Nette\DI\ContainerBuilder
	 */
	private $builder;



	public function __construct(Criteria $criteria, Nette\DI\ContainerBuilder $builder)
	{
		$this->arguments = $criteria;
		$this->builder = $builder;
	}



	public function matches(Kdyby\Aop\Pointcut\Method $method)
	{
		return TRUE;
	}



	/**
	 * @return string
	 */
	public function createCondition()
	{
		return $this->arguments->serialize($this->builder);
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return FALSE;
	}

}
