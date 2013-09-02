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
class PointcutMethod extends Code\Method
{

	/**
	 * @var array
	 */
	private $before = array();

	/**
	 * @var array
	 */
	private $around = array();

	/**
	 * @var array
	 */
	private $afterReturning = array();

	/**
	 * @var array
	 */
	private $afterThrowing = array();

	/**
	 * @var array
	 */
	private $after = array();



	public function addAdvice(Kdyby\Aop\DI\AdviceDefinition $adviceDef)
	{
		$adviceMethod = $adviceDef->getAdvice();

		switch ($adviceDef->getAdviceType()) {
			case Kdyby\Aop\Before::getClassName():
				$this->before[] = Code\Helpers::format(
					'$this->__getAdvice(?)->?($before = new \Kdyby\Aop\JoinPoint\BeforeMethod($this, __FUNCTION__, $arguments));' . "\n" .
					'$arguments = $before->getArguments();',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				);

				break;

			case Kdyby\Aop\Around::getClassName():
				$this->around[] = Code\Helpers::format(
					'$around->addChainLink($this->__getAdvice(?), ?);',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				);
				break;

			case Kdyby\Aop\AfterReturning::getClassName():
				$this->afterReturning[] = Code\Helpers::format(
					'$this->__getAdvice(?)->?($afterReturning = new \Kdyby\Aop\JoinPoint\AfterReturning($this, __FUNCTION__, $arguments, $result));' . "\n" .
					'$result = $afterReturning->getResult();',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				);

				break;

			case Kdyby\Aop\AfterThrowing::getClassName():
				$this->afterThrowing[] = Code\Helpers::format(
					'$this->__getAdvice(?)->?(new \Kdyby\Aop\JoinPoint\AfterThrowing($this, __FUNCTION__, $arguments, $exception));',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				);
				break;

			case Kdyby\Aop\After::getClassName():
				$this->after[] = Code\Helpers::format(
					'$this->__getAdvice(?)->?(new \Kdyby\Aop\JoinPoint\AfterMethod($this, __FUNCTION__, $arguments, $result, $exception));',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				);
				break;

			default:
				throw new Kdyby\Aop\InvalidArgumentException("Unknown advice type " . $adviceDef->getAdviceType());
		}
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		$this->setBody('');
		$this->addBody('$arguments = func_get_args(); $exception = $result = NULL;');

		if ($this->before) {
			foreach ($this->before as $before) {
				$this->addBody($before);
			}
		}

		if ($this->afterThrowing || $this->after) {
			$this->addBody('try {');
		}

		if (!$this->around) {
			$argumentsPass = array();
			foreach (array_values($this->getParameters()) as $i => $parameter) {
				$argumentsPass[] = '$arguments[' . $i . ']';
			}
			$parentCall = Code\Helpers::format('$result = parent::?(?);', $this->getName(), new Code\PhpLiteral(implode(', ', $argumentsPass)));

		} else {
			$parentCall = Code\Helpers::format('$around = new \Kdyby\Aop\JoinPoint\AroundMethod($this, __FUNCTION__, $arguments);');
			foreach ($this->around as $around) {
				$parentCall .= "\n" . $around;
			}
			$parentCall .= "\n" . Code\Helpers::format('$result = $around->proceed();');
		}

		$this->addBody(($this->afterThrowing || $this->after) ? Nette\Utils\Strings::indent($parentCall) : $parentCall);

		if ($this->afterThrowing || $this->after) {
			$this->addBody('} catch (\Exception $exception) {');
		}

		if ($this->afterThrowing) {
			foreach ($this->afterThrowing as $afterThrowing) {
				$this->addBody(Nette\Utils\Strings::indent($afterThrowing));
			}
		}

		if ($this->afterThrowing || $this->after) {
			$this->addBody('}');
		}

		if ($this->afterReturning) {
			if ($this->afterThrowing || $this->after) {
				$this->addBody('if (empty($exception)) {');
			}

			foreach ($this->afterReturning as $afterReturning) {
				$this->addBody(($this->afterThrowing || $this->after) ? Nette\Utils\Strings::indent($afterReturning) : $afterReturning);
			}

			if ($this->afterThrowing || $this->after) {
				$this->addBody('}');
			}
		}

		if ($this->after) {
			foreach ($this->after as $after) {
				$this->addBody($after);
			}
		}

		if ($this->afterThrowing || $this->after) {
			$this->addBody('if ($exception) { throw $exception; }');
		}
		$this->addBody('return $result;');

		return parent::__toString();
	}



	/**
	 * @param \ReflectionMethod $from
	 * @param Code\Method $method
	 * @throws \Exception|\ReflectionException
	 * @return Code\Method
	 */
	public static function expandTypeHints(\ReflectionMethod $from, Code\Method $method)
	{
		$parameters = $method->getParameters();
		/** @var Code\Parameter[] $parameters */

		foreach ($from->getParameters() as $paramRefl) {
			try {
				$parameters[$paramRefl->getName()]->setTypeHint($paramRefl->isArray() ? 'array' : ($paramRefl->getClass() ? '\\' . $paramRefl->getClass()->getName() : ''));
			} catch (\ReflectionException $e) {
				if (preg_match('#Class (.+) does not exist#', $e->getMessage(), $m)) {
					$parameters[$paramRefl->getName()]->setTypeHint('\\' . $m[1]);
				} else {
					throw $e;
				}
			}
		}
		$method->setParameters($parameters);

		if (!$method->getVisibility()) {
			$method->setVisibility('public');
		}

		return $method;
	}

}
