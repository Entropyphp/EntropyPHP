<?php

namespace Entropy\Invoker\ParameterResolver;

use Invoker\ParameterResolver\ParameterResolver;
use ReflectionFunctionAbstract;

class AssociativeArrayTypeHintResolver implements ParameterResolver
{
    /**
     * @inheritDoc
     */
    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        $parameters = $reflection->getParameters();

        // Skip parameters already resolved
        if (! empty($resolvedParameters)) {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($name, $providedParameters)) {
                $type = $parameter->getType();
                $value = $providedParameters[$name];

                if ($type && !$type->isBuiltin()) {
                    // If not a built-in type, just assign the value
                    $resolvedParameters[$index] = $value;
                } elseif ($type && $type->getName() === 'int' && is_numeric($value)) {
                    // Convert to int if required
                    $resolvedParameters[$index] = (int)$value;
                } else {
                    // Otherwise, assign as is
                    $resolvedParameters[$index] = $value;
                }
            }
        }

        return $resolvedParameters;
    }
}
