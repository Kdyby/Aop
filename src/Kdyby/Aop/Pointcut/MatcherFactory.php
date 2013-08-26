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
use Nette;
use Nette\ObjectMixin;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class MatcherFactory extends Nette\Object
{

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
	private $cache = array();



	public function __construct(Nette\DI\ContainerBuilder $builder, Reader $annotationReader)
	{
		$this->builder = $builder;
		$this->annotationReader = $annotationReader;
	}



	/**
	 * @param string $type
	 * @param string $arg
	 * @return Rule
	 */
	public function getMatcher($type, $arg)
	{
		if (!isset($this->cache[$type][$arg])) {
			$this->cache[$type][$arg] = call_user_func(array($this, 'create' . ucfirst($type)), $arg);
		}

		return $this->cache[$type][$arg];
	}



	public function createClass($class)
	{
		return new Matcher\ClassMatcher($class);
	}



	public function createMethod($method)
	{
		return new Matcher\MethodMatcher($method);
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
