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

	public function dataMatch()
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

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('KdybyTests\Aop\Cat'))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\WithinMatcher('KdybyTests\Aop\Cat'))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$reader = new AnnotationReader();

		$data[] = array(TRUE,
			new Pointcut\Rules(array(new Matcher\ClassAnnotateWithMatcher('KdybyTests\Aop\Test', $reader))),
			$this->createDefinition('KdybyTests\Aop\SmegHead'),
		);

		$data[] = array(FALSE,
			new Pointcut\Rules(array(new Matcher\ClassAnnotateWithMatcher('KdybyTests\Aop\Test', $reader))),
			$this->createDefinition('KdybyTests\Aop\Legie'),
		);

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
	 * @dataProvider dataMatch
	 */
	public function testMatch($expected, Kdyby\Aop\Pointcut\Rule $rules, Kdyby\Aop\Pointcut\ServiceDefinition $def)
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
