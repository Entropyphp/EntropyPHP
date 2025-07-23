<?php

declare(strict_types=1);

namespace Entropy\Invoker\ParameterResolver;

use ActiveRecord\Model;
use ReflectionNamedType;
use ReflectionFunctionAbstract;
use Invoker\ParameterResolver\ParameterResolver;

/**
 * Subtitue un paramètre $id d’une route
 * par le model ActiveRecord attendu par la fonction
 */
class ActiveRecordResolver implements ParameterResolver
{
    /**
     * Nom du champ id par défaut id
     */
    private string $id = 'id';

    /**
     * Alias pour le champ $id par défaut null
     *
     * @var string|null Si non null sera utilisé à la place de $id
     */
    private ?string $alias;

    /**
     * Constructor
     *
     * @param string|null $alias
     */
    public function __construct(?string $alias = null)
    {
        $this->alias = $alias;
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

        foreach ($providedParameters as $key => $parameter) {
            if (is_int($key)) {
                continue;
            }

            $id = $this->alias ?: $this->id;

            if ($key === $id) {
                foreach ($reflectionParameters as $index => $reflectionParameter) {
                    $parameterType = $reflectionParameter->getType();

                    if (!$parameterType) {
                        // No type
                        continue;
                    }
                    /** @var ReflectionNamedType $parameterType */
                    if ($parameterType->isBuiltin()) {
                        // Primitive types are not supported
                        continue;
                    }
                    if (!$parameterType instanceof ReflectionNamedType) {
                        // Union types are not supported
                        continue;
                    }

                    $class = $parameterType->getName();

                    if (class_exists($class) && in_array(Model::class, class_parents($class))) {
                        $obj = $class::find($parameter);
                        $resolvedParameters[$index] = $obj;
                    }
                }
            }
        }
        return $resolvedParameters;
    }
}
