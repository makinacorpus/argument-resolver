# Callback argument resolver

Provide a callback argument resolver interface and implementation, very similar
to `symfony/http-kernel`'s `ArgumentResolver` class implementation, but usable
out of Symfony project and HTTP context.

This API does not depend upon any other, and has copy-pasted some code
from the Symfony component:

 - This choice has been made because Symfony's code hardwires the Request
   object dependency as a part of its API where we need to be able to use
   this API out of the HTTP context.
 - This makes this component resilient to Symfony code changes in the long
   term and simplifies maintainance.

# 1.0 Roadmap

 - [x] API basics
 - [x] Retrieve features from `makinacorpus/access-control`
 - [x] Basic Symfony bundle
 - [x] Service argument value resolver

# Get started

## Installation

Install it using composer:

```sh
composer require makinacorpus/argument-resolver
```

## As Symfony bundle setup

Then add to your `config/bundles.php` file:

```php
<?php

return [
    // ... Your other bundles.
    MakinaCorpus\ArgumentResolver\Bridge\Symfony\ArgumentResolverBundle::class => ['all' => true],
];
```

## Basic usage

Consider the following function:

```php
namespace Some\Namespace;

function foo(int $bar, ?int $foo, string $fizz = 'foo', \DateTime ...$dates): void
{
    // Do something.
}
```

This will reconciliate arguments from argument value resolvers:

```php
use MakinaCorpus\ArgumentResolver\Context\ArrayResolverContext;
use MakinaCorpus\ArgumentResolver\DefaultArgumentResolver;

$callback = '\\Some\\Namespace\\foo';

$argumentResolver = new DefaultArgumentResolver();

$arguments = $argumentResolver->getArguments(
    $callback,
    new ArrayResolverContext(
        [
            'bar' => 12,
        ]
    )
);

echo ($callback)(...$arguments);
// 12
```

And that's it. This has no real added value as-is, but it becomes handy when
working with dynamic service method calls in a large complex app.

# Symfony integration

## Basics

More than one argument resolver can coexist in container, each one will have a
dedicated string identifier, for example:

 - when using `makinacorpus/access-control`, the `access-control` argument
   resolver will be created,
 - when using `makinacorpus/corebus`, the `corebus` argument resolver will be
   created.

For plugging in custom value resolver, there is two different tags:

 - use the `custom.argument_resolver.default` tag for registering a value resolver
   to all argument resolvers,
 - use the `custom.argument_resolver.NAME` tag, where `NAME` is one of the argument
   resolver identifiers for register a given value converter.

## Define a new argument resolver

Whatever situation is yours, creating a new resolver in a project, or in a
custom bundle, you must add a new service using the `argument_resolver` tag
to define a new service, such as:

```yaml
services:
    # ... your other services
    my_custom_bundle.argument_resolver:
        class: MakinaCorpus\ArgumentResolver\DefaultArgumentResolver
        tags: [{ name: 'custom.argument_resolver', id: 'my_custom_name' }]
```

You may also want to provide some additional custom value resolvers:

```yaml
services:
    # ... your other services
    MyCustomBundle\ArgumentResolver\Resolver\FooValueResolver:
        tags: ['custom.argument_resolver.my_custom_name']
```

Notice that the name after the last `.` in the `custom.argument_resolver.my_custom_name`
string refers to the argument resolver `id` attribute.

In your services, use the `my_custom_bundle.argument_resolver` service for
injection, since you defined for your own usage.

Some compiler passes will do the hard job of autowiring anything that needs
to be autowired for you.
