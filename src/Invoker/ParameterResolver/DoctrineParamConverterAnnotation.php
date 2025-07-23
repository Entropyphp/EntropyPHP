<?php

declare(strict_types=1);

namespace Entropy\Invoker\ParameterResolver;

use ActiveRecord\Exceptions\RecordNotFound;
use Doctrine\Persistence\ManagerRegistry;
use Invoker\ParameterResolver\ParameterResolver;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

class DoctrineParamConverterAnnotation implements ParameterResolver
{
	private string $methodParam;
	private array $findBy;
	private ManagerRegistry $mg;

	public function __construct(ManagerRegistry $mg, string $methodParam, array $findBy)
	{
		$this->mg = $mg;
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
		// Si findBy est vide, on retourne directement les paramètres résolus
		if (empty($this->findBy)) {
			return $resolvedParameters;
		}

		$reflectionParameters = $reflection->getParameters();
		// Supprime les paramètres déjà résolus de la réflexion
		$reflectionParameters = array_diff_key($reflectionParameters, $resolvedParameters);

		// On récupère la première clé de findBy
		$findByKey = array_key_first($this->findBy);

		foreach ($providedParameters as $key => $parameter) {
			if (is_int($key)) {
				continue; // Ignore les paramètres par position (index)
			}

			// Si le paramètre correspond à la clé de findBy, on traite le paramètre
			if ($key === $this->findBy[$findByKey]) {
				foreach ($reflectionParameters as $index => $reflectionParameter) {
					$name = $reflectionParameter->getName();

					// Si c'est le paramètre que nous cherchons à résoudre
					if ($name === $this->methodParam) {
						$parameterType = $reflectionParameter->getType();

						// Si aucun type n'est spécifié ou si le type est primitif, on ignore
						if ($this->isValidType($parameterType)) {
							$class = $parameterType->getName();
							$obj = $this->findEntity($class, $parameter, $findByKey);

							if ($obj) {
								$resolvedParameters[$index] = $obj;
							}
						}
					}
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
     * Trouve l'entité correspondante dans le repository.
     * @throws RecordNotFound
     */
	private function findEntity(string $class, $parameter, string $findByKey): null|object|string
	{
		$em = $this->mg->getManagerForClass($class);
		if ($em === null) {
			return null; // Pas de gestionnaire pour cette classe
		}

		$repo = $em->getRepository($class);
		if ($findByKey === 'id') {
			$entity = $repo->find((int)$parameter);
		} else {
            $entity = $repo->findOneBy([$findByKey => $parameter]);
        }

        if (!$entity) {
            throw new RecordNotFound("Couldn't find $class with $findByKey=$parameter");
        }
        return $entity;
    }
}
