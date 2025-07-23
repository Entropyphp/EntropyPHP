<?php

declare(strict_types=1);

namespace Entropy\Invoker\ParameterResolver;

use ActiveRecord\Model;
use ReflectionNamedType;
use ReflectionFunctionAbstract;
use ActiveRecord\Exceptions\RecordNotFound;
use Invoker\ParameterResolver\ParameterResolver;

class ActiveRecordAnnotationConverter implements ParameterResolver
{
    /**
     * Nom du paramètre de la methode à injecter
     */
    private string $methodParam;
    /**
     * Other field to find Record
     */
    private array $findBy;

    public function __construct(string $methodParam, array $findBy)
    {
        $this->methodParam = $methodParam;
        $this->findBy = $findBy;
    }

    /**
     * @throws RecordNotFound
     */
    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {

        if (empty($this->findBy)) {
            return $resolvedParameters;
        }

        $reflectionParameters = $reflection->getParameters();
        // Skip parameters already resolved
        if (!empty($resolvedParameters)) {
            $reflectionParameters = array_diff_key($reflectionParameters, $resolvedParameters);
        }

        /** @todo best annotation parse */
        $findByKey = array_key_first($this->findBy);
        $include = $this->findBy['include'] ?? null;

        foreach ($providedParameters as $key => $parameter) {
            if (is_int($key)) {
                continue;
            }

            if ($key === $this->findBy[$findByKey]) {
                foreach ($reflectionParameters as $index => $reflectionParameter) {
                    $name = $reflectionParameter->getName();

                    if ($name === $this->methodParam) {
                        $parameterType = $reflectionParameter->getType();

                        if (!$parameterType) {
                            // No type
                            continue;
                        }
                        /** @var ReflectionNamedType $parameterType */
                        if ($parameterType->isBuiltin()) {
                            // Primitive types not supported
                            continue;
                        }
                        if (!$parameterType instanceof ReflectionNamedType) {
                            // Union types not supported
                            continue;
                        }

                        $class = $parameterType->getName();

                        if (class_exists($class) && in_array(Model::class, class_parents($class))) {
                            if ($findByKey === 'id') {
                                if (null === $include) {
                                    $obj = $class::find((int) $parameter);
                                } else {
                                    $include = ['include' => [$include]];
                                    $obj = $class::find((int) $parameter, $include);
                                }
                            } else {
                                $method = "find_by_" . $findByKey;
                                if (null === $include) {
                                    $obj = $class::$method($parameter);
                                } else {
                                    $include = ['include' => [$include]];
                                    $obj = $class::$method($parameter, $include);
                                }
                                if (!$obj) {
                                    throw new RecordNotFound("Couldn't find $class with $findByKey=$parameter");
                                }
                            }
                            $resolvedParameters[$index] = $obj;
                        }
                    }
                }
            }
        }
        return $resolvedParameters;
    }
}
