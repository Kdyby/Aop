<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Kdyby;
use Kdyby\Aop\Pointcut;
use Nette;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AspectsExtension extends Nette\DI\CompilerExtension
{
	const ASPECT_TAG = 'kdyby.aspect';



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$config = new AspectsConfig($this->getConfig(), $this);
		$config->disablePrefixing()->load($this->compiler, $builder);

		foreach ($this->compiler->getExtensions() as $extension) {
			if (!$extension instanceof IAspectsProvider) {
				continue;
			}

			if (!($config = $extension->getAspectsConfiguration()) || !$config instanceof AspectsConfig) {
				$refl = new Nette\Reflection\Method($extension, 'getAspectsConfiguration');
				$given = is_object($config) ? 'instance of ' . get_class($config) : gettype($config);
				throw new Kdyby\Aop\UnexpectedValueException("Method $refl is expected to return instance of Kdyby\\Aop\\DI\\AspectsConfig, but $given given.");
			}

			$config->load($this->compiler, $builder);
		}
	}



	/**
	 * @param string $configFile
	 * @param Nette\DI\CompilerExtension $extension
	 * @return AspectsConfig
	 */
	public static function loadAspects($configFile, Nette\DI\CompilerExtension $extension)
	{
		return new AspectsConfig($extension->loadFromFile($configFile), $extension);
	}



	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('aspects', new AspectsExtension());
		};
	}

}
