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
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class NamespaceBlock extends Nette\Object
{

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var array|Code\ClassType[]
	 */
	public $classes = [];

	/**
	 * @var array
	 */
	public $imports = [];



	public function __construct($namespace)
	{
		$this->name = $namespace;
	}



	/**
	 * @param Code\ClassType $class
	 * @return Code\ClassType
	 */
	public function addClass(Code\ClassType $class)
	{
		return $this->classes[$class->getName()] = $class;
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		$s = 'namespace ' . $this->name . " {\n\n";

		foreach ($this->imports as $import => $alias) {
			$s .= 'use ' . (is_numeric($import) ? $alias : $import . ' as ' . $alias) . ";\n";
		}

		if ($this->imports) {
			$s .= "\n";
		}

		foreach ($this->classes as $class) {
			$s .= "$class\n\n";
		}

		return $s . '}';
	}

}
