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

		$data = array();

		$data[] = array(
			new Pointcut\Rules(array(
				$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
				$mf->getMatcher('method', 'deletePost'),
			)),
			'method(KdybyTests\Aop\CommonClass->deletePost())',
		);

		$data[] = array(
			new Pointcut\Rules(array(
				$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
				$mf->getMatcher('method', 'public methodName'),
			)),
			'method(public KdybyTests\Aop\CommonClass->methodName())',
		);

		$data[] = array(
			new Pointcut\Rules(array(
				$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
				$mf->getMatcher('method', 'protected methodName'),
			)),
			'method(protected KdybyTests\Aop\CommonClass->methodName())',
		);

		$data[] = array(
			$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
			'method(KdybyTests\Aop\CommonClass->*())',
		);

		$data[] = array(
			new Pointcut\Rules(array(
				$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
				$mf->getMatcher('method', 'public *'),
			)),
			'method(public KdybyTests\Aop\CommonClass->*())',
		);

		$data[] = array(
			new Pointcut\Rules(array(
				$mf->getMatcher('class', 'Example\MyPackage*'),
				$mf->getMatcher('method', 'delete*'),
			)),
			'method(Example\MyPackage*->delete*())',
		);

		$data[] = array(
			$mf->getMatcher('method', 'delete*'),
			'method(*->delete*())',
		);

		$data[] = array(
			new Pointcut\Rules(array(
				$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
				$mf->getMatcher('method', '[!inject]*'),
			)),
			'method(KdybyTests\Aop\CommonClass->[!inject]*())',
		);

		$data[] = array(
			new Pointcut\Rules(array(
				$mf->getMatcher('class', 'KdybyTests\Aop\PackageClass'),
				$mf->getMatcher('method', 'update'),
				$mf->getMatcher('arguments', Criteria::create()
					->where('title', Criteria::EQ, new PhpLiteral('"Kdyby"'))
					->where('override', Criteria::EQ, new PhpLiteral('TRUE'))
				),
			)),
			'method(KdybyTests\Aop\PackageClass->update(title == "Kdyby", override == TRUE))',
		);

		$data[] = array(
			$mf->getMatcher('class', 'KdybyTests\Aop\CommonClass'),
			'class(\KdybyTests\Aop\CommonClass)',
		);

		$data[] = array(
			$mf->getMatcher('class', 'Example\MyPackage\Service\*'),
			'class(Example\MyPackage\Service\*)',
		);

		$data[] = array(
			$mf->getMatcher('within', 'KdybyTests\Aop\LoggerInterface'),
			'within(KdybyTests\Aop\LoggerInterface)',
		);

		$data[] = array(
			$mf->getMatcher('classAnnotatedWith', 'Doctrine\ORM\Mapping\Entity'),
			'classAnnotatedWith(Doctrine\ORM\Mapping\Entity)',
		);

		$data[] = array(
			$mf->getMatcher('methodAnnotatedWith', 'Acme\Demo\Annotations\Special'),
			'methodAnnotatedWith(Acme\Demo\Annotations\Special)',
		);

		$data[] = array(
			$mf->getMatcher('setting', Criteria::create()->where('my.configuration.option', Criteria::EQ, new PhpLiteral('TRUE'))),
			'setting(my.configuration.option)',
		);

		$data[] = array(
			$mf->getMatcher('setting', Criteria::create()->where('my.configuration.option', Criteria::EQ, new PhpLiteral("'AOP is cool'"))),
			"setting(my.configuration.option == 'AOP is cool')",
		);

		$data[] = array(
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.name', Criteria::EQ, new PhpLiteral('"Andi"'))),
			'evaluate(current.securityContext.party.name == "Andi")',
		);

		$data[] = array(
			$mf->getMatcher('evaluate', Criteria::create()->where('this.someObject.someProperty', Criteria::EQ, 'current.securityContext.party.name')),
			'evaluate(this.someObject.someProperty == current.securityContext.party.name)',
		);

		$data[] = array(
			$mf->getMatcher('evaluate', Criteria::create()->where('this.someProperty', Criteria::IN, array(
				new PhpLiteral('TRUE'),
				new PhpLiteral('"someString"'),
				'current.securityContext.party.address'
			))),
			'evaluate(this.someProperty in (TRUE, "someString", current.securityContext.party.address))',
		);

		$data[] = array(
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.accounts', Criteria::CONTAINS, 'this.myAccount')),
			'evaluate(current.securityContext.party.accounts contains this.myAccount)',
		);

		$data[] = array(
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.accounts', Criteria::MATCHES, array(
				new PhpLiteral("'Administrator'"),
				new PhpLiteral("'Customer'"),
				new PhpLiteral("'User'"),
			))),
			"evaluate(current.securityContext.party.accounts matches ('Administrator', 'Customer', 'User'))",
		);

		$data[] = array(
			$mf->getMatcher('evaluate', Criteria::create()->where('current.securityContext.party.accounts', Criteria::MATCHES, 'this.accounts')),
			'evaluate(current.securityContext.party.accounts matches this.accounts)',
		);

		$data[] = array(
			$mf->getMatcher('evaluate', Criteria::create()->where('%foo.dave%', Criteria::EQ, new PhpLiteral('TRUE'))),
			'evaluate(%foo.dave%)',
		);

		$data[] = array(
			$mf->getMatcher('filter', 'KdybyTests\Aop\MyPointcutFilter'),
			'filter(KdybyTests\Aop\MyPointcutFilter)', # implements \Kdyby\Aop\Pointcut\Rule
		);

		$data[] = array(
			new Pointcut\Rules(array(
				$mf->getMatcher('class', 'Example\TestPackage\PointcutTestingTargetClass*'),
				new Pointcut\Matcher\Inverse($mf->getMatcher('class', 'KdybyTests\Aop\PackageClass')),
			)),
			'method(Example\TestPackage\PointcutTestingTargetClass*->*()) && !method(KdybyTests\Aop\PackageClass->*())',
		);

		$data[] = array(
			new Pointcut\Rules(array(
				new Pointcut\Rules(array(
					$mf->getMatcher('class', 'KdybyTests\Aop\PointcutTestingAspect'),
					$mf->getMatcher('method', 'pointcutTestingTargetClasses'),
				)),
				new Pointcut\Rules(array(
					$mf->getMatcher('class', 'KdybyTests\Aop\PointcutTestingAspect'),
					$mf->getMatcher('method', 'otherPointcutTestingTargetClass'),
				)),
			), Pointcut\Rules::OP_OR),
			'KdybyTests\Aop\PointcutTestingAspect->pointcutTestingTargetClasses || KdybyTests\Aop\PointcutTestingAspect->otherPointcutTestingTargetClass',
		);

		$data[] = array(
			new Pointcut\Rules(array(
				new Pointcut\Rules(array(
					new Pointcut\Rules(array(
						$mf->getMatcher('class', 'KdybyTests\Aop\PointcutTestingAspect'),
						$mf->getMatcher('method', 'pointcutTestingTargetClasses'),
					)),
					$mf->getMatcher('within', 'KdybyTests\Aop\LoggerInterface'),
				)),
				new Pointcut\Rules(array(
					$mf->getMatcher('class', 'KdybyTests\Aop\PointcutTestingAspect'),
					$mf->getMatcher('method', 'otherPointcutTestingTargetClass'),
				)),
			), Pointcut\Rules::OP_OR),
			'(KdybyTests\Aop\PointcutTestingAspect->pointcutTestingTargetClasses && within(KdybyTests\Aop\LoggerInterface))' . # intentionally no space after )
				'|| KdybyTests\Aop\PointcutTestingAspect->otherPointcutTestingTargetClass',
		);

		$data[] = array(
			new Pointcut\Rules(array(
				$mf->getMatcher('class', 'Example\TestPackage\Basic*'),
				$mf->getMatcher('within', 'Kdyby\Service*'),
			), Pointcut\Rules::OP_OR),
			'method(Example\TestPackage\Basic*->*()) || within(Kdyby\Service*)',
		);

		$data[] = array(
			new Pointcut\Rules(array(
				new Pointcut\Rules(array(
					$mf->getMatcher('class', 'KdybyTests\Aop\FeedAggregator'),
					$mf->getMatcher('method', 'public [import|update]*'),
				)),
				new Pointcut\Rules(array(
					$mf->getMatcher('class', 'KdybyTests\Aop\PointcutTestingAspect'),
					$mf->getMatcher('method', 'someOtherPointcut'),
				)),
			), Pointcut\Rules::OP_OR),
			'method(public KdybyTests\Aop\FeedAggregator->[import|update]*()) || KdybyTests\Aop\PointcutTestingAspect->someOtherPointcut',
		);

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
