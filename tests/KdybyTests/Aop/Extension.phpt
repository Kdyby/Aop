<?php

/**
 * Test: Kdyby\Aop\Extension.
 *
 * @testCase KdybyTests\Aop\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Aop
 */

namespace KdybyTests\Aop;

use Kdyby;
use Kdyby\Aop\JoinPoint\AfterMethod;
use Kdyby\Aop\JoinPoint\AfterReturning;
use Kdyby\Aop\JoinPoint\AfterThrowing;
use Kdyby\Aop\JoinPoint\AroundMethod;
use Kdyby\Aop\JoinPoint\BeforeMethod;
use Kdyby\Aop\JoinPoint\MethodInvocation;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/files/aspect-examples.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends Tester\TestCase
{

	/**
	 * @param string $configFile
	 * @return \SystemContainer|Nette\DI\Container
	 */
	public function createContainer($configFile)
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addConfig(__DIR__ . '/../nette-reset.' . (!isset($config->defaultExtensions['nette']) ? 'v23' : 'v22') . '.neon');
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');

		Kdyby\Annotations\DI\AnnotationsExtension::register($config);
		Kdyby\Aop\DI\AspectsExtension::register($config);
		Kdyby\Aop\DI\AopExtension::register($config);

		return $config->createContainer();
	}



	public function testAspectConfiguration()
	{
		$dic = $this->createContainer('aspect-configs');
		foreach ($services = array_keys($dic->findByTag(Kdyby\Aop\DI\AspectsExtension::ASPECT_TAG)) as $serviceId) {
			$service = $dic->getService($serviceId);
			Assert::true($service instanceof AspectWithArguments);
			Assert::same([$dic->getByType('Nette\Http\Request')], $service->args);
		}

		Assert::same(4, count($services));
	}



	public function testIfAspectAppliedOnCreatedObject()
	{
		$dic = $this->createContainer('factory');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		$createdObject = $dic->getByType('KdybyTests\Aop\ICommonServiceFactory')->create();

		Assert::notEqual('KdybyTests\Aop\CommonService', get_class($service));
		Assert::notEqual('KdybyTests\Aop\CommonService', get_class($createdObject));
		Assert::isEqual(get_class($service), get_class($createdObject));
	}



	public function testFunctionalBefore()
	{
		$dic = $this->createContainer('before');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, 'KdybyTests\Aop\BeforeAspect', 0, new BeforeMethod($service, 'magic', [2]));
		/** @var BeforeAspect $advice */

		$service->return = 3;
		Assert::same(6, $service->magic(2));
		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\BeforeAspect', 1, new BeforeMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		Assert::same(9, $service->magic(2));
		Assert::same([3], $service->calls[2]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\BeforeAspect', 2, new BeforeMethod($service, 'magic', [3]));
	}



	public function testFunctionalConstructor()
	{
		$dic = $this->createContainer('constructor');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		self::assertAspectInvocation($service, 'KdybyTests\Aop\ConstructorBeforeAspect', 0, new BeforeMethod($service, '__construct', [$dic]));
	}



	public function testFunctionalBefore_conditional()
	{
		$dic = $this->createContainer('before.conditional');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		Assert::same(0, $service->magic(0));
		Assert::same(2, $service->magic(1));
		Assert::same(4, $service->magic(2));

		Assert::same([0], $service->calls[0]);
		Assert::same([1], $service->calls[1]);
		Assert::same([2], $service->calls[2]);

		self::assertAspectInvocation($service, 'KdybyTests\Aop\ConditionalBeforeAspect', 0, new BeforeMethod($service, 'magic', [1]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\ConditionalBeforeAspect', 1, NULL);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\ConditionalBeforeAspect', 2, NULL);
	}



	public function testFunctionalAround()
	{
		$dic = $this->createContainer('around');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundAspect', 0, new AroundMethod($service, 'magic', [2]));
		/** @var AroundAspect $advice */

		$service->return = 3;
		Assert::same(6, $service->magic(2));
		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundAspect', 1, new AroundMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		Assert::same(9, $service->magic(2));
		Assert::same([3], $service->calls[2]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundAspect', 2, new AroundMethod($service, 'magic', [3]));
	}



	public function testFunctionalAround_conditional()
	{
		$dic = $this->createContainer('around.conditional');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		Assert::same(0, $service->magic(0));
		Assert::same(2, $service->magic(1));
		Assert::same(4, $service->magic(2));

		Assert::same([0], $service->calls[0]);
		Assert::same([1], $service->calls[1]);
		Assert::same([2], $service->calls[2]);

		self::assertAspectInvocation($service, 'KdybyTests\Aop\ConditionalAroundAspect', 0, new AroundMethod($service, 'magic', [1]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\ConditionalAroundAspect', 1, NULL);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\ConditionalAroundAspect', 2, NULL);
	}



	public function testFunctionalAround_blocking()
	{
		$dic = $this->createContainer('around.blocking');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		$advice = self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundBlockingAspect', 0, new AroundMethod($service, 'magic', [2]));
		/** @var AroundBlockingAspect $advice */

		$service->return = 3;
		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundBlockingAspect', 1, new AroundMethod($service, 'magic', [2]));

		$service->throw = TRUE;
		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundBlockingAspect', 2, new AroundMethod($service, 'magic', [2]));

		$advice->modifyArgs = [3];
		Assert::null($service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundBlockingAspect', 3, new AroundMethod($service, 'magic', [3]));

		$advice->modifyReturn = 9;
		Assert::same(9, $service->magic(2));
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundBlockingAspect', 4, new AroundMethod($service, 'magic', [3]));

		$advice->modifyThrow = TRUE;
		Assert::throws(function () use ($service) {
			$service->magic(2);
		}, 'RuntimeException', "Everybody is dead Dave.");
		Assert::true(empty($service->calls));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundBlockingAspect', 5, new AroundMethod($service, 'magic', [3]));
	}



	public function testFunctionalAfterReturning()
	{
		$dic = $this->createContainer('afterReturning');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		$advice = self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterReturningAspect', 0, new AfterReturning($service, 'magic', [2], 4));
		/** @var AfterReturningAspect $advice */

		$service->return = 3;
		Assert::same(6, $service->magic(2));
		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterReturningAspect', 1, new AfterReturning($service, 'magic', [2], 6));

		$advice->modifyReturn = 9;
		Assert::same(9, $service->magic(2));
		Assert::same([2], $service->calls[2]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterReturningAspect', 2, new AfterReturning($service, 'magic', [2], 9));
	}



	public function testFunctionalAfterReturning_conditional()
	{
		$dic = $this->createContainer('afterReturning.conditional');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		Assert::same(0, $service->magic(0));

		$service->return = 3;
		Assert::same(3, $service->magic(1));

		$service->return = 2;
		Assert::same(4, $service->magic(2));

		Assert::same([0], $service->calls[0]);
		Assert::same([1], $service->calls[1]);
		Assert::same([2], $service->calls[2]);

		self::assertAspectInvocation($service, 'KdybyTests\Aop\ConditionalAfterReturningAspect', 0, new AfterReturning($service, 'magic', [0], 0));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\ConditionalAfterReturningAspect', 1, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\ConditionalAfterReturningAspect', 2, NULL);
	}



	public function testFunctionalAfterThrowing()
	{
		$dic = $this->createContainer('afterThrowing');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		$service->throw = TRUE;
		Assert::throws(function () use ($service) {
			$service->magic(2);
		}, 'RuntimeException', "Something's fucky");

		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterThrowingAspect', 0, new AfterThrowing($service, 'magic', [2], new \RuntimeException("Something's fucky")));
	}



	public function testFunctionalAfter()
	{
		$dic = $this->createContainer('after');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterAspect', 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = TRUE;
		Assert::throws(function () use ($service) {
			$service->magic(2);
		}, 'RuntimeException', "Something's fucky");

		Assert::same([2], $service->calls[1]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterAspect', 1, new AfterMethod($service, 'magic', [2], NULL, new \RuntimeException("Something's fucky")));
	}



	public function testFunctionalAll()
	{
		$dic = $this->createContainer('all');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\BeforeAspect', 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundAspect', 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterReturningAspect', 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterAspect', 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = TRUE;
		Assert::throws(function () use ($service) {
			$service->magic(3);
		}, 'RuntimeException', "Something's fucky");
		Assert::same([3], $service->calls[1]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\BeforeAspect', 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundAspect', 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterThrowingAspect', 0, new AfterThrowing($service, 'magic', [3], new \RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterAspect', 1, new AfterMethod($service, 'magic', [3], NULL, new \RuntimeException("Something's fucky")));
	}



	public function testFunctionalAll_doubled()
	{
		$dic = $this->createContainer('all.doubled');
		$service = $dic->getByType('KdybyTests\Aop\CommonService');
		/** @var CommonService $service */

		Assert::same(4, $service->magic(2));
		Assert::same([2], $service->calls[0]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\BeforeAspect', 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\SecondBeforeAspect', 0, new BeforeMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundAspect', 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\SecondAroundAspect', 0, new AroundMethod($service, 'magic', [2]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterReturningAspect', 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\SecondAfterReturningAspect', 0, new AfterReturning($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterAspect', 0, new AfterMethod($service, 'magic', [2], 4));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\SecondAfterAspect', 0, new AfterMethod($service, 'magic', [2], 4));

		$service->throw = TRUE;
		Assert::throws(function () use ($service) {
			$service->magic(3);
		}, 'RuntimeException', "Something's fucky");
		Assert::same([3], $service->calls[1]);
		self::assertAspectInvocation($service, 'KdybyTests\Aop\BeforeAspect', 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\SecondBeforeAspect', 1, new BeforeMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AroundAspect', 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\SecondAroundAspect', 1, new AroundMethod($service, 'magic', [3]));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterThrowingAspect', 0, new AfterThrowing($service, 'magic', [3], new \RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\SecondAfterThrowingAspect', 0, new AfterThrowing($service, 'magic', [3], new \RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\AfterAspect', 1, new AfterMethod($service, 'magic', [3], NULL, new \RuntimeException("Something's fucky")));
		self::assertAspectInvocation($service, 'KdybyTests\Aop\SecondAfterAspect', 1, new AfterMethod($service, 'magic', [3], NULL, new \RuntimeException("Something's fucky")));
	}



	/**
	 * @param object $service
	 * @param string $adviceClass
	 * @param int $adviceCallIndex
	 * @param MethodInvocation $joinPoint
	 * @return object
	 */
	private static function assertAspectInvocation($service, $adviceClass, $adviceCallIndex, MethodInvocation $joinPoint = NULL)
	{
		$advices = array_filter(self::getAspects($service), function ($advice) use ($adviceClass) {
			return get_class($advice) === $adviceClass;
		});
		Assert::true(!empty($advices));
		$advice = reset($advices);
		Assert::true($advice instanceof $adviceClass);

		if ($joinPoint === NULL) {
			Assert::true(empty($advice->calls[$adviceCallIndex]));

			return $advice;
		}

		Assert::true(!empty($advice->calls[$adviceCallIndex]));
		$call = $advice->calls[$adviceCallIndex];
		/** @var MethodInvocation $call */

		$joinPointClass = get_class($joinPoint);
		Assert::true($call instanceof $joinPointClass);
		Assert::equal($joinPoint->getArguments(), $call->getArguments());
		Assert::same($joinPoint->getTargetObject(), $call->getTargetObject());
		Assert::same($joinPoint->getTargetReflection()->getName(), $call->getTargetReflection()->getName());

		if ($joinPoint instanceof Kdyby\Aop\JoinPoint\ResultAware) {
			/** @var AfterReturning $call */
			Assert::same($joinPoint->getResult(), $call->getResult());
		}

		if ($joinPoint instanceof Kdyby\Aop\JoinPoint\ExceptionAware) {
			/** @var AfterThrowing $call */
			Assert::equal($joinPoint->getException() ? get_class($joinPoint->getException()) : NULL, $call->getException() ? get_class($call->getException()) : NULL);
			Assert::equal($joinPoint->getException() ? $joinPoint->getException()->getMessage() : '', $call->getException() ? $call->getException()->getMessage() : '');
		}

		return $advice;
	}



	/**
	 * @param string $service
	 * @return array
	 */
	private static function getAspects($service)
	{
		try {
			$propRefl = Nette\Reflection\ClassType::from($service)
				->getProperty('_kdyby_aopAdvices'); // internal property

			$propRefl->setAccessible(TRUE);
			return $propRefl->getValue($service);

		} catch (\ReflectionException $e) {
			return [];
		}
	}

}

\run(new ExtensionTest());
