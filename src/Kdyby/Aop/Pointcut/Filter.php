<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\Pointcut;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface Filter
{

	/**
	 * Analyzes method if it can be accepted.
	 *
	 * @param Method $method
	 * @return bool
	 */
	function matches(Method $method);



	/**
	 * Tries to figure out types, that could be used for searching in ContainerBuilder.
	 * Pre-filtering of services should increase speed of filtering.
	 *
	 * @return array|bool
	 */
	function listAcceptedTypes();

}
