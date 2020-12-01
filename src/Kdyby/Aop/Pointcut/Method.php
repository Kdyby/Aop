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
 * @property-read array|string[] $typesWithin
 */
class Method
{

	use Nette\SmartObject;

	const VISIBILITY_PUBLIC = 'public';
	const VISIBILITY_PROTECTED = 'protected';
	const VISIBILITY_PRIVATE = 'private';

	/**
	 * @var Nette\Reflection\Method
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



	public function getName(): string
	{
		return $this->method->getName();
	}



	public function getVisibility(): string
	{
		return $this->method->isPublic() ? self::VISIBILITY_PUBLIC
			: ($this->method->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PRIVATE);
	}



	public function getClassName(): string
	{
		return $this->serviceDefinition->getTypeReflection()->getName();
	}



	public function getTypesWithin(): array
	{
		return $this->serviceDefinition->getTypesWithin();
	}



	/**
	 * @param Reader $reader
	 * @return object[]
	 */
	public function getAnnotations(Reader $reader): array
	{
		return $reader->getMethodAnnotations($this->method);
	}



	/**
	 * @return array|object[]
	 */
	public function getClassAnnotations(Reader $reader): array
	{
		return $reader->getClassAnnotations($this->serviceDefinition->getTypeReflection());
	}



	public function getServiceDefinition(): ServiceDefinition
	{
		return $this->serviceDefinition;
	}


	public function getCode(): PointcutMethod
	{
		return PointcutMethod::expandTypeHints($this->method, PointcutMethod::from($this->method));
	}


	public function getPointcutCode(): PointcutMethod
	{
		return PointcutMethod::expandTypeHints($this->method, PointcutMethod::from($this->method));
	}


	public function getParameterNames(): array
	{
		return array_keys($this->method->getParameters());
	}

	public function unwrap(): Nette\Reflection\Method
	{
		return $this->method;
	}

}
