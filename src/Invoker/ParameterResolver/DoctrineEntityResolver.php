<?php

declare(strict_types=1);

namespace Entropy\Invoker\ParameterResolver;

use ActiveRecord\Exceptions\RecordNotFound;
use Doctrine\Persistence\ManagerRegistry;
use Invoker\ParameterResolver\ParameterResolver;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

class DoctrineEntityResolver implements ParameterResolver
{
	private string $id = 'id';
	private ?string $alias;
	private ManagerRegistry $mg;

	/**
	 * Constructor
	 *
	 * @param ManagerRegistry $mg
	 * @param string|null $alias
	 */
	public function __construct(ManagerRegistry $mg, ?string $alias = null)
	{
		$this->mg = $mg;
		$this->alias = $alias;
	}

	/**
	 * @throws RecordNotFound
	 */
	public function getParameters(
		ReflectionFunctionAbstract $reflection,
		array $providedParameters,
		array $resolvedParameters
	): array {
		$reflectionParameters = $reflection->getParameters();
		// Exclure les paramètres déjà résolus
		$reflectionParameters = array_diff_key($reflectionParameters, $resolvedParameters);

		// Vérification de l'alias ou du champ par défaut (id)
		$id = $this->alias ?? $this->id;

		// Itérer sur les paramètres fournis pour trouver celui qui correspond à l'id
		foreach ($providedParameters as $key => $parameter) {
			if (!is_int($key) && $key === $id) {
				$resolvedParameters = $this->resolveEntityParameter(
                    $reflectionParameters,
                    $parameter,
                    $resolvedParameters
                );
			}
		}

		return $resolvedParameters;
	}

	/**
	 * Résoudre le paramètre d'entité
	 * @throws RecordNotFound
	 */
	private function resolveEntityParameter(
		array $reflectionParameters,
			  $parameter,
		array $resolvedParameters
	): array {
		// Vérification des types des paramètres et de leur résolution
		foreach ($reflectionParameters as $index => $reflectionParameter) {
			$parameterType = $reflectionParameter->getType();

			// Ignorer les paramètres sans type ou de type primitif
			if ($this->isValidType($parameterType)) {
				$class = $parameterType->getName();
				$entity = $this->findEntity($class, $parameter);

				if ($entity) {
					$resolvedParameters[$index] = $entity;
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
			return false; // Pas de type
		}

		if ($parameterType->isBuiltin()) {
			return false; // Types primitifs non supportés
		}

		return $parameterType instanceof ReflectionNamedType;
	}

    /**
     * Trouver l'entité en utilisant le repository de Doctrine
     * @throws RecordNotFound
     */
	private function findEntity(string $class, $parameter): null|object|string
	{
		$em = $this->mg->getManagerForClass($class);
		if ($em === null) {
			return null; // Pas de gestionnaire pour cette classe
		}

		$repo = $em->getRepository($class);
        $entity = $repo->find($parameter);
        if (!$entity) {
            throw new RecordNotFound(sprintf('Could\'t find %s with id= %s',$class, $parameter));
        }
		return $entity;
	}
}
