<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\PhpGenerator;

use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AdvisedClassType
{

	use Nette\SmartObject;

	const CG_INJECT_METHOD = '__injectAopContainer';
	const CG_PUBLIC_PROXY_PREFIX = '__publicAopProxy_';



	/**
	 * @param Code\Method $method
	 * @return Code\Method
	 */
	public static function setMethodInstance(Code\ClassType $class, Code\Method $method)
	{
		$methods = [$method->getName() => $method] + $class->getMethods();
		$class->setMethods($methods);

		return $method;
	}



	public static function generatePublicProxyMethod(Code\ClassType $class, Code\Method $originalMethod)
	{
		$proxyMethod = new Code\Method(self::CG_PUBLIC_PROXY_PREFIX .  $originalMethod->getName());

		$proxyMethod->setVisibility('public');
		$proxyMethod->setComment("@internal\n@deprecated");

		$argumentsPass = [];
		$args = [];
		foreach ($originalMethod->getParameters() as $parameter) {
			/** @var Code\Parameter $parameter */
			$argumentsPass[] = '$' . $parameter->getName();
			$args[$parameter->getName()] = $parameter;
		}
		$proxyMethod->addBody('return parent::?(?);', [ $originalMethod->getName(), new Code\PhpLiteral(implode(', ', $argumentsPass))]);

		$proxyMethod->setParameters($args);
		self::setMethodInstance($class, $proxyMethod);
	}



	/**
	 * @param Kdyby\Aop\Pointcut\ServiceDefinition $service
	 * @param Code\PhpNamespace $namespace
	 * @return Code\ClassType
	 */
	public static function fromServiceDefinition(Kdyby\Aop\Pointcut\ServiceDefinition $service, Code\PhpNamespace $namespace)
	{
		$originalType = $service->getTypeReflection();

		$class = $namespace->addClass(str_replace(['\\', '.'], '_', "{$originalType}Class_{$service->serviceId}"));

		$class->setExtends('\\' . $originalType->getName());
		$class->setFinal(TRUE);

		$class->addProperty('_kdyby_aopContainer')
			->setVisibility('private')
			->addComment('@var \Nette\DI\Container|\SystemContainer');
		$class->addProperty('_kdyby_aopAdvices', [])
			->setVisibility('private');

		$injectMethod = $class->addMethod(self::CG_INJECT_METHOD);
		$injectMethod->addParameter('container')->setTypeHint('\Nette\DI\Container');
		$injectMethod->setComment("@internal\n@deprecated");
		$injectMethod->addBody('$this->_kdyby_aopContainer = $container;');

		$providerMethod = $class->addMethod('__getAdvice');
		$providerMethod->setVisibility('private');
		$providerMethod->addParameter('name');
		$providerMethod->addBody(
			'if (!isset($this->_kdyby_aopAdvices[$name])) {' . "\n\t" .
			'$this->_kdyby_aopAdvices[$name] = $this->_kdyby_aopContainer->createService($name);' . "\n}\n\n" .
			'return $this->_kdyby_aopAdvices[$name];'
		);

		if (!$originalType->hasMethod('__sleep')) {
			$properties = [];
			foreach ($originalType->getProperties() as $property) {
				if ($property->isStatic()) {
					continue;
				}

				$properties[] = "'" . $property->getName() . "'";
			}

			$sleep = $class->addMethod('__sleep');
			$sleep->setBody('return array(?);', [new Code\PhpLiteral(implode(', ', $properties))]);
		}

		return $class;
	}

}
