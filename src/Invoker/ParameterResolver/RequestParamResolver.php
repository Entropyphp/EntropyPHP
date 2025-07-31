<?php

declare(strict_types=1);

namespace Entropy\Invoker\ParameterResolver;

use Invoker\ParameterResolver\ParameterResolver;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

class RequestParamResolver implements ParameterResolver
{
    public function __construct(private readonly ServerRequestInterface $request)
    {
    }

    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        $reflectionParameters = $reflection->getParameters();
        // Skip parameters already resolved
        if (!empty($resolvedParameters)) {
            $reflectionParameters = array_diff_key($reflectionParameters, $resolvedParameters);
        }

        foreach ($reflectionParameters as $index => $reflectionParameter) {
            $parameterType = $reflectionParameter->getType();

            // Ignorer les paramètres sans type ou de type primitif
            if ($this->isValidType($parameterType)) {
                $class = $parameterType->getName();
                if ($class === ServerRequestInterface::class) {
                    $resolvedParameters[$index] = $this->request;
                }
            }
        }
        return $resolvedParameters;
    }

    /**
     * Vérifie si le type du paramètre est valide (non primitif et non union).
     */
    private function isValidType(?ReflectionNamedType $parameterType): bool
    {
        if (!$parameterType) {
            return false; // No type
        }

        if ($parameterType->isBuiltin()) {
            return false; // Types primitifs non supportés
        }

        return $parameterType instanceof ReflectionNamedType;
    }
}
