<?php

/**
 * Test: Kdyby\Aop\Criteria.
 *
 * @testCase KdybyTests\Aop\CriteriaTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Aop
 */

namespace KdybyTests\Aop;

use Doctrine\Common\Collections\ArrayCollection;
use Kdyby;
use Kdyby\Aop\Pointcut\Matcher\Criteria;
use Nette;
use Nette\PhpGenerator as Code;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CriteriaTest extends Tester\TestCase
{

	public function testEqual()
	{
		Assert::true(Criteria::compare(TRUE, Criteria::EQ, TRUE));
		Assert::false(Criteria::compare(TRUE, Criteria::EQ, FALSE));
		Assert::false(Criteria::compare(TRUE, Criteria::NEQ, TRUE));
		Assert::true(Criteria::compare(TRUE, Criteria::NEQ, FALSE));
	}



	public function testGreater()
	{
		Assert::true(Criteria::compare(2, Criteria::GT, 1));
		Assert::false(Criteria::compare(1, Criteria::GT, 1));
		Assert::false(Criteria::compare(1, Criteria::GT, 2));
	}



	public function testGreaterOrEqual()
	{
		Assert::true(Criteria::compare(2, Criteria::GTE, 1));
		Assert::true(Criteria::compare(1, Criteria::GTE, 1));
		Assert::false(Criteria::compare(1, Criteria::GTE, 2));
	}



	public function testLower()
	{
		Assert::true(Criteria::compare(1, Criteria::LT, 2));
		Assert::false(Criteria::compare(2, Criteria::LT, 2));
		Assert::false(Criteria::compare(2, Criteria::LT, 1));
	}



	public function testLowerOrEqual()
	{
		Assert::true(Criteria::compare(1, Criteria::LTE, 2));
		Assert::true(Criteria::compare(2, Criteria::LTE, 2));
		Assert::false(Criteria::compare(2, Criteria::LTE, 1));
	}



	public function testIs()
	{
		$foo = new \stdClass;
		$bar = new \stdClass;

		Assert::true(Criteria::compare($foo, Criteria::IS, $foo));
		Assert::false(Criteria::compare($foo, Criteria::IS, $bar));
		Assert::true(Criteria::compare($bar, Criteria::IS, $bar));
		Assert::false(Criteria::compare($bar, Criteria::IS, $foo));
	}



	public function testIn()
	{
		$dave = new \stdClass;
		$lister = new ArrayCollection([$dave]);
		$kryten = new \SplObjectStorage();
		$kryten->attach($dave);
		$cat = new \stdClass;

		Assert::true(Criteria::compare($dave, Criteria::IN, $lister));
		Assert::false(Criteria::compare($dave, Criteria::NIN, $lister));
		Assert::true(Criteria::compare($dave, Criteria::IN, $kryten));
		Assert::false(Criteria::compare($dave, Criteria::NIN, $kryten));
		Assert::false(Criteria::compare($cat, Criteria::IN, $lister));
		Assert::true(Criteria::compare($cat, Criteria::NIN, $lister));
		Assert::false(Criteria::compare($cat, Criteria::IN, $kryten));
		Assert::true(Criteria::compare($cat, Criteria::NIN, $kryten));

		Assert::throws(function () use ($dave) {
			Criteria::compare($dave, Criteria::IN, $dave);
		}, 'Kdyby\Aop\InvalidArgumentException', 'Right value is expected to be array or instance of Traversable');

		Assert::throws(function () use ($dave) {
			Criteria::compare($dave, Criteria::NIN, $dave);
		}, 'Kdyby\Aop\InvalidArgumentException', 'Right value is expected to be array or instance of Traversable');
	}



	public function testContains()
	{
		$dave = new \stdClass;
		$lister = new ArrayCollection([$dave]);
		$kryten = new \SplObjectStorage();
		$kryten->attach($dave);
		$cat = new \stdClass;

		Assert::true(Criteria::compare($lister, Criteria::CONTAINS, $dave));
		Assert::true(Criteria::compare($kryten, Criteria::CONTAINS, $dave));
		Assert::false(Criteria::compare($lister, Criteria::CONTAINS, $cat));
		Assert::false(Criteria::compare($kryten, Criteria::CONTAINS, $cat));

		Assert::throws(function () use ($dave) {
			Criteria::compare($dave, Criteria::CONTAINS, $dave);
		}, 'Kdyby\Aop\InvalidArgumentException', 'Right value is expected to be array or instance of Traversable');
	}



	public function testMatches()
	{
		$dave = ['a', 'b', 'c'];
		$cat = ['c', 'd', 'e'];
		$lister = ['e', 'f', 'g'];

		Assert::true(Criteria::compare($dave, Criteria::MATCHES, $cat));
		Assert::true(Criteria::compare($cat, Criteria::MATCHES, $lister));
		Assert::false(Criteria::compare($lister, Criteria::MATCHES, $dave));

		Assert::throws(function () use ($dave) {
			Criteria::compare($dave, Criteria::MATCHES, 'h');
		}, 'Kdyby\Aop\InvalidArgumentException', 'Right value is expected to be array or Traversable');

		Assert::throws(function () use ($dave) {
			Criteria::compare('h', Criteria::MATCHES, $dave);
		}, 'Kdyby\Aop\InvalidArgumentException', 'Left value is expected to be array or Traversable');
	}



	public function testSerialize_propertyAccess()
	{
		$criteria = Criteria::create()->where('this.foo.bar', Criteria::EQ, new Code\PhpLiteral('TRUE'));
		Assert::same(
			"(Criteria::compare(PropertyAccess::createPropertyAccessor()->getValue(\$this, 'foo.bar'), '==', true))",
			(string) $criteria->serialize(new Nette\DI\ContainerBuilder())
		);
	}



	public function testSerialize_arguments()
	{
		$criteria = Criteria::create()->where('$arg', Criteria::EQ, new Code\PhpLiteral('TRUE'));
		Assert::same(
			"(Criteria::compare(\$arg, '==', true))",
			(string) $criteria->serialize(new Nette\DI\ContainerBuilder())
		);
	}



	public function testSerialize_parameter()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['foo']['bar'] = 'complicated value!';
		$criteria = Criteria::create()->where('%foo.bar%', Criteria::EQ, new Code\PhpLiteral('TRUE'));
		Assert::same(
			"(Criteria::compare('complicated value!', '==', true))",
			(string) $criteria->serialize($builder)
		);
	}



	public function testSerialize_service_byName()
	{
		$criteria = Criteria::create()->where('context.foo.bar', Criteria::EQ, new Code\PhpLiteral('TRUE'));
		Assert::same(
			"(Criteria::compare(PropertyAccess::createPropertyAccessor()->getValue(\$this->_kdyby_aopContainer->getService('foo'), 'bar'), '==', true))",
			(string) $criteria->serialize(new Nette\DI\ContainerBuilder())
		);
	}



	public function testSerialize_service_byType()
	{
		$criteria = Criteria::create()->where('context.stdClass.bar', Criteria::EQ, new Code\PhpLiteral('TRUE'));
		Assert::match(
			"(Criteria::compare(PropertyAccess::createPropertyAccessor()->getValue(\$this->_kdyby_aopContainer->getByType('stdClass'), 'bar'), '==', true))",
			(string) $criteria->serialize(new Nette\DI\ContainerBuilder())
		);

		$criteria = Criteria::create()->where('context.KdybyTests\Aop\CriteriaTest.bar', Criteria::EQ, new Code\PhpLiteral('TRUE'));
		Assert::match(
			"(Criteria::compare(PropertyAccess::createPropertyAccessor()->getValue(\$this->_kdyby_aopContainer->getByType('KdybyTests%a%Aop%a%CriteriaTest'), 'bar'), '==', true))",
			(string) $criteria->serialize(new Nette\DI\ContainerBuilder())
		);
	}

}

\run(new CriteriaTest());
