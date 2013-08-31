<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Aop;

use Doctrine\Common\Annotations\Reader;
use Nette;
use Kdyby\Aop;



class LoggingAspect extends Nette\Object
{

	/**
	 * @Aop\Before("method(Nette\Application\Application::processRequest)")
	 */
	public function log(Aop\JoinPoint\BeforeMethod $before)
	{
		list($request) = $before->getArguments();
		// log it somewhere
	}

}



class AclAspect extends Nette\Object
{

	/**
	 * @var Reader
	 */
	private $annotationReader;



	public function __construct(Reader $reader)
	{
		$this->annotationReader = $reader;
	}



	/**
	 * @Aop\Around("method(Nette\Application\IPresenter->[render|action|handle]*())")
	 */
	public function protect(Aop\JoinPoint\AroundMethod $around)
	{
		$annotations = $this->annotationReader->getMethodAnnotations($around->getTargetReflection());
		// rules check an

		if (FALSE) {
			throw new Nette\Application\ForbiddenRequestException();
		}
	}

}
