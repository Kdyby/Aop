<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\Pointcut;

use Kdyby;
use Nette;



/**
 * Wraps the Nette's ServiceDefinition, allowing safer manipulation and analysis.
 *
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @property string $serviceId
 * @property array|Method[] $openMethods
 * @property \ReflectionClass $typeReflection
 */
class ServiceDefinition
{

	use Nette\SmartObject;

	/**
	 * @var \Nette\DI\ServiceDefinition
	 */
	private $serviceDefinition;

	/**
	 * @var \ReflectionClass
	 */
	private $originalType;

	/**
	 * @var array|Nette\PhpGenerator\Method[]
	 */
	private $openMethods;

	/**
	 * @var array
	 */
	private $typesWithing;

	/**
	 * @var string
	 */
	private $serviceId;



	public function __construct(Nette\DI\Definitions\Definition $def, $serviceId)
	{
		$this->serviceDefinition = $def;

		if (empty($def->getType())) {
			throw new Kdyby\Aop\InvalidArgumentException("Given service definition has unresolved class, please specify service type explicitly.");
		}

		$this->originalType = new \ReflectionClass($def->getType());
		$this->serviceId = $serviceId;
	}



	/**
	 * @return string
	 */
	public function getServiceId()
	{
		return $this->serviceId;
	}


	public function getTypeReflection(): \ReflectionClass
	{
		return $this->originalType;
	}



	/**
	 * @return array
	 */
	public function getTypesWithin()
	{
		if ($this->typesWithing !== NULL) {
			return $this->typesWithing;
		}

		return $this->typesWithing = class_parents($class = $this->originalType->getName()) + class_implements($class) + [$class => $class];
	}



	/**
	 * @return array|Method[]
	 */
	public function getOpenMethods()
	{
		if ($this->openMethods !== NULL) {
			return $this->openMethods;
		}

		$this->openMethods = [];
		$type = $this->originalType;
		do {
			foreach ($type->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED) as $method) {
				if ($method->isFinal()) {
					continue; // todo: maybe in next version
				}

				if (!isset($this->openMethods[$method->getName()])) {
					$this->openMethods[$method->getName()] = new Method($method, $this);
				}
			}

		} while (!empty($type->getParentClass()) && $type = $type->getParentClass());

		return $this->openMethods;
	}



	/**
	 * @param Filter $rule
	 * @return array|Method[]
	 */
	public function match(Filter $rule)
	{
		$matching = [];
		foreach ($this->getOpenMethods() as $method) {
			if ($rule->matches($method)) {
				$matching[] = $method;
			}
		}

		return $matching;
	}

}
