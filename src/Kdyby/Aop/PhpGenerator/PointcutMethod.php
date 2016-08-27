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
	private $before = [];

	/**
	 * @var array
	 */
	private $around = [];

	/**
	 * @var array
	 */
	private $afterReturning = [];

	/**
	 * @var array
	 */
	private $afterThrowing = [];

	/**
	 * @var array
	 */
	private $after = [];



	public function addAdvice(Kdyby\Aop\DI\AdviceDefinition $adviceDef)
	{
		$adviceMethod = $adviceDef->getAdvice();

		switch ($adviceDef->getAdviceType()) {
			case Kdyby\Aop\Before::getClassName():
				$this->before[] = $this->generateRuntimeCondition($adviceDef, Code\Helpers::format(
					'$this->__getAdvice(?)->?($__before = new \Kdyby\Aop\JoinPoint\BeforeMethod($this, __FUNCTION__, $__arguments));' . "\n" .
					'$__arguments = $__before->getArguments();',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));

				break;

			case Kdyby\Aop\Around::getClassName():
				$this->around[] = $this->generateRuntimeCondition($adviceDef, Code\Helpers::format(
					'$__around->addChainLink($this->__getAdvice(?), ?);',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			case Kdyby\Aop\AfterReturning::getClassName():
				$this->afterReturning[] = $this->generateRuntimeCondition($adviceDef, Code\Helpers::format(
					'$this->__getAdvice(?)->?($__afterReturning = new \Kdyby\Aop\JoinPoint\AfterReturning($this, __FUNCTION__, $__arguments, $__result));' . "\n" .
					'$__result = $__afterReturning->getResult();',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			case Kdyby\Aop\AfterThrowing::getClassName():
				$this->afterThrowing[] = $this->generateRuntimeCondition($adviceDef, Code\Helpers::format(
					'$this->__getAdvice(?)->?(new \Kdyby\Aop\JoinPoint\AfterThrowing($this, __FUNCTION__, $__arguments, $__exception));',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			case Kdyby\Aop\After::getClassName():
				$this->after[] = $this->generateRuntimeCondition($adviceDef, Code\Helpers::format(
					'$this->__getAdvice(?)->?(new \Kdyby\Aop\JoinPoint\AfterMethod($this, __FUNCTION__, $__arguments, $__result, $__exception));',
					$adviceMethod->getServiceDefinition()->getServiceId(),
					$adviceMethod->getName()
				));
				break;

			default:
				throw new Kdyby\Aop\InvalidArgumentException("Unknown advice type " . $adviceDef->getAdviceType());
		}
	}



	private function generateRuntimeCondition(Kdyby\Aop\DI\AdviceDefinition $adviceDef, $code)
	{
		$filter = $adviceDef->getFilter();
		if (!$filter instanceof Kdyby\Aop\Pointcut\RuntimeFilter) {
			return $code;

		} elseif (!$condition = $filter->createCondition()) {
			return $code;
		}

		foreach ($adviceDef->getTargetMethod()->getParameterNames() as $i => $name) {
			$condition = str_replace('$' . $name, '$__arguments[' . $i . ']', $condition);
		}

		return Code\Helpers::format("if ? {\n?\n}", new Code\PhpLiteral($condition), new Code\PhpLiteral(Nette\Utils\Strings::indent($code)));
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		$this->setBody('');

		if (strtolower($this->getName()) === '__construct') {
			$this->addParameter('_kdyby_aopContainer')
					->setTypeHint('\Nette\DI\Container');
			$this->addBody('$this->_kdyby_aopContainer = $_kdyby_aopContainer;');
		}

		$this->addBody('$__arguments = func_get_args(); $__exception = $__result = NULL;');

		if ($this->before) {
			foreach ($this->before as $before) {
				$this->addBody($before);
			}
		}

		if ($this->afterThrowing || $this->after) {
			$this->addBody('try {');
		}

		if (!$this->around) {
			$parentCall = Code\Helpers::format('$__result = call_user_func_array("parent::?", $__arguments);', $this->getName());
		} else {
			$parentCall = Code\Helpers::format('$__around = new \Kdyby\Aop\JoinPoint\AroundMethod($this, __FUNCTION__, $__arguments);');
			foreach ($this->around as $around) {
				$parentCall .= "\n" . $around;
			}
			$parentCall .= "\n" . Code\Helpers::format('$__result = $__around->proceed();');
		}

		$this->addBody(($this->afterThrowing || $this->after) ? Nette\Utils\Strings::indent($parentCall) : $parentCall);

		if ($this->afterThrowing || $this->after) {
			$this->addBody('} catch (\Exception $__exception) {');
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
				$this->addBody('if (empty($__exception)) {');
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
			$this->addBody('if ($__exception) { throw $__exception; }');
		}
		$this->addBody('return $__result;');

		return parent::__toString();
	}



	/**
	 * @param \ReflectionMethod $from
	 * @param Code\Method $method
	 * @throws \ReflectionException
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
