<?php

/**
 * Test: Kdyby\Aop\AspectAnalyzer.
 *
 * @testCase KdybyTests\Aop\AspectAnalyzerTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Aop
 */

namespace KdybyTests\Aop;

use Doctrine\Common\Annotations\AnnotationReader;
use Kdyby;
use Kdyby\Aop\Pointcut;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/files/aspect-examples.php';
require_once __DIR__ . '/../../../src/Kdyby/Aop/annotations.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AspectAnalyzerTest extends Tester\TestCase
{

	/***
	 * @return array
	 */
	public function dataAnalyze()
	{
		$data = array();

		$data[] = array(
			array(
				'log' => new Pointcut\Rules(array(
					new Pointcut\Matcher\WithinMatcher('Nette\Application\Application'),
					new Pointcut\Matcher\MethodMatcher('processRequest'),
				)),
			),
			$this->createDefinition('KdybyTests\Aop\LoggingAspect'),
		);

		$data[] = array(
			array(
				'protect' => new Pointcut\Rules(array(
					new Pointcut\Matcher\WithinMatcher('Nette\Application\IPresenter'),
					new Pointcut\Matcher\MethodMatcher('[render|action|handle]*'),
				)),
			),
			$this->createDefinition('KdybyTests\Aop\AclAspect'),
		);

		return $data;
	}



	/**
	 * @dataProvider dataAnalyze
	 */
	public function testAnalyze(array $pointcuts, Kdyby\Aop\Pointcut\ServiceDefinition $service)
	{
		$builder = new Nette\DI\ContainerBuilder();
		$annotationReader = new AnnotationReader();
		$matcherFactory = new Pointcut\MatcherFactory($builder, $annotationReader);
		$analyzer = new Pointcut\AspectAnalyzer(new Pointcut\Parser($matcherFactory), $annotationReader);

		Assert::equal($pointcuts, $analyzer->analyze($service));
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

\run(new AspectAnalyzerTest());
