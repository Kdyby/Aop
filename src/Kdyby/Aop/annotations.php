<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Aop;

use Doctrine;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @property string $value
 * @property-read string $value
 */
interface Annotation
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface AdviceAnnotation extends Annotation
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class BaseAnnotation extends Doctrine\Common\Annotations\Annotation implements Annotation
{

	/**
	 * @return string
	 */
	public static function getClassName()
	{
		return get_called_class();
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @Annotation
 * @Target("CLASS")
 */
class Aspect extends BaseAnnotation
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @Annotation
 * @Target("METHOD")
 */
class Before extends BaseAnnotation implements AdviceAnnotation
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @Annotation
 * @Target("METHOD")
 */
class AfterReturning extends BaseAnnotation implements AdviceAnnotation
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @Annotation
 * @Target("METHOD")
 */
class AfterThrowing extends BaseAnnotation implements AdviceAnnotation
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @Annotation
 * @Target("METHOD")
 */
class After extends BaseAnnotation implements AdviceAnnotation
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @Annotation
 * @Target("METHOD")
 */
class Around extends BaseAnnotation implements AdviceAnnotation
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @Annotation
 * @Target({"METHOD", "CLASS", "PROPERTY"})
 */
class Introduce extends BaseAnnotation implements AdviceAnnotation
{

}
