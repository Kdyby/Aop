<?php

/**
 * Test: Kdyby\Aop\PointcutRules.
 *
 * @testCase KdybyTests\Aop\PointcutRulesTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Aop
 */

namespace KdybyTests\Aop;

use Doctrine\Common\Annotations\AnnotationReader;
use Kdyby;
use Kdyby\Aop\Pointcut;
use Kdyby\Aop\Pointcut\Matcher;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/files/pointcut-examples.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PointcutRulesTest extends Tester\TestCase
{

	public function dataMatchClass()
	{
		$data = array();

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\ClassMatcher('KdybyTests\Aop\SmegHead'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\ClassMatcher('KdybyTests\Aop\*'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\ClassMatcher('*'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\ClassMatcher('KdybyTests\Aop\SmegHead'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		return $data;
	}



	/**
	 * @dataProvider dataMatchClass
	 */
	public function testMatchClass($expected, Kdyby\Aop\Pointcut\Filter $rules, Kdyby\Aop\Pointcut\ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	public function dataMatchWithin()
	{
		$data = array();

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('KdybyTests\Aop\Cat'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('KdybyTests\Aop\Cat'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('Nette\Templating\*'))),
			$this->createDefinition('KdybyTests\Aop\CustomTemplate'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('Nette\Templating\I*'))),
			$this->createDefinition('Nette\Templating\FileTemplate'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('Nette\Templating\*'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		return $data;
	}



	/**
	 * @dataProvider dataMatchWithin
	 */
	public function testMatchWithin($expected, Kdyby\Aop\Pointcut\Filter $rules, Kdyby\Aop\Pointcut\ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	public function dataMatchMethod()
	{
		$data = array();

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('injectFoo'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('public injectFoo'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('protected injectFoo'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('private injectFoo'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('*Calculation'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('protected *Calculation'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('inject*'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('[inject]Bar'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('[inject]Bar'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('[inject]Bar'))),
			$this->createDefinition('KdybyTests\Aop\CustomTemplate'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('[!inject]Bar'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('[!inject]Bar'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('[!inject]Bar'))),
			$this->createDefinition('KdybyTests\Aop\CustomTemplate'),
		);

		return $data;
	}



	/**
	 * @dataProvider dataMatchMethod
	 */
	public function testMatchMethod($expected, Kdyby\Aop\Pointcut\Filter $rules, Kdyby\Aop\Pointcut\ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	public function dataMatchFilter()
	{
		$data = array();

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\FilterMatcher('KdybyTests\Aop\MyPointcutFilter'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\FilterMatcher('KdybyTests\Aop\MyPointcutFilter'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		return $data;
	}



	/**
	 * @dataProvider dataMatchFilter
	 */
	public function testMatchFilter($expected, Kdyby\Aop\Pointcut\Filter $rules, Kdyby\Aop\Pointcut\ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	public function dataMatchClassAnnotateWith()
	{
		$data = array();

		$reader = new AnnotationReader();

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\ClassAnnotateWithMatcher('KdybyTests\Aop\Test', $reader))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\ClassAnnotateWithMatcher('KdybyTests\Aop\Test', $reader))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		return $data;
	}



	/**
	 * @dataProvider dataMatchClassAnnotateWith
	 */
	public function testMatchClassAnnotateWith($expected, Kdyby\Aop\Pointcut\Filter $rules, Kdyby\Aop\Pointcut\ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	public function dataMatchMethodAnnotateWith()
	{
		$data = array();

		$reader = new AnnotationReader();

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\MethodAnnotateWithMatcher('KdybyTests\Aop\Test', $reader))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\MethodAnnotateWithMatcher('KdybyTests\Aop\Test', $reader))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		return $data;
	}



	/**
	 * @dataProvider dataMatchMethodAnnotateWith
	 */
	public function testMatchMethodAnnotateWith($expected, Kdyby\Aop\Pointcut\Filter $rules, Kdyby\Aop\Pointcut\ServiceDefinition $def)
	{
		Assert::same($expected, (bool) $def->match($rules));
	}



	/**
	 * @param string $class
	 * @return Pointcut\ServiceDefinition
	 */
	private function createDefinition($class)
	{
		$def = new Nette\DI\ServiceDefinition();
		$def->setClass($class);

		return new Pointcut\ServiceDefinition($def);
	}

}

\run(new PointcutRulesTest());
