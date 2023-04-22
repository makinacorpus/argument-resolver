<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\ArgumentResolver\DefaultArgumentResolver;
use MakinaCorpus\ArgumentResolver\Metadata\CallableMetadataFactory;
use MakinaCorpus\ArgumentResolver\Resolver\ServiceArgumentValueResolver;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class RegisterArgumentResolverPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public static function resolverServiceId(string $id): string
    {
        return 'argument_resolver.' . $id;
    }

    public static function registerServiceMethods(ContainerBuilder $container, string $resolverId, string $serviceId, ?array $methods = null): void
    {
        // This populates incrementally some container parameter.
        if ($container->hasParameter('argument_resolver.service_map')) {
            $map = $container->getParameter('argument_resolver.service_map');
        } else {
            $map = [];
        }

        if (!isset($map[$resolverId][$serviceId])) {
            $map[$resolverId][$serviceId] = [];
        }
        // Gracefully merge methods.
        if ($methods) {
            foreach ($methods as $method) {
                $map[$resolverId][$serviceId][$method] = $method;
            }
        }

        $container->setParameter('argument_resolver.service_map', $map);
    }

    private function createServiceLocatorFor(ContainerBuilder $container, string $resolverId, array $services)
    {
        $resolverServiceId = self::resolverServiceId($resolverId);

        // Service could have been removed.
        if (!$container->hasDefinition($resolverServiceId) && !$container->hasAlias($resolverServiceId)) {
            return;
        }

        $parameterBag = $container->getParameterBag();

        // That's what we build: service dependencies list.
        $dependencies = [];

        foreach ($services as $serviceId => $methods) {
            $definition = $container->getDefinition($serviceId);
            $className = $definition->getClass();

            // Resolve real service class, taking parent definitions into account.
            while ($definition instanceof ChildDefinition) {
                $definition = $container->findDefinition($definition->getParent());
                $className = $className ?: $definition->getClass();
            }

            $className = $parameterBag->resolveValue($className);
            if (!$refClass = $container->getReflectionClass($className)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" used for service "%s" cannot be found.', $className, $serviceId));
            }

            // Aggregate targeted method.
            if ($methods) {
                foreach ($methods as $index => $methodName) {
                    if (!$refClass->hasMethod($methodName)) {
                        throw new InvalidArgumentException(\sprintf('Class "%s" used for service "%s" has no method "%s".', $className, $serviceId, $methodName));
                    }
                    $methods[$index] = $refClass->getMethod($methodName);
                }
            }

            foreach ($methods ?? $refClass->getMethods() as $refMethod) {
                \assert($refMethod instanceof \ReflectionMethod);

                if ($refMethod->isAbstract() || $refMethod->isConstructor() || $refMethod->isDestructor()) {
                    continue;
                }

                // Do not populate method that are being called programatically
                // by the container, they are initialization methods.
                // Some setters are called using registerForAutoconfiguration()
                // which happens way later during compilation, we have no way to
                // catch those, there's going to be a lot of false posivite here.
                foreach ($definition->getMethodCalls() as $methodCall) {
                    if ($methodCall[0] === $refMethod->getName()) {
                        continue 2;
                    }
                }

                // For each method, find parameters whose class name is a service
                // name, and inject it into the service locator.
                foreach ($refMethod->getParameters() as $refParam) {
                    \assert($refParam instanceof \ReflectionParameter);

                    if (!$refParam->hasType()) {
                        continue;
                    }
                    $refType = $refParam->getType();
                    if ($refType->isBuiltin() || !$refType instanceof \ReflectionNamedType) {
                        continue;
                    }

                    // This only works when services are registered or aliased with
                    // the interface or class name here.
                    $targetServiceId = $refType->getName();

                    if ($container->hasDefinition($targetServiceId) || $container->hasAlias($targetServiceId)) {
                        // This trick with container reference behavior comes
                        // from RegisterControllerArgumentLocatorsPass: it
                        // allows us to avoid false positives: when services
                        // are found during autoconfiguration/autowiring pass
                        // but then removed, we have cache build errors if we
                        // reference them in the service locator. Using this
                        // behavior trick, this goes to runtime instead and
                        // never triggers for false positive methods which
                        // were not meant to be used by the argument resolver.
                        // Symfony does a very bad job in auto-documenting its
                        // own code, we have to hard guess why they do things.
                        // I guess they did it for the same reason as us.
                        $invalidBehavior = ContainerInterface::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE;
                        if ($refParam->allowsNull()) {
                            $invalidBehavior = ContainerInterface::NULL_ON_INVALID_REFERENCE;
                        }

                        $dependencies[$targetServiceId] = new Reference($targetServiceId, $invalidBehavior);
                    }
                }
            }
        }

        if (!$dependencies) {
            return;
        }

        $serviceLocatorRef = ServiceLocatorTagPass::register($container, $dependencies);

        $valueResolverDefinition = new Definition();
        $valueResolverDefinition->setClass(ServiceArgumentValueResolver::class);
        $valueResolverDefinition->setArguments([$serviceLocatorRef]);
        // Let it pass after user ones, be polite.
        $valueResolverDefinition->addTag($resolverServiceId, ['priority' => -50]);
        $container->setDefinition($resolverServiceId . '.services_resolver', $valueResolverDefinition);
    }

    /**
     * From the previously built map, create service locators and argument
     * value resolvers.
     */
    private function createServiceLocators(ContainerBuilder $container)
    {
        if (!$container->hasParameter('argument_resolver.service_map')) {
            return;
        }
        $map = $container->getParameter('argument_resolver.service_map');

        foreach ($map as $resolverId => $services) {
            $this->createServiceLocatorFor($container, $resolverId, $services);
        }

        $container->getParameterBag()->remove('argument_resolver.service_map');
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $registered = [];

        foreach ($container->findTaggedServiceIds('argument_resolver') as $serviceId => $attributes) {
            $id = $attributes[0]['id'] ?? null;
            if (!$id) {
                throw new InvalidArgumentException(\sprintf("Service '%s' uses the 'argument_resolver' tag without setting the 'id' attribute.", $serviceId));
            }
            if (isset($registered[$id])) {
                throw new InvalidArgumentException(\sprintf("Service '%s' uses the 'argument_resolver' and redefined the '%s' value for the 'id' attribute.", $serviceId, $id));
            }

            $definition = $container->getDefinition($serviceId);
            if (!$definition->getClass()) {
                $definition->setClass(DefaultArgumentResolver::class);
            }
            if (!$definition->getArguments()) {
                $definition->setArgument(0, new Reference(CallableMetadataFactory::class));
            }

            // Register a predictible alias for the argument resolver.
            $newAlias = self::resolverServiceId($id);
            if ($newAlias !== $serviceId) {
                $container->setAlias($newAlias, $serviceId);
            }

            $registered[$id] = $definition;
        }

        // For all registered services that need method service injection,
        // create service locators and inject them into their corresponding
        // argument resolver via an argument value locator.
        $this->createServiceLocators($container);

        // Add "argument_resolver.ID" tags for all identifiers, to all "default"
        // identified value resolvers, to propagate them to all definitions.
        // This allow us to preserve the priority.
        foreach (\array_keys($registered) as $id) {
            foreach ($container->findTaggedServiceIds('argument_resolver.default') as $serviceId => $attributes) {
                $definition = $container->getDefinition($serviceId);
                $resolverTag = self::resolverServiceId($id);
                if (!$definition->hasTag($resolverTag)) {
                    $definition->addTag($resolverTag, $attributes[0]);
                }
            }
        }

        foreach ($registered as $id => $definition) {
            // Find already set value resolver for this service, they could exist.
            $valueResolvers = $definition->getArguments()[1] ?? null;
            if ($valueResolvers) {
                if (!\is_array($valueResolvers)) {
                    $valueResolvers = [$valueResolvers];
                }
            } else {
                $valueResolvers = [];
            }

            // They are sorted, just set everything into place.
            foreach ($this->findAndSortTaggedServices(self::resolverServiceId($id), $container) as $reference) {
                $valueResolvers[] = $reference;
            }
            $definition->setArgument(1, $valueResolvers);
        }
    }
}
