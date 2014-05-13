<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Aop;

use Doctrine\Common\Annotations\Annotation;
use Kdyby\Aop\Pointcut\Filter;
use Kdyby\Aop\Pointcut\Method;
use Nette;



interface Rimmer
{

}



interface Lister
{

}



interface Kryten
{

}



interface Cat
{

}



class Legie implements Rimmer, Lister, Kryten, Cat
{

	/**
	 * @Test()
	 */
	public function publicCalculation()
	{
	}



	protected function protectedCalculation()
	{

	}



	private function privateCalculation()
	{

	}



	public function injectBar()
	{

	}

}



/**
 * @Test()
 */
class SmegHead
{

	public function injectFoo()
	{

	}



	public function bar()
	{

	}

}



/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Test extends Annotation
{

}



class CustomTemplate implements Nette\Templating\ITemplate
{

	public function render()
	{
	}

}



class MyPointcutFilter implements Filter
{

	public function matches(Method $method)
	{
		return $method->getClassName() === 'KdybyTests\Aop\Legie';
	}



	public function listAcceptedTypes()
	{
		return FALSE;
	}

}

interface LoggerInterface
{

}

class CommonClass
{

}

class PackageClass
{

}

class PointcutTestingAspect
{

}

class FeedAggregator
{

}

class MockPresenter extends Nette\Application\UI\Presenter
{

	public function renderDefault()
	{

	}

	public function actionDefault()
	{

	}

	public function handleSort()
	{

	}

}

class BaseClass
{

	public function __construct($x) {
		;
	}
}

class InheritedClass extends BaseClass
{

	public function __construct($x, $y)
	{
		parent::__construct($x);
	}
}