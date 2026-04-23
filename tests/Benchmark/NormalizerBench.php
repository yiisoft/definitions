<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Benchmark;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use stdClass;
use Yiisoft\Definitions\Helpers\Normalizer;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\CarFactory;
use Yiisoft\Definitions\Tests\Support\ColorInterface;
use Yiisoft\Definitions\Tests\Support\ColorPink;
use Yiisoft\Definitions\Tests\Support\EngineInterface;
use Yiisoft\Definitions\Tests\Support\EngineMarkOne;

/**
 * @Iterations(5)
 * @Revs(1000)
 * @Groups({"normalizer"})
 * @BeforeMethods({"before"})
 */
final class NormalizerBench
{
    private const DEFINITION_COUNT = 200;

    /**
     * @var class-string[]
     */
    private array $classDefinitions = [];

    /**
     * @var string[]
     */
    private array $referenceDefinitions = [];

    /**
     * @var array[]
     */
    private array $arrayDefinitions = [];

    /**
     * @var callable[]
     */
    private array $callableDefinitions = [];

    /**
     * @var object[]
     */
    private array $objectDefinitions = [];

    public function before(): void
    {
        $this->classDefinitions = [];
        $this->referenceDefinitions = [];
        $this->arrayDefinitions = [];
        $this->callableDefinitions = [];
        $this->objectDefinitions = [];

        for ($i = 0; $i < self::DEFINITION_COUNT; $i++) {
            $this->classDefinitions[] = ColorPink::class;
            $this->referenceDefinitions[] = 'engine';
            $this->arrayDefinitions[] = [
                'class' => Car::class,
                '__construct()' => [Reference::to(EngineInterface::class)],
                'setColor()' => [Reference::to(ColorInterface::class)],
            ];
            $this->callableDefinitions[] = [CarFactory::class, 'create'];
            $this->objectDefinitions[] = new stdClass();
        }
    }

    /**
     * @Groups({"class"})
     */
    public function benchClassDefinitions(): void
    {
        foreach ($this->classDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"reference"})
     */
    public function benchReferenceDefinitions(): void
    {
        foreach ($this->referenceDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"definition", "typical"})
     */
    public function benchArrayDefinitions(): void
    {
        foreach ($this->arrayDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"factory", "typical"})
     */
    public function benchCallableDefinitions(): void
    {
        foreach ($this->callableDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"value"})
     */
    public function benchObjectDefinitions(): void
    {
        foreach ($this->objectDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"definition", "typical"})
     */
    public function benchArrayDefinitionsWithInferredClass(): void
    {
        foreach ($this->arrayDefinitions as $definition) {
            unset($definition['class']);
            Normalizer::normalize($definition, Car::class);
        }
    }

    /**
     * @Groups({"class"})
     */
    public function benchSameClassDefinitions(): void
    {
        foreach ($this->classDefinitions as $_definition) {
            Normalizer::normalize(EngineMarkOne::class, EngineMarkOne::class);
        }
    }
}
