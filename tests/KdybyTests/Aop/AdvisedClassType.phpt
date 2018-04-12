<?php

/**
 * Test: Kdyby\Aop\AdvisedClassTypeTest.
 *
 * @testCase KdybyTests\Aop\AdvisedClassTypeTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Aop
 */

namespace KdybyTests\Aop;


use Kdyby;
use Kdyby\Aop\Pointcut\Matcher\Criteria;
use Nette\PhpGenerator\ClassType;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



class AdvisedClassTypeTest extends Tester\TestCase
{

	public function testSetMethodInstance()
	{
		$testClass = ClassType::from(TestClass::class);

		$method = Kdyby\Aop\PhpGenerator\AdvisedClassType::setMethodInstance($testClass, $testClass->getMethod('first'));
		$string = $methodCode = $method->__toString();
		Assert::count(2, $method->getParameters());
	}
}

class TestClass
{
	public function first(int $param, string $second)
	{

	}
}
\run(new AdvisedClassTypeTest());
