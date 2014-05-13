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
		$definition = $this->createDefinition('KdybyTests\Aop\InheritedClass');
		Assert::equal($definition->getOpenMethods(), array('__construct' => new Pointcut\Method(\Nette\Reflection\Method::from('KdybyTests\Aop\InheritedClass', '__construct'), $definition)));
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

\run(new ServiceDefinitionTest());
