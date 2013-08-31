<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop\PhpGenerator;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PhpFile extends Nette\Object
{

	/**
	 * @var array|NamespaceBlock[]
	 */
	public $namespaces = array();



	/**
	 * @param string $namespace
	 * @return NamespaceBlock
	 */
	public function getNamespace($namespace)
	{
		if (!isset($this->namespaces[$namespace])) {
			$this->namespaces[$namespace] = new NamespaceBlock($namespace);
		}

		return $this->namespaces[$namespace];
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		$s = '<' . "?php\n\n";

		foreach ($this->namespaces as $namespace) {
			$s .= $namespace . "\n\n";
		}

		return $s;
	}

}
