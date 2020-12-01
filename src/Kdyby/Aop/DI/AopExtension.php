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
use Doctrine\Common\Annotations\AnnotationRegistry;
use Kdyby;
use Kdyby\Aop\PhpGenerator\AdvisedClassType;
use Kdyby\Aop\Pointcut;
use Nette;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AopExtension extends Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	private $classes = [];

	/**
	 * @var array
	 */
	private $serviceDefinitions = [];

	/**
	 * @var string
	 */
	private $compiledFile;



	public function loadConfiguration()
	{
		AnnotationRegistry::registerLoader("class_exists");
		AnnotationReader::addGlobalIgnoredName('persistent');
	}



	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$this->compiledFile = NULL;

		$namespace = 'Container_' . substr(md5(serialize([
			$builder->parameters,
			$this->compiler->exportDependencies(),
			PHP_VERSION_ID - PHP_RELEASE_VERSION,
		])), 0, 10);

		$file = new Code\PhpFile();
		$cg = $file->addNamespace('Kdyby\\Aop_CG\\' . $namespace);
		$cg->addUse('Kdyby\Aop\Pointcut\Matcher\Criteria');
		$cg->addUse('Symfony\Component\PropertyAccess\PropertyAccess');

		foreach ($this->findAdvisedMethods() as $serviceId => $pointcuts) {
			$service = $this->getWrappedDefinition($serviceId);
			$advisedClass = AdvisedClassType::fromServiceDefinition($service, $cg);
			$constructorInject = FALSE;

			foreach ($pointcuts as $methodAdvices) {
				/** @var Pointcut\Method $targetMethod */
				$targetMethod = reset($methodAdvices)->getTargetMethod();

				$newMethod = $targetMethod->getPointcutCode();
				AdvisedClassType::setMethodInstance($advisedClass, $newMethod->getMethod());
				AdvisedClassType::generatePublicProxyMethod($advisedClass, $targetMethod->getCode()->getMethod());
				$constructorInject = $constructorInject || strtolower($newMethod->getName()) === '__construct';

				/** @var AdviceDefinition[] $methodAdvices */
				foreach ($methodAdvices as $adviceDef) {
					$newMethod->addAdvice($adviceDef);
				}
				$newMethod->beforePrint();
			}

			$this->patchService($serviceId, $advisedClass, $cg, $constructorInject);
		}

		if (!$cg->getClasses()) {
			return;
		}

		require_once ($this->compiledFile = $this->writeGeneratedCode($file, $cg));
	}



	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		if (!$this->compiledFile) {
			return;
		}

		$init = $class->methods['initialize'];
		$init->addBody('require_once ?;', [$this->compiledFile]);
	}



	private function patchService($serviceId, Code\ClassType $advisedClass, Code\PhpNamespace $cg, $constructorInject = FALSE)
	{
		static $publicSetup;
		if ($publicSetup === NULL) {
			$refl = new Nette\Reflection\Property('Nette\DI\ServiceDefinition', 'setup');
			$publicSetup = $refl->isPublic();
		}

		$def = $this->getContainerBuilder()->getDefinition($serviceId);
		if($def instanceof Nette\DI\Definitions\FactoryDefinition) {
			$def = $def->getResultDefinition();
		}
		$factory = $def->getFactory();
		if ($factory) {
			$def->setFactory(new Nette\DI\Statement($cg->getName() . '\\' . $advisedClass->getName(), $factory->arguments));

		} else {
			$def->setFactory($cg->getName() . '\\' . $advisedClass->getName());
		}

		if (!$constructorInject) {
			$statement = new Nette\DI\Statement(AdvisedClassType::CG_INJECT_METHOD, ['@Nette\DI\Container']);

			if ($publicSetup) {
				array_unshift($def->setup, $statement);

			} else {
				$setup = $def->getSetup();
				array_unshift($setup, $statement);
				$def->setSetup($setup);
			}
		}
	}



	private function writeGeneratedCode(Code\PhpFile $file, Code\PhpNamespace $namespace)
	{
		$builder = $this->getContainerBuilder();

		if (!is_dir($tempDir = Nette\DI\Helpers::expand('%tempDir%/cache/_Kdyby.Aop', $builder->parameters))) {
			mkdir($tempDir, 0777, TRUE);
		}

		$key = md5(serialize($builder->parameters) . serialize(array_keys($namespace->getClasses())));
		file_put_contents($cached = $tempDir . '/' . $key . '.php', (string) $file);

		return $cached;
	}



	/**
	 * @return array
	 */
	private function findAdvisedMethods()
	{
		$builder = $this->getContainerBuilder();
		$builder->resolve();

		$annotationReader = new AnnotationReader();
		$matcherFactory = new Pointcut\MatcherFactory($builder, $annotationReader);
		$analyzer = new Pointcut\AspectAnalyzer(new Pointcut\Parser($matcherFactory), $annotationReader);

		$advisedMethods = [];
		$this->classes = NULL;

		foreach ($builder->findByTag(AspectsExtension::ASPECT_TAG) as $aspectId => $meta) {
			$advices = $analyzer->analyze($aspectService = $this->getWrappedDefinition($aspectId));

			foreach ($advices as $advice => $filters) {
				/** @var Pointcut\Filter[] $filters */
				foreach ($filters as $adviceType => $filter) {
					if ($types = $filter->listAcceptedTypes()) {
						$services = $this->findByTypes($types);

					} else { // this cannot be done in any other way sadly...
						$services = array_keys($builder->getDefinitions());
					}

					foreach ($services as $serviceId) {
						foreach ($this->getWrappedDefinition($serviceId)->match($filter) as $method) {
							$advisedMethods[$serviceId][$method->getName()][] = new AdviceDefinition($adviceType, $method, $aspectService->openMethods[$advice], $filter);
						}
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
			$this->classes = [];
			foreach ($this->getContainerBuilder()->getDefinitions() as $name => $def) {
				if ($def->getType() !== NULL) {
					$additional = [];
					if ($def instanceof Nette\DI\Definitions\FactoryDefinition) {
						$this->classes[strtolower($def->getResultDefinition()->getType())][] = (string) $name;
					}
					foreach (class_parents($def->getType()) + class_implements($def->getType()) + [$def->getType()] + $additional as $parent) {
						$this->classes[strtolower($parent)][] = (string) $name;
					}
				}
			}
		}

		$services = [];
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
		if (!isset($this->serviceDefinitions[$id])) {
			$def =$this->getContainerBuilder()->getDefinition($id);
			if ($def instanceof Nette\DI\Definitions\FactoryDefinition) {
				$def = $def->getResultDefinition();
			}
			$this->serviceDefinitions[$id] = new Pointcut\ServiceDefinition($def, $id);
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
