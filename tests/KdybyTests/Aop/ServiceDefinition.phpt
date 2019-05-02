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
 * @author Karel Hak <karel.hak@fregis.cz>
 */
class ServiceDefinitionTest extends Tester\TestCase
{

	public function testInheritedConstructor()
	{
		$definition = $this->createDefinition(InheritedClass::class);
		Assert::equal($definition->getOpenMethods(), ['__construct' => new Pointcut\Method(new \ReflectionMethod(InheritedClass::class, '__construct'), $definition)]);
	}


	private function createDefinition(string $type): Pointcut\ServiceDefinition
	{
		$def = new Nette\DI\ServiceDefinition();
		$def->setType($type);

		return new Pointcut\ServiceDefinition($def, 'abc');
	}

}

(new ServiceDefinitionTest())->run();
