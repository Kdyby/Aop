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
use Kdyby\Aop\Pointcut\Matcher\Criteria;
use Kdyby\Aop\Pointcut\Matcher\SettingMatcher;
use Nette;
use Nette\PhpGenerator as Code;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/files/pointcut-examples.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PointcutRulesTest extends Tester\TestCase
{

	public function dataMatchWithin()
	{
		$data = array();

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('KdybyTests\Aop\SmegHead'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('KdybyTests\Aop\*'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('*'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('KdybyTests\Aop\SmegHead'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

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
			new Pointcut\Rules(array(new Matcher\MethodMatcher('[?inject]Bar'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('[?inject]Bar'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\MethodMatcher('[?inject]Bar'))),
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



	public function testMatchMethod_or()
	{
		$rules = new Pointcut\Rules(array(new Matcher\MethodMatcher('public [render|action|handle]*')));
		$def = $this->createDefinition('KdybyTests\Aop\MockPresenter');

		Assert::same(array(
			$def->openMethods['renderDefault'],
			$def->openMethods['actionDefault'],
			$def->openMethods['handleSort'],
		), $def->match($rules));
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



	public function testMatchesSetting()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['foo']['dave'] = TRUE;
		$builder->parameters['foo']['kryten'] = FALSE;
		$builder->parameters['friendship'] = 'Is magic';

		$args = new SettingMatcher(Criteria::create()->where('foo.dave', Criteria::EQ, new Code\PhpLiteral('TRUE')), $builder);
		Assert::true($args->matches($this->mockMethod()));

		$args = new SettingMatcher(Criteria::create()->where('foo.kryten', Criteria::EQ, new Code\PhpLiteral('FALSE')), $builder);
		Assert::true($args->matches($this->mockMethod()));

		$args = new SettingMatcher(Criteria::create()->where('friendship', Criteria::EQ, new Code\PhpLiteral("'Is magic'")), $builder);
		Assert::true($args->matches($this->mockMethod()));
	}



	/**
	 * @return Kdyby\Aop\Pointcut\Method
	 */
	private function mockMethod()
	{
		if (method_exists('Nette\Reflection\ClassType', 'newInstanceWithoutConstructor')) {
			return Nette\Reflection\ClassType::from('Kdyby\Aop\Pointcut\Method')->newInstanceWithoutConstructor();

		} else {
			return unserialize(sprintf('O:%d:"%s":0:{}', strlen('Kdyby\Aop\Pointcut\Method'), 'Kdyby\Aop\Pointcut\Method'));
		}
	}



	/**
	 * @param string $class
	 * @return Pointcut\ServiceDefinition
	 */
	private function createDefinition($class)
	{
		$def = new Nette\DI\ServiceDefinition();
		$def->setClass($class);

		return new Pointcut\ServiceDefinition($def, 'abc');
	}

}

\run(new PointcutRulesTest());
