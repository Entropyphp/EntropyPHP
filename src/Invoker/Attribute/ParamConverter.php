<?php

declare(strict_types=1);

namespace Entropy\Invoker\Attribute;

use Attribute;
use Entropy\Invoker\Exception\InvalidAnnotation;

use function is_string;

/**
 * ParamConverter annotation.
 *
 * Marks a method as an injection point
 *
 * The first param is the method parameter to convert from route param
 * ```
 * Ex: ParamConverter("post", options={"id"="post_id"})
 * ```.
 *
 * @api
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD"})
 *
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
final class ParamConverter
{
    /**
     * Parameters indexed by the parameter number (index) or name.
     * Used if the annotation is set on a method.
     */
    private mixed $parameters;

    private ?string $name;

    private ?array $options;

    /**
     * @throws InvalidAnnotation
     */
    public function __construct(mixed $parameters = [], string $name = null, array $options = [])
    {
        $this->parameters = $parameters;
        $this->name = $parameters['value'] ?? (is_string($parameters) ? $parameters : $name);
        $this->options = $parameters['options'] ?? ([] !== $options ? $options : null);

        // Method param name
        if (null === $this->name) {
            throw new InvalidAnnotation(sprintf(
                '@ParamConverter("name", options={"id" = "value"}) expects parameter "name", %s given.',
                $name
            ));
        }
    }

    /**
     * @return array Parameters, indexed by the parameter number (index) or name
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the value of name's parameter
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the value of options
     */
    public function getOptions()
    {
        return $this->options;
    }
}
