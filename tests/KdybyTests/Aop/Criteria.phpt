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
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['foo'] = TRUE;
		$builder->parameters['bar'] = FALSE;

		Assert::true(Criteria::create()->where('foo', Criteria::EQ, 'foo')->evaluate($builder));
		Assert::false(Criteria::create()->where('foo', Criteria::EQ, 'bar')->evaluate($builder));
		Assert::false(Criteria::create()->where('foo', Criteria::NEQ, 'foo')->evaluate($builder));
		Assert::true(Criteria::create()->where('foo', Criteria::NEQ, 'bar')->evaluate($builder));
	}



	public function testGreater()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['foo'] = 1;
		$builder->parameters['bar'] = 2;

		Assert::true(Criteria::create()->where('bar', Criteria::GT, 'foo')->evaluate($builder));
		Assert::false(Criteria::create()->where('foo', Criteria::GT, 'foo')->evaluate($builder));
		Assert::false(Criteria::create()->where('foo', Criteria::GT, 'bar')->evaluate($builder));
	}



	public function testGreaterOrEqual()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['foo'] = 1;
		$builder->parameters['bar'] = 2;

		Assert::true(Criteria::create()->where('bar', Criteria::GTE, 'foo')->evaluate($builder));
		Assert::true(Criteria::create()->where('foo', Criteria::GTE, 'foo')->evaluate($builder));
		Assert::false(Criteria::create()->where('foo', Criteria::GTE, 'bar')->evaluate($builder));
	}



	public function testLower()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['foo'] = 2;
		$builder->parameters['bar'] = 1;

		Assert::true(Criteria::create()->where('bar', Criteria::LT, 'foo')->evaluate($builder));
		Assert::false(Criteria::create()->where('foo', Criteria::LT, 'foo')->evaluate($builder));
		Assert::false(Criteria::create()->where('foo', Criteria::LT, 'bar')->evaluate($builder));
	}



	public function testLowerOrEqual()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['foo'] = 2;
		$builder->parameters['bar'] = 1;

		Assert::true(Criteria::create()->where('bar', Criteria::LTE, 'foo')->evaluate($builder));
		Assert::true(Criteria::create()->where('foo', Criteria::LTE, 'foo')->evaluate($builder));
		Assert::false(Criteria::create()->where('foo', Criteria::LTE, 'bar')->evaluate($builder));
	}



	public function testIs()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['foo'] = new \stdClass;
		$builder->parameters['bar'] = new \stdClass;
		$builder->parameters['baz'] = $builder->parameters['foo'];

		Assert::true(Criteria::create()->where('foo', Criteria::IS, 'foo')->evaluate($builder));
		Assert::false(Criteria::create()->where('foo', Criteria::IS, 'bar')->evaluate($builder));
		Assert::true(Criteria::create()->where('bar', Criteria::IS, 'bar')->evaluate($builder));
		Assert::false(Criteria::create()->where('bar', Criteria::IS, 'baz')->evaluate($builder));
		Assert::true(Criteria::create()->where('baz', Criteria::IS, 'baz')->evaluate($builder));
		Assert::true(Criteria::create()->where('baz', Criteria::IS, 'foo')->evaluate($builder));
	}



	public function testIn()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['dave'] = new \stdClass;
		$builder->parameters['cat'] = new \stdClass;
		$builder->parameters['lister'] = new ArrayCollection(array($builder->parameters['dave']));
		$builder->parameters['kryten'] = $sos = new \SplObjectStorage();
		$sos->attach($builder->parameters['dave']);

		Assert::true(Criteria::create()->where('dave', Criteria::IN, 'lister')->evaluate($builder));
		Assert::false(Criteria::create()->where('dave', Criteria::NIN, 'lister')->evaluate($builder));
		Assert::true(Criteria::create()->where('dave', Criteria::IN, 'kryten')->evaluate($builder));
		Assert::false(Criteria::create()->where('dave', Criteria::NIN, 'kryten')->evaluate($builder));
		Assert::false(Criteria::create()->where('cat', Criteria::IN, 'lister')->evaluate($builder));
		Assert::true(Criteria::create()->where('cat', Criteria::NIN, 'lister')->evaluate($builder));
		Assert::false(Criteria::create()->where('cat', Criteria::IN, 'kryten')->evaluate($builder));
		Assert::true(Criteria::create()->where('cat', Criteria::NIN, 'kryten')->evaluate($builder));

		Assert::throws(function () use ($builder) {
			Criteria::create()->where('dave', Criteria::IN, 'dave')->evaluate($builder);
		}, 'Kdyby\Aop\InvalidArgumentException', 'Right value is expected to be array or instance of Traversable');

		Assert::throws(function () use ($builder) {
			Criteria::create()->where('dave', Criteria::NIN, 'dave')->evaluate($builder);
		}, 'Kdyby\Aop\InvalidArgumentException', 'Right value is expected to be array or instance of Traversable');
	}



	public function testContains()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['dave'] = new \stdClass;
		$builder->parameters['cat'] = new \stdClass;
		$builder->parameters['lister'] = new ArrayCollection(array($builder->parameters['dave']));
		$builder->parameters['kryten'] = $sos = new \SplObjectStorage();
		$sos->attach($builder->parameters['dave']);

		Assert::true(Criteria::create()->where('lister', Criteria::CONTAINS, 'dave')->evaluate($builder));
		Assert::true(Criteria::create()->where('kryten', Criteria::CONTAINS, 'dave')->evaluate($builder));
		Assert::false(Criteria::create()->where('lister', Criteria::CONTAINS, 'cat')->evaluate($builder));
		Assert::false(Criteria::create()->where('kryten', Criteria::CONTAINS, 'cat')->evaluate($builder));

		Assert::throws(function () use ($builder) {
			Criteria::create()->where('dave', Criteria::CONTAINS, 'dave')->evaluate($builder);
		}, 'Kdyby\Aop\InvalidArgumentException', 'Right value is expected to be array or instance of Traversable');
	}



	public function testMatches()
	{
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters['dave'] = array('a', 'b', 'c');
		$builder->parameters['cat'] = array('c', 'd', 'e');
		$builder->parameters['lister'] = array('e', 'f', 'g');
		$builder->parameters['misc'] = 'h';

		Assert::true(Criteria::create()->where('dave', Criteria::MATCHES, 'cat')->evaluate($builder));
		Assert::true(Criteria::create()->where('cat', Criteria::MATCHES, 'lister')->evaluate($builder));
		Assert::false(Criteria::create()->where('lister', Criteria::MATCHES, 'dave')->evaluate($builder));

		Assert::throws(function () use ($builder) {
			Criteria::create()->where('dave', Criteria::MATCHES, 'misc')->evaluate($builder);
		}, 'Kdyby\Aop\InvalidArgumentException', 'Right value is expected to be array or Traversable');

		Assert::throws(function () use ($builder) {
			Criteria::create()->where('misc', Criteria::MATCHES, 'dave')->evaluate($builder);
		}, 'Kdyby\Aop\InvalidArgumentException', 'Left value is expected to be array or Traversable');
	}

}

\run(new CriteriaTest());
