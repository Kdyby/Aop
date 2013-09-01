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
class AdvisedClassType extends Code\ClassType
{

	const CG_INJECT_METHOD = '__injectAopContainer';
	const CG_PUBLIC_PROXY_PREFIX = '__publicAopProxy_';



	/**
	 * @param Code\Method $method
	 * @return Code\Method
	 */
	public function setMethodInstance(Code\Method $method)
	{
		$methods = array($method->getName() => $method) + $this->getMethods();
		$this->setMethods($methods);

		return $method;
	}



	public function generatePublicProxyMethod(Code\Method $method)
	{
		$originalName = $method->getName();
		$method->setName(self::CG_PUBLIC_PROXY_PREFIX . $originalName);
		$method->setVisibility('public');

		$argumentsPass = array();
		foreach ($method->getParameters() as $parameter) {
			/** @var Code\Parameter $parameter */
			$argumentsPass[] = '$' . $parameter->getName();
		}
		$method->addBody('return parent::?(?);', array($method->getName(), new Code\PhpLiteral(implode(', ', $argumentsPass))));

		$this->setMethodInstance($method);
	}



	/**
	 * @param Kdyby\Aop\Pointcut\ServiceDefinition $service
	 * @return AdvisedClassType
	 */
	public static function fromServiceDefinition(Kdyby\Aop\Pointcut\ServiceDefinition $service)
	{
		$originalType = $service->getTypeReflection();

		$class = new static();
		/** @var AdvisedClassType $class */

		$class->setName(str_replace(array('\\', '.'), '_', "{$originalType}Class_{$service->serviceId}"));
		$class->setExtends('\\' . $originalType->getName());
		$class->setFinal(TRUE);

		$class->addProperty('_kdyby_aopContainer')
			->setVisibility('private')
			->addDocument('@var \Nette\DI\Container|\SystemContainer');
		$class->addProperty('_kdyby_aopAdvices', array())
			->setVisibility('private');

		$injectMethod = $class->addMethod(self::CG_INJECT_METHOD);
		$injectMethod->addParameter('container')->setTypeHint('\Nette\DI\Container');
		$injectMethod->addDocument('@internal');
		$injectMethod->addBody('$this->_kdyby_aopContainer = $container;');

		$providerMethod = $class->addMethod('__getAdvice');
		$providerMethod->setVisibility('private');
		$providerMethod->addParameter('name');
		$providerMethod->addBody(
			'if (!isset($this->_kdyby_aopAdvices[$name])) {' . "\n\t" .
			'$this->_kdyby_aopAdvices[$name] = $this->_kdyby_aopContainer->createService($name);' . "\n}\n\n" .
			'return $this->_kdyby_aopAdvices[$name];'
		);

		return $class;
	}

}
