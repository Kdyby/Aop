# Documentation

This extension is here to provide [AOP functionality](http://en.wikipedia.org/wiki/Aspect-oriented_programming) into [Nette Framework](http://nette.org/) [DI Container](http://doc.nette.org/en/dependency-injection#toc-nette-di-container).


## Installation


The best way to install Kdyby/Aop is using  [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/aop:@dev
```

You can enable the extension using your neon config.

```yml
extensions:
	aop: Kdyby\Aop\DI\AopExtension
	annotations: Kdyby\Annotations\DI\AnnotationsExtension
	aspects: Kdyby\Aop\DI\AspectsExtension
```

You can find the [documentation of annotations extension here](https://github.com/Kdyby/Annotations/blob/master/docs/en/index.md).


## Configuration

This extension creates new configuration section `aspects` and it should behave exactly like services, but all the services are marked as aspects.

```yml
aspects:
	- MyApp\LoggingAspect(@dep, %param%)
```

> Never give the aspects any names, keep them anonymouse.

This internally works exactly like `services` section, but it tags all the aspects with `kdyby.aspect` tag.
So if you don't want to, or cannot use the section, just tag the aspect and you're good to go.


#### IAspectsProvider

Implement this interface to your CompilerExtension if you want it to provide aspects. Example:

```php
class AclExtension extends Nette\DI\CompilerExtension implements \Kdyby\Aop\DI\IAspectsProvider
{
	public function getAspectsConfiguration()
	{
		return \Kdyby\Aop\DI\AspectsExtension::loadAspects(__DIR__ . '/aspects.neon', $this);
	}
}
```

The `aspects.neon` file should be list of unnamed services as in `aspects` section.


#### AspectsExtension

> There are two extensions?!

Yeah, why not? I needed the section `aspects` for services and section `aop` for configuration.


## Dictionary

<dl>
	<dt>Aspect</dt>
	<dd>The object that extends behaviour of other objects</dd>

	<dt>Advice</dt>
	<dd>The action that is taken when you're extending the behaviour. You can read it as "The object Application is being advised by Logger Aspect"</dd>

	<dt>Join point</dt>
	<dd>The exact moment at the runtime, where your advice is connected.</dd>

	<dt>Pointcut</dt>
	<dd>The special syntax for join point definition</dd>
</dl>


## Advice types

<dl>
	<dt>Before</dt>
	<dd>This advice can be used for reading/logging of method arguments, or their modification</dd>

	<dt>After</dt>
	<dd>Think of this as the `finally` keyword, it should get called even if the method throws, but it cannot change what is returned.</dd>

	<dt>After returning</dt>
	<dd>You can read/log or modify the return value here</dd>

	<dt>After throwing</dt>
	<dd>You can read the exception here</dd>

	<dt>Around</dt>
	<dd>The most powerful advice, if it's defined, it can prevent the original method from being called, change it's arguments or return value, damn, even the exception.</dd>
</dl>

> Choose wisely, great powers comes with a (performance) cost.


## Pointcut Syntax

#### method(`[public|protected] ns\class->method(argument == value)`)

Examples:

- `method(public Nette\Application\Application->processRequest())`
- `method(Nette\Application\UI\Presenter->[render|action|handle]*())` - should match all three variants of methods, meaning all `render*()`, `action*()` and `handle*()` (presenters have to be registered in DIC)
- `method(Nette\Application\UI\*->[handle]*())` - should match all `Presenter`, `Control` and `PresenterComponent` signals (they have to be registered in DIC)
- `method(*->*())` - matches all methods of all services in DIC - **You should never do this, it would be really painful!!!**

> Keep those conditions as simple as possible! The more complex they are, the longer it will take to compile!

The arguments are evaluated at runtime, read more at `evaluate` pointcut.

#### class(`ns\class`)

Examples:

- `class(Nette\Application\UI\Presenter)`
- `class(Nette\Application\IPresenter)` - yeah, it can match also interfaces or parent classes
- `class(Nette\Application\UI\*)` - matches all classes in namespace `Nette\Application\UI`
- `class(*)` - matches all classes - **You should never do this, it would be really painful!!!**

> Keep in mind, that exact class name can be be optimized, to analyze only those services, that matches it exactly.
> When you use wildmark, all the services has to be scanned it they match and this can literary kill your application in development mode!

#### within(`ns\class`)

This is basically an alias for `class` pointcut. But it expresses better the nature of this pointcut.
It scans all the types of the service and if it implements an interface, or one of parent classes matches, than this will also match.

#### filter(`filterClass`)

Argument of this pointcut should be name of class that implements `Kdyby\Aop\Pointcut\Filter`.
You can basically write your own pointcut filter here.

#### setting(`%foo.bar% == TRUE`)

Wanna have the ability to turn on and off the advices based on DIC parameters? No problem!

#### evaluate(`this.foo.bar == TRUE`)

This is really advanced runtime pointcut and it's also used in method arguments.
What does it mean, runtime? Well, it's serialised to condition and every time you run the method, the condition is evaluated and decides it the advice gets called.

Examples:

- `evaluate($argument == 1)` - this is for arguments matching
- `evaluate(this.dave.lister[kryten] == TRUE)` - this translates to $this->dave->lister['kryten'] but it's little smarter than that, have a look at [Symfony/PropertyAccess](http://symfony.com/doc/current/components/property_access/index.html), it's used for (surprisingly) property access.
- `evaluate(context.httpRequest.post == TRUE)` - this is translated to `$context->getService('httpRequest')->isPost()` but also here, for property access is used `Symfony/PropertyAccess`
- `evaluate(context.Nette\Http\IRequest.post == TRUE)` - this is translated to `$context->getByType('Nette\Http\IRequest')->isPost()`
- `evaluate(%foo.bar% == TRUE)` - you can write this, but it's a nonsense, there is `setting` pointcut for DIC parameters

And don't forget to have a look at [Symfony/PropertyAccess](http://symfony.com/doc/current/components/property_access/index.html).

#### classAnnotatedWith(`Some\Annotation`)

Matches all classes, that are annotated with this annotation. It uses [Kdyby/Annotations](https://github.com/Kdyby/Annotations) for reading them.

#### methodAnnotatedWith(`Some\Annotation`)

Matches all methods, that are annotated with this annotation.


## Join points

When join point is invoked, an instance of concrete JoinPoint class is also created and passed to your advice (the method on aspect).
For every type of advice, there is a join point class in namespace `Kdyby\Aop\JoinPoint`.

- `BeforeMethod` provides arguments and can be used for arguments modification
- `AfterMethod` provides return value or exception
- `AfterReturning` provides return value and can be used for it's modification
- `AfterThrowing` provides the exception (only if there is some thrown)
- `AroundMethod` provides everything and allows you to change it completely


## Aspect examples

Let's utilize what we've learned so far and write some aspects.

```php
use Kdyby\Aop; // annotations can recognize imports, because they behave like classes

class BeforeAspect extends Nette\Object
{
	private $db;

	public function __construct(Kdyby\Doctrine\Connection $db)
	{
		$this->db = $db;
	}

	/**
	 * @Aop\Before("method(CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\BeforeMethod $before)
	{
		$this->db->insert('log', array('something' => $before->arguments[1]));
		$before->setArgument(1, "changed value");
	}

}
```

This aspect will add an advice `log` to method `magic` of class `CommonService`, that will log it's second argument (index `1`) and always change it.


```php
class AroundAspect extends Nette\Object
{

	/**
	 * @Aop\Around("method(CommonService->magic)")
	 */
	public function log(Aop\JoinPoint\AroundMethod $around)
	{
		// I can change the arguments here

		$result = $around->proceed();

		// I can change the result here

		return $result;
	}

}
```

In around aspect, you must manually call the method `->proceed()` which will either invoke another around advice in chain, or the method itself. You can never know and you shouldn't even care.
