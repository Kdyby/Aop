<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Aop;

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

	public function publicCalculation()
	{
	}



	protected function protectedCalculation()
	{

	}



	public function injectBar()
	{

	}

}



class SmegHead
{

	public function injectFoo()
	{

	}

}


