<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\Pointcut;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Kdyby;
use Kdyby\Aop\InvalidAspectExceptions;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AspectAnalyzer
{
	use Nette\SmartObject;

	/**
	 * @var \Doctrine\Common\Annotations\Reader
	 */
	private $annotationReader;

	/**
	 * @var Parser
	 */
	private $pointcutParser;



	public function __construct(Parser $parser, Reader $reader = NULL)
	{
		$this->annotationReader = $reader ?: new AnnotationReader();
		$this->pointcutParser = $parser;
	}



	/**
	 * @param ServiceDefinition $service
	 * @throws \Kdyby\Aop\InvalidAspectExceptions
	 * @return array|Filter[]
	 */
	public function analyze(ServiceDefinition $service)
	{
		$pointcuts = [];
		foreach ($service->getOpenMethods() as $method) {
			if (!$annotations = $this->filterAopAnnotations($method->getAnnotations($this->annotationReader))) {
				continue;
			}

			$rules = [];
			foreach ($annotations as $annotation) {
				$rules[get_class($annotation)] = $this->pointcutParser->parse($annotation->value);
			}

			$pointcuts[$method->getName()] = $rules;
		}

		if (empty($pointcuts)) {
			throw new InvalidAspectExceptions("The aspect {$service->typeReflection} has no pointcuts defined.");
		}

		return $pointcuts;
	}



	/**
	 * @param array $annotations
	 * @return array|Kdyby\Aop\AdviceAnnotation[]
	 */
	private function filterAopAnnotations(array $annotations)
	{
		return array_filter($annotations, function ($annotation) {
			return $annotation instanceof Kdyby\Aop\AdviceAnnotation;
		});
	}

}
