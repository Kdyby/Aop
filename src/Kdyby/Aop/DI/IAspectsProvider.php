<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\DI;

use Kdyby;
use Nette;



/**
 * Implement this interface to your CompilerExtension if you want it to provide aspects.
 *
 * Example:
 * <code>
 * class AclExtension extends Nette\DI\CompilerExtension implements \Kdyby\Aop\DI\IAspectsProvider
 * {
 *     public function getAspectsConfiguration()
 *     {
 *         return \Kdyby\Aop\DI\AspectsExtension::loadAspects(__DIR__ . '/aspects.neon', $this);
 *     }
 * }
 * </code>
 *
 * The `aspects.neon` file should be list of unnamed services
 *
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
interface IAspectsProvider
{

	/**
	 * @return AspectsConfig
	 */
	function getAspectsConfiguration();

}
