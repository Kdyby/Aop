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
 * @author Filip Procházka <filip@prochazka.su>
 */
class AspectsConfig
{

	use Nette\SmartObject;

	/**
	 * @var array
	 */
	private $aspectsList;

	/**
	 * @var bool
	 */
	private $prefix = TRUE;



	public function __construct(array $aspectsList)
	{
		$this->aspectsList = $aspectsList;
	}



	public function disablePrefixing(): self
	{
		$this->prefix = FALSE;
		return $this;
	}



	public function load(Nette\DI\Compiler $compiler, Nette\DI\ContainerBuilder $containerBuilder): void
	{
		foreach ($this->aspectsList as $def) {
			if ( (!is_array($def)) && !is_string($def) && (!$def instanceof \stdClass || empty($def->value)) && !$def instanceof Nette\DI\Statement) {
				$serialised = Nette\Utils\Json::encode($def);
				throw new Kdyby\Aop\UnexpectedValueException("The service definition $serialised is expected to be an array or Neon entity.");
			}
			$definition = new Nette\DI\Definitions\ServiceDefinition();
			$definition->setFactory(is_array($def) ? $def['class'] : $def);
			$definition->setTags([AspectsExtension::ASPECT_TAG => true]);
			$containerBuilder->addDefinition(null, $definition);
		}
	}

}
