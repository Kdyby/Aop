<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Kdyby;
use Kdyby\Aop\Pointcut;
use Nette;
use Nette\PhpGenerator as Code;



if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']); // fuck you
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AopExtension extends Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	private $classes = array();

	/**
	 * @var array
	 */
	private $serviceDefinitions = array();



	public function loadConfiguration()
	{
		$this->registerAspectsExtension();
	}



	private function registerAspectsExtension()
	{
		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof AspectsExtension) {
				return;
			}
		}

		$this->compiler->addExtension('aspects', new AspectsExtension());
	}



	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$file = new Kdyby\Aop\PhpGenerator\PhpFile();
		$cg = $file->getNamespace('Kdyby\Aop_CG');

		foreach ($this->findAdvisedMethods() as $name => $advices) {
			$service = $this->getWrappedDefinition($name);
			$advisedClass = $cg->addClass($this->prepareAdvisedClass($service));

			/** @var array|AdviceDefinition[] $advices */
			foreach ($advices as $advice) {

			}

			$def = $builder->getDefinition($name);
			$def->setClass($cg->name . '\\' . $advisedClass->getName());
		}

		// write php file to cache
	}



	/**
	 * @param Pointcut\ServiceDefinition $service
	 * @return Code\ClassType
	 */
	private function prepareAdvisedClass(Pointcut\ServiceDefinition $service)
	{
		$originalType = $service->getTypeReflection();

		$advisedClass = new Code\ClassType();
		$advisedClass->setName(str_replace(array('\\', '.'), '_', "{$originalType}Class_{$service->serviceId}"))
			->setExtends('\\' . $originalType->getName())
			->setFinal(TRUE);

		$advisedClass->addProperty('_kdyby_aopContainer')
			->setVisibility('private')
			->addDocument('@var \Nette\DI\Container|\SystemContainer');
		$advisedClass->addProperty('_kdyby_aopAdvices', array())
			->setVisibility('private');

		$injectMethod = $advisedClass->addMethod('__injectAopContainer');
		$injectMethod->addParameter('container')->setTypeHint('Nette\DI\Container');
		$injectMethod->addDocument('@internal');
		$injectMethod->addBody('$this->_kdyby_aopContainer = $container;');

		return $advisedClass;
	}



	/**
	 * @return array|AdviceDefinition[][]
	 */
	private function findAdvisedMethods()
	{
		$builder = $this->getContainerBuilder();
		$builder->prepareClassList();

		$annotationReader = new AnnotationReader();
		$matcherFactory = new Pointcut\MatcherFactory($builder, $annotationReader);
		$analyzer = new Pointcut\AspectAnalyzer(new Pointcut\Parser($matcherFactory), $annotationReader);

		$advisedMethods = array();
		$this->classes = NULL;

		foreach ($builder->findByTag(self::ASPECT_TAG) as $aspectId => $meta) {
			$advices = $analyzer->analyze($aspectService = $this->getWrappedDefinition($aspectId));

			foreach ($advices as $advice => $filter) {
				if ($types = $filter->listAcceptedTypes()) {
					$services = $this->findByTypes($types);

				} else { // this cannot be done in any other way sadly...
					$services = array_keys($builder->getDefinitions());
				}

				foreach ($services as $serviceId) {
					foreach ($this->getWrappedDefinition($serviceId)->match($filter) as $method) {
						$advisedMethods[$serviceId][] = new AdviceDefinition($method, $aspectService->openMethods[$advice]);
					}
				}
			}
		}

		return $advisedMethods;
	}



	/**
	 * @param array|string $types
	 * @return array
	 */
	private function findByTypes($types)
	{
		if ($this->classes === NULL) {
			$this->classes = array();
			foreach ($this->getContainerBuilder()->getDefinitions() as $name => $def) {
				$class = $def->implement ? : $def->class;
				if ($def->autowired && $class) {
					foreach (class_parents($class) + class_implements($class) + array($class) as $parent) {
						$this->classes[strtolower($parent)][] = (string) $name;
					}
				}
			}
		}

		$services = array();
		foreach (array_filter((array)$types) as $type) {
			$lower = ltrim(strtolower($type), '\\');
			if (isset($this->classes[$lower])) {
				$services = array_merge($services, $this->classes[$lower]);
			}
		}

		return array_unique($services);
	}



	/**
	 * @param $id
	 * @return Pointcut\ServiceDefinition
	 */
	private function getWrappedDefinition($id)
	{
		if (isset($this->serviceDefinitions[$id])) {
			$this->serviceDefinitions[$id] = new Pointcut\ServiceDefinition($this->getContainerBuilder()->getDefinition($id), $id);
		}

		return $this->serviceDefinitions[$id];
	}



	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('aop', new AopExtension());
		};
	}

}
