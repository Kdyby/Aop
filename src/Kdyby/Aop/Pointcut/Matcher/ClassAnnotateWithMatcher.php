<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\Pointcut\Matcher;

use Doctrine\Common\Annotations\Reader;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ClassAnnotateWithMatcher implements Kdyby\Aop\Pointcut\Filter
{

	use Nette\SmartObject;

	/**
	 * @var string
	 */
	private $annotationClass;

	/**
	 * @var \Doctrine\Common\Annotations\Reader
	 */
	private $reader;



	public function __construct($annotationClass, Reader $reader)
	{
		$this->annotationClass = $annotationClass;
		$this->reader = $reader;
	}



	public function matches(Kdyby\Aop\Pointcut\Method $method)
	{
		foreach ($method->getClassAnnotations($this->reader) as $annotation) {
			if (!$annotation instanceof $this->annotationClass) {
				continue;
			}

			return TRUE;
		}

		return FALSE;
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return FALSE;
	}

}
