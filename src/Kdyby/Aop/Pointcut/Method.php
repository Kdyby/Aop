<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\Pointcut;

use Doctrine\Common\Annotations\Reader;
use Kdyby;
use Kdyby\Aop\PhpGenerator\PointcutMethod;
use Nette;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @property array|string[] $typesWithin
 * @property-read array|string[] $typesWithin
 */
class Method
{

	use Nette\SmartObject;

	const VISIBILITY_PUBLIC = 'public';
	const VISIBILITY_PROTECTED = 'protected';
	const VISIBILITY_PRIVATE = 'private';

	/**
	 * @var \Nette\Reflection\Method
	 */
	private $method;

	/**
	 * @var ServiceDefinition
	 */
	private $serviceDefinition;



	public function __construct(Nette\Reflection\Method $method, ServiceDefinition $serviceDefinition)
	{
		$this->method = $method;
		$this->serviceDefinition = $serviceDefinition;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->method->getName();
	}



	/**
	 * @return string
	 */
	public function getVisibility()
	{
		return $this->method->isPublic() ? self::VISIBILITY_PUBLIC
			: ($this->method->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PRIVATE);
	}



	/**
	 * @return string
	 */
	public function getClassName()
	{
		return $this->serviceDefinition->getTypeReflection()->getName();
	}



	/**
	 * @return array
	 */
	public function getTypesWithin()
	{
		return $this->serviceDefinition->getTypesWithin();
	}



	/**
	 * @param Reader $reader
	 * @return array|object[]
	 */
	public function getAnnotations(Reader $reader)
	{
		return $reader->getMethodAnnotations($this->method);
	}



	/**
	 * @param Reader $reader
	 * @return array|object[]
	 */
	public function getClassAnnotations(Reader $reader)
	{
		return $reader->getClassAnnotations($this->serviceDefinition->getTypeReflection());
	}



	/**
	 * @return \Kdyby\Aop\Pointcut\ServiceDefinition
	 */
	public function getServiceDefinition()
	{
		return $this->serviceDefinition;
	}



	/**
	 * @return Code\Method
	 */
	public function getCode()
	{
		return PointcutMethod::expandTypeHints($this->method, Code\Method::from($this->method));
	}



	/**
	 * @return PointcutMethod
	 */
	public function getPointcutCode()
	{
		return PointcutMethod::expandTypeHints($this->method, PointcutMethod::from($this->method));
	}



	/**
	 * @return array
	 */
	public function getParameterNames()
	{
		return array_keys($this->method->getParameters());
	}



	/**
	 * @return Nette\Reflection\Method
	 */
	public function unwrap()
	{
		return $this->method;
	}

}
