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
use Kdyby\Aop\PhpGenerator\NamespaceBlock;
use Kdyby\Aop\PhpGenerator\PhpFile;
use Kdyby\Aop\Pointcut;
use Nette;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AopExtension extends Nette\DI\CompilerExtension
{
	const CG_INJECT_METHOD = '__injectAopContainer';

	/**
	 * @var array
	 */
	private $classes = array();

	/**
	 * @var array
	 */
	private $serviceDefinitions = array();

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

		if (!empty($builder->parameters['container']['class'])) {
			$namespace = $builder->parameters['container']['class'];

		} else {
			$namespace = $builder->getClassName(); //Nette 2.3
		}

		$file = new PhpFile();
		$cg = $file->getNamespace('Kdyby\\Aop_CG\\' . $namespace);
		$cg->imports[] = 'Kdyby\Aop\Pointcut\Matcher\Criteria';
		$cg->imports[] = 'Symfony\Component\PropertyAccess\PropertyAccess';

		foreach ($this->findAdvisedMethods() as $serviceId => $pointcuts) {
			$service = $this->getWrappedDefinition($serviceId);
			$advisedClass = AdvisedClassType::fromServiceDefinition($service);
			$constructorInject = FALSE;

			foreach ($pointcuts as $methodAdvices) {
				/** @var Pointcut\Method $targetMethod */
				$targetMethod = reset($methodAdvices)->getTargetMethod();

				$newMethod = $targetMethod->getPointcutCode();
				$advisedClass->setMethodInstance($newMethod);
				$advisedClass->generatePublicProxyMethod($targetMethod->getCode());
				$constructorInject = $constructorInject || strtolower($newMethod->name) === '__construct';

				/** @var AdviceDefinition[] $methodAdvices */
				foreach ($methodAdvices as $adviceDef) {
					$newMethod->addAdvice($adviceDef);
				}
			}

			$cg->addClass($advisedClass);
			$this->patchService($serviceId, $advisedClass, $cg, $constructorInject);
		}

		if (!$cg->classes) {
			return;
		}

		require_once ($this->compiledFile = $this->writeGeneratedCode($file));
	}



	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		if (!$this->compiledFile) {
			return;
		}

		$init = $class->methods['initialize'];
		$init->addBody('require_once ?;', array($this->compiledFile));
	}



	private function patchService($serviceId, Code\ClassType $advisedClass, NamespaceBlock $cg, $constructorInject = FALSE)
	{
		static $publicSetup;

		if ($publicSetup === NULL) {
			$refl = new Nette\Reflection\Property('Nette\DI\ServiceDefinition', 'setup');
			$publicSetup = $refl->isPublic();
		}

		$def = $this->getContainerBuilder()->getDefinition($serviceId);
		if ($def->factory) {
			$def->factory->entity = $cg->name . '\\' . $advisedClass->getName();

		} else {
			$def->setFactory($cg->name . '\\' . $advisedClass->getName());
		}

		if (!$constructorInject) {
			$statement = new Nette\DI\Statement(AdvisedClassType::CG_INJECT_METHOD, array('@Nette\DI\Container'));

			if ($publicSetup) {
				array_unshift($def->setup, $statement);

			} else {
				$setup = $def->getSetup();
				array_unshift($setup, $statement);
				$def->setSetup($setup);
			}
		}
	}



	private function writeGeneratedCode(PhpFile $file)
	{
		$builder = $this->getContainerBuilder();

		if (!is_dir($tempDir = $builder->expand('%tempDir%/cache/_Kdyby.Aop'))) {
			mkdir($tempDir, 0777, TRUE);
		}

		$key = md5(serialize($builder->parameters) . serialize(array_keys(reset($file->namespaces)->classes)));
		file_put_contents($cached = $tempDir . '/' . $key . '.php', (string) $file);

		return $cached;
	}



	/**
	 * @return array
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
			$this->classes = array();
			foreach ($this->getContainerBuilder()->getDefinitions() as $name => $def) {
				$class = $def->class;
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
		if (!isset($this->serviceDefinitions[$id])) {
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
