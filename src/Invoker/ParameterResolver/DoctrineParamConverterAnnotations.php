<?php

declare(strict_types=1);

namespace Entropy\Invoker\ParameterResolver;

use Invoker\ParameterResolver\ParameterResolver;
use Entropy\Invoker\Annotation\ParameterConverter;
use Doctrine\Persistence\ManagerRegistry;
use Entropy\Annotation\AnnotationReaderTrait;
use Entropy\Annotation\AnnotationsLoader;
use ReflectionFunctionAbstract;
use Doctrine\ORM\Mapping\Driver\RepeatableAttributeCollection;
use ReflectionMethod;

class DoctrineParamConverterAnnotations implements ParameterResolver
{
	use AnnotationReaderTrait;

	private ManagerRegistry $mg;
	private AnnotationsLoader $annotationsLoader;

	public function __construct(ManagerRegistry $mg, AnnotationsLoader $annotationsLoader)
	{
		$this->mg = $mg;
		$this->annotationsLoader = $annotationsLoader;
		$this->annotationsLoader->setAnnotation(ParameterConverter::class);
	}

	/**
	 * Récupère les paramètres en fonction des annotations
	 *
	 * @param ReflectionFunctionAbstract $reflection
	 * @param array $providedParameters
	 * @param array $resolvedParameters
	 * @return array
	 */
	public function getParameters(
		ReflectionFunctionAbstract $reflection,
		array $providedParameters,
		array $resolvedParameters
	): array {

		// Récupérer les annotations de la méthode
		/** @var  $reflection ReflectionMethod */
		$annotations = $this->annotationsLoader->getMethodAnnotations($reflection);
		if (empty($annotations)) {
			return $resolvedParameters;
		}

		// Analyser les annotations et créer les convertisseurs
		$converters = $this->parseAnnotations($annotations);

		if (empty($converters)) {
			return $resolvedParameters;
		}

		// Obtenir les paramètres de la réflexion, en excluant ceux déjà résolus
		$reflectionParameters = array_diff_key($reflection->getParameters(), $resolvedParameters);

		foreach ($converters as $converter) {
			// Résoudre les paramètres avec les convertisseurs
			$resolvedParameters = $converter->getParameters($reflection, $providedParameters, $resolvedParameters);

			// Si tous les paramètres sont résolus, on peut arrêter
			if (empty(array_diff_key($reflectionParameters, $resolvedParameters))) {
				return $resolvedParameters;
			}
		}

		return $resolvedParameters;
	}

	/**
	 * Parse les annotations pour extraire les convertisseurs
	 *
	 * @param iterable $annotations
	 * @return ParameterResolver[]
	 */
	protected function parseAnnotations(iterable $annotations): array
	{
		$converters = [];

		foreach ($annotations as $annotation) {
			// Vérification si l'annotation est de type RepeatableAttributeCollection
			$annotationCollection = $annotation instanceof RepeatableAttributeCollection
				? iterator_to_array($annotation)
				: [$annotation];

			foreach ($annotationCollection as $annot) {
				// Si l'annotation est un ParameterConverter, on crée un convertisseur
				if ($annot instanceof ParameterConverter) {
					$converters[] = new DoctrineParamConverterAnnotation(
						$this->mg,
						$annot->getName(),
						$annot->getOptions()
					);
				}
			}
		}

		return $converters;
	}
}
