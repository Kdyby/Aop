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
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class MatcherFactory
{

	use Nette\SmartObject;

	/**
	 * @var \Nette\DI\ContainerBuilder
	 */
	private $builder;

	/**
	 * @var \Doctrine\Common\Annotations\Reader
	 */
	private $annotationReader;

	/**
	 * @var array
	 */
	private $cache = [];



	public function __construct(Nette\DI\ContainerBuilder $builder, Reader $annotationReader = NULL)
	{
		$this->builder = $builder;
		$this->annotationReader = $annotationReader ?: new AnnotationReader();
	}



	/**
	 * @param string $type
	 * @param string $arg
	 * @return Filter
	 */
	public function getMatcher($type, $arg)
	{
		if (!isset($this->cache[$type][(string) $arg])) {
			$this->cache[$type][(string) $arg] = call_user_func([$this, 'create' . ucfirst($type)], $arg);
		}

		return $this->cache[$type][(string) $arg];
	}



	public function createClass($class)
	{
		return new Matcher\WithinMatcher($class);
	}



	public function createMethod($method)
	{
		return new Matcher\MethodMatcher($method);
	}



	public function createArguments($criteria)
	{
		return new Matcher\EvaluateMatcher($criteria, $this->builder);
	}



	public function createWithin($within)
	{
		return new Matcher\WithinMatcher($within);
	}



	public function createFilter($filterClass)
	{
		return new Matcher\FilterMatcher($filterClass);
	}



	public function createSetting($setting)
	{
		return new Matcher\SettingMatcher($setting, $this->builder);
	}



	public function createEvaluate($evaluate)
	{
		return new Matcher\EvaluateMatcher($evaluate, $this->builder);
	}



	public function createClassAnnotatedWith($annotation)
	{
		return new Matcher\ClassAnnotateWithMatcher($annotation, $this->annotationReader);
	}



	public function createMethodAnnotatedWith($annotation)
	{
		return new Matcher\MethodAnnotateWithMatcher($annotation, $this->annotationReader);
	}

}
