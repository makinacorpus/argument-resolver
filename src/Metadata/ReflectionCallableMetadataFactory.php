<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Metadata;

use MakinaCorpus\ArgumentResolver\Error\InvalidCallableError;
use MakinaCorpus\ArgumentResolver\Error\UnsupportedParameterTypeError;

class ReflectionCallableMetadataFactory implements CallableMetadataFactory
{
    /**
     * {@inheritdoc}
     */
    public function getArgumentsMetadata(callable $callback): array
    {
        $ret = [];
        try {
            $callback = \Closure::fromCallable($callback);
            $refFunc = new \ReflectionFunction($callback);

            foreach ($refFunc->getParameters() as $refParam) {
                \assert($refParam instanceof \ReflectionParameter);
                $ret[] = new ArgumentMetadata(
                    $refParam->getName(),
                    $this->getParameterAllowedTypes($refParam),
                    $refParam->isVariadic(),
                    $hasDefault = $refParam->isDefaultValueAvailable(),
                    $hasDefault ? $refParam->getDefaultValue() : null,
                    $refParam->allowsNull()
                );
            }
            return $ret;
        } catch (\ReflectionException $e) {
            throw new InvalidCallableError("Could not introspect provided callable.", 0, $e);
        }
    }

    /**
     * Find all allowed types for a given reflection parameter.
     */
    private function getParameterAllowedTypes(\ReflectionParameter $refParam): array
    {
        if (!$refParam->hasType()) {
            return [];
        }

        $refType = $refParam->getType();

        if ($refType instanceof \ReflectionUnionType) {
            $ret = [];
            foreach ($refType->getTypes() as $candidate) {
                \assert($candidate instanceof \ReflectionNamedType);
                // mixed pseudo type is an allow all, so allow all.
                if ('mixed' === $candidate) {
                    return [];
                }
                $ret[] = $candidate->getName();
            }
            return $ret;
        }

        if ($refType instanceof \ReflectionNamedType) {
            return 'mixed' === ($typeName = $refType->getName()) ? [] : [$typeName];
        }

        throw new UnsupportedParameterTypeError(\sprintf("%s reflection types are not supported yet.", \get_class($refType)));
    }
}
