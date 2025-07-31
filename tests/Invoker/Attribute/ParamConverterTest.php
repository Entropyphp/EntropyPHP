<?php

declare(strict_types=1);

namespace Entropy\Tests\Invoker\Attribute;

use PHPUnit\Framework\TestCase;
use Entropy\Invoker\Attribute\ParamConverter;
use Entropy\Invoker\Exception\InvalidAnnotation;

class ParamConverterTest extends TestCase
{
    public function testConstructWithStringParameter(): void
    {
        $converter = new ParamConverter('post');

        $this->assertEquals('post', $converter->getName());
        $this->assertNull($converter->getOptions());
    }

    /**
     * @throws InvalidAnnotation
     */
    public function testConstructWithArrayParameter(): void
    {
        $converter = new ParamConverter([
            'value' => 'post',
            'options' => ['id' => 'post_id']
        ]);

        $this->assertEquals('post', $converter->getName());
        $this->assertEquals(['id' => 'post_id'], $converter->getOptions());
    }

    /**
     * @throws InvalidAnnotation
     */
    public function testConstructWithNameAndOptions(): void
    {
        $converter = new ParamConverter(
            name: 'post',
            options: ['id' => 'post_id']
        );

        $this->assertEquals('post', $converter->getName());
        $this->assertEquals(['id' => 'post_id'], $converter->getOptions());
    }

    /**
     * @throws InvalidAnnotation
     */
    public function testConstructWithEmptyOptions(): void
    {
        $converter = new ParamConverter('post', options: []);

        $this->assertEquals('post', $converter->getName());
        $this->assertNull($converter->getOptions());
    }

    public function testConstructThrowsExceptionWhenNameIsNull(): void
    {
        $this->expectException(InvalidAnnotation::class);
        $this->expectExceptionMessage(
            '@ParamConverter("name", options={"id" = "value"}) expects parameter "name",  given.'
        );

        new ParamConverter();
    }

    /**
     * @throws InvalidAnnotation
     */
    public function testGetParameters(): void
    {
        $parameters = ['value' => 'post', 'options' => ['id' => 'post_id']];
        $converter = new ParamConverter($parameters);

        $this->assertEquals($parameters, $converter->getParameters());
    }

    /**
     * @throws InvalidAnnotation
     */
    public function testConstructWithMultipleParameters(): void
    {
        $converter = new ParamConverter(
            parameters: ['value' => 'post', 'extra' => 'data'],
            options: ['id' => 'post_id']
        );

        $this->assertEquals('post', $converter->getName());
        $this->assertEquals(['id' => 'post_id'], $converter->getOptions());
        $this->assertEquals(['value' => 'post', 'extra' => 'data'], $converter->getParameters());
    }
}
