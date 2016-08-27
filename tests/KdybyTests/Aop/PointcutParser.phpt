<?php

/**
 * Test: Kdyby\Aop\Pointcut\Parser.
 *
 * @testCase KdybyTests\Aop\PointcutParserTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Aop
 */

namespace KdybyTests\Aop;

use Kdyby;
use Kdyby\Aop\Pointcut;
use Kdyby\Aop\Pointcut\Matcher\Criteria;
use Nette;
use Nette\PhpGenerator\PhpLiteral;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/files/pointcut-examples.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PointcutParserTest extends Tester\TestCase
{

	/**
	 * @var Pointcut\MatcherFactory
	 */
	private $matcherFactory;



	/**
	 * @return Pointcut\MatcherFactory
	 */
	public function getMatcherFactory()
	{
		if ($this->matcherFactory === NULL) {
			$this->matcherFactory = new Pointcut\MatcherFactory(new Nette\DI\ContainerBuilder());
		}

		return $this->matcherFactory;
	}



	protected function tearDown()
	{
		$this->matcherFactory = NULL;
	}



	public function dataParse()
	{
		$mf = $this->getMatcherFactory();

		$data = [];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
				$mf->getMatcher('method', 'deletePost'),
			]),
			'method(KdybyTests\Aop\CommonClass->deletePost())',
		];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
				$mf->getMatcher('method', 'public methodName'),
			]),
			'method(public KdybyTests\Aop\CommonClass->methodName())',
		];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
				$mf->getMatcher('method', 'protected methodName'),
			]),
			'method(protected KdybyTests\Aop\CommonClass->methodName())',
		];

		$data[] = [
			$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
			'method(KdybyTests\Aop\CommonClass->*())',
		];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
				$mf->getMatcher('method', 'public *'),
			]),
			'method(public KdybyTests\Aop\CommonClass->*())',
		];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'Example\MyPackage*'),
				$mf->getMatcher('method', 'delete*'),
			]),
			'method(Example\MyPackage*->delete*())',
		];

		$data[] = [
			$mf->getMatcher('method', 'delete*'),
			'method(*->delete*())',
		];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
				$mf->getMatcher('method', '[!inject]*'),
			]),
			'method(KdybyTests\Aop\CommonClass->[!inject]*())',
		];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'KdybyTests\Aop\PackageClass'),
				$mf->getMatcher('method', 'update'),
				$mf->getMatcher('arguments', Criteria::create()
					->where('title', Criteria::EQ, new PhpLiteral('"Kdyby"'))
					->where('override', Criteria::EQ, new PhpLiteral('TRUE'))
				),
			]),
			'method(KdybyTests\Aop\PackageClass->update(title == "Kdyby", override == TRUE))',
		];

		$data[] = [
			$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
			'class(\KdybyTests\Aop\CommonClass)',
		];

		$data[] = [
			$mf->getMatcher('class', 'Example\MyPackage\Service\*'),
			'class(Example\MyPackage\Service\*)',
		];

		$data[] = [
			$mf->getMatcher('within', 'KdybyTests\Aop\LoggerInterface'),
			'within(KdybyTests\Aop\LoggerInterface)',
		];

		$data[] = [
			$mf->getMatcher('classAnnotatedWith', 'Doctrine\ORM\Mapping\Entity'),
			'classAnnotatedWith(Doctrine\ORM\Mapping\Entity)',
		];

		$data[] = [
			$mf->getMatcher('methodAnnotatedWith', 'Acme\Demo\Annotations\Special'),
			'methodAnnotatedWith(Acme\Demo\Annotations\Special)',
		];

		$data[] = [
			$mf->getMatcher('setting', Criteria::create()->where('my.configuration.option', Criteria::EQ, new PhpLiteral('TRUE'))),
			'setting(my.configuration.option)',
		];

		$data[] = [
			$mf->getMatcher('setting', Criteria::create()->where('my.configuration.option', Criteria::EQ, new PhpLiteral("'AOP is cool'"))),
			"setting(my.configuration.option == 'AOP is cool')",
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.name', Criteria::EQ, new PhpLiteral('"Andi"'))),
			'evaluate(current.securityContext.party.name == "Andi")',
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('this.someObject.someProperty', Criteria::EQ, 'current.securityContext.party.name')),
			'evaluate(this.someObject.someProperty == current.securityContext.party.name)',
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('this.someProperty', Criteria::IN, [
				new PhpLiteral('TRUE'),
				new PhpLiteral('"someString"'),
				'current.securityContext.party.address'
			])),
			'evaluate(this.someProperty in (TRUE, "someString", current.securityContext.party.address))',
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.accounts', Criteria::CONTAINS, 'this.myAccount')),
			'evaluate(current.securityContext.party.accounts contains this.myAccount)',
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.accounts', Criteria::MATCHES, [
				new PhpLiteral("'Administrator'"),
				new PhpLiteral("'Customer'"),
				new PhpLiteral("'User'"),
			])),
			"evaluate(current.securityContext.party.accounts matches ('Administrator', 'Customer', 'User'))",
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.accounts', Criteria::MATCHES, 'this.accounts')),
			'evaluate(current.securityContext.party.accounts matches this.accounts)',
		];

		$data[] = [
			$mf->getMatcher('evaluate', Criteria::create()->where('%foo.dave%', Criteria::EQ, new PhpLiteral('TRUE'))),
			'evaluate(%foo.dave%)',
		];

		$data[] = [
			$mf->getMatcher('filter', 'KdybyTests\Aop\MyPointcutFilter'),
			'filter(KdybyTests\Aop\MyPointcutFilter)', # implements \Kdyby\Aop\Pointcut\Rule
		];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'Example\TestPackage\PointcutTestingTargetClass*'),
				new Pointcut\Matcher\Inverse($mf->getMatcher('class', 'KdybyTests\Aop\PackageClass')),
			]),
			'method(Example\TestPackage\PointcutTestingTargetClass*->*()) && !method(KdybyTests\Aop\PackageClass->*())',
		];

		$data[] = [
			new Pointcut\Rules([
				new Pointcut\Rules([
					$mf->getMatcher('class', 'KdybyTests\Aop\PointcutTestingAspect'),
					$mf->getMatcher('method', 'pointcutTestingTargetClasses'),
				]),
				new Pointcut\Rules([
					$mf->getMatcher('class', 'KdybyTests\Aop\PointcutTestingAspect'),
					$mf->getMatcher('method', 'otherPointcutTestingTargetClass'),
				]),
			], Pointcut\Rules::OP_OR),
			'KdybyTests\Aop\PointcutTestingAspect->pointcutTestingTargetClasses || KdybyTests\Aop\PointcutTestingAspect->otherPointcutTestingTargetClass',
		];

		$data[] = [
			new Pointcut\Rules([
				new Pointcut\Rules([
					new Pointcut\Rules([
						$mf->getMatcher('class', 'KdybyTests\Aop\PointcutTestingAspect'),
						$mf->getMatcher('method', 'pointcutTestingTargetClasses'),
					]),
					$mf->getMatcher('within', 'KdybyTests\Aop\LoggerInterface'),
				]),
				new Pointcut\Rules([
					$mf->getMatcher('class', 'KdybyTests\Aop\PointcutTestingAspect'),
					$mf->getMatcher('method', 'otherPointcutTestingTargetClass'),
				]),
			], Pointcut\Rules::OP_OR),
			'(KdybyTests\Aop\PointcutTestingAspect->pointcutTestingTargetClasses && within(KdybyTests\Aop\LoggerInterface))' . # intentionally no space after )
				'|| KdybyTests\Aop\PointcutTestingAspect->otherPointcutTestingTargetClass',
		];

		$data[] = [
			new Pointcut\Rules([
				$mf->getMatcher('class', 'Example\TestPackage\Basic*'),
				$mf->getMatcher('within', 'Kdyby\Service*'),
			], Pointcut\Rules::OP_OR),
			'method(Example\TestPackage\Basic*->*()) || within(Kdyby\Service*)',
		];

		$data[] = [
			new Pointcut\Rules([
				new Pointcut\Rules([
					$mf->getMatcher('class', 'KdybyTests\Aop\FeedAggregator'),
					$mf->getMatcher('method', 'public [import|update]*'),
				]),
				new Pointcut\Rules([
					$mf->getMatcher('class', 'KdybyTests\Aop\PointcutTestingAspect'),
					$mf->getMatcher('method', 'someOtherPointcut'),
				]),
			], Pointcut\Rules::OP_OR),
			'method(public KdybyTests\Aop\FeedAggregator->[import|update]*()) || KdybyTests\Aop\PointcutTestingAspect->someOtherPointcut',
		];

		return $data;
	}



	/**
	 * @dataProvider dataParse
	 */
	public function testParse($expected, $input)
	{
		$parser = new Kdyby\Aop\Pointcut\Parser($this->getMatcherFactory());
		Assert::equal($expected, $parser->parse($input));
	}

}

\run(new PointcutParserTest());
