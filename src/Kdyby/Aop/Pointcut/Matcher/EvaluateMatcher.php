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
class EvaluateMatcher extends Nette\Object implements Kdyby\Aop\Pointcut\Filter
{

	/**
	 * @var Criteria
	 */
	private $evaluate;



	public function __construct(Criteria $criteria)
	{
		$this->evaluate = $criteria;
	}



	public function matches(Kdyby\Aop\Pointcut\Method $method)
	{
		return TRUE; // todo: implement
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return FALSE;
	}

}
