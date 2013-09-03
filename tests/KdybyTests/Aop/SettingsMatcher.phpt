<?php

/**
 * Test: Kdyby\Aop\SettingsMatcher.
 *
 * @testCase KdybyTests\Aop\SettingsMatcherTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Aop
 */

namespace KdybyTests\Aop;

use Kdyby;
use Kdyby\Aop\Pointcut\Matcher\Criteria;
use Kdyby\Aop\Pointcut\Matcher\SettingMatcher;
use Nette;
use Nette\PhpGenerator as Code;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class SettingsMatcherTest extends Tester\TestCase
{

	public function testMatches()
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
		return Nette\Reflection\ClassType::from('Kdyby\Aop\Pointcut\Method')->newInstanceWithoutConstructor();
	}

}

\run(new SettingsMatcherTest());
