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
use Yiisoft\Definitions\Tests\Support\Bike;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\CarFactory;
use Yiisoft\Definitions\Tests\Support\Chair;
use Yiisoft\Definitions\Tests\Support\ColorInterface;
use Yiisoft\Definitions\Tests\Support\ColorPink;
use Yiisoft\Definitions\Tests\Support\EngineInterface;
use Yiisoft\Definitions\Tests\Support\EngineMarkOne;
use Yiisoft\Definitions\Tests\Support\EngineMarkTwo;
use Yiisoft\Definitions\Tests\Support\GearBox;
use Yiisoft\Definitions\Tests\Support\Mechanism;
use Yiisoft\Definitions\Tests\Support\Mouse;
use Yiisoft\Definitions\Tests\Support\Notebook;
use Yiisoft\Definitions\Tests\Support\Phone;
use Yiisoft\Definitions\Tests\Support\Recorder;
use Yiisoft\Definitions\Tests\Support\RedChair;
use Yiisoft\Definitions\Tests\Support\Table;
use Yiisoft\Definitions\Tests\Support\Tree;

/**
 * @Iterations(5)
 * @Revs(1000)
 * @Groups({"normalizer"})
 * @BeforeMethods({"before"})
 */
final class NormalizerBench
{
    private const DEFINITION_COUNT = 120;

    /**
     * @var class-string[]
     */
    private const CLASS_DEFINITIONS = [
        Bike::class,
        Car::class,
        Chair::class,
        ColorPink::class,
        EngineMarkOne::class,
        EngineMarkTwo::class,
        GearBox::class,
        Mechanism::class,
        Mouse::class,
        Notebook::class,
        Phone::class,
        Recorder::class,
        RedChair::class,
        Table::class,
        Tree::class,
    ];

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

    /**
     * @var list<array{0:mixed,1:class-string|null}>
     */
    private array $mixedDefinitions = [];

    public function before(): void
    {
        $this->classDefinitions = [];
        $this->referenceDefinitions = [];
        $this->arrayDefinitions = [];
        $this->callableDefinitions = [];
        $this->objectDefinitions = [];
        $this->mixedDefinitions = [];

        for ($i = 0; $i < self::DEFINITION_COUNT; $i++) {
            $this->classDefinitions[] = self::CLASS_DEFINITIONS[$i % count(self::CLASS_DEFINITIONS)];
            $this->referenceDefinitions[] = "service.$i";

            $arrayDefinition = [
                'class' => Car::class,
                '__construct()' => [Reference::to(EngineInterface::class)],
                'setColor()' => [Reference::to(ColorInterface::class)],
            ];
            $this->arrayDefinitions[] = $arrayDefinition;
            $this->callableDefinitions[] = CarFactory::create(...);
            $objectDefinition = new stdClass();
            $this->objectDefinitions[] = $objectDefinition;

            $type = $i % 6;
            if ($type === 0) {
                $this->mixedDefinitions[] = [$this->classDefinitions[$i], null];
            } elseif ($type === 1) {
                $this->mixedDefinitions[] = [$this->referenceDefinitions[$i], null];
            } elseif ($type === 2) {
                $this->mixedDefinitions[] = [$arrayDefinition, null];
            } elseif ($type === 3) {
                $this->mixedDefinitions[] = [[
                    '__construct()' => ['Yii Phone', '3.0'],
                    '$dev' => true,
                    'addApp()' => ['Browser', '7'],
                ], Phone::class];
            } elseif ($type === 4) {
                $this->mixedDefinitions[] = [CarFactory::create(...), null];
            } else {
                $this->mixedDefinitions[] = [$objectDefinition, null];
            }
        }

        $this->clearNormalizerCache();
        $this->warmUpNormalizer($this->classDefinitions);
        $this->warmUpNormalizer($this->referenceDefinitions);
        $this->warmUpNormalizer($this->objectDefinitions);
        $this->normalizeMixedDefinitions();
    }

    /**
     * @Groups({"class", "warm"})
     */
    public function benchWarmClassDefinitions(): void
    {
        foreach ($this->classDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"class", "cold"})
     */
    public function benchColdClassDefinitions(): void
    {
        $this->clearNormalizerCache();

        foreach ($this->classDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"reference", "warm"})
     */
    public function benchWarmReferenceDefinitions(): void
    {
        foreach ($this->referenceDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"reference", "cold"})
     */
    public function benchColdReferenceDefinitions(): void
    {
        $this->clearNormalizerCache();

        foreach ($this->referenceDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"definition", "cold", "typical"})
     */
    public function benchArrayDefinitions(): void
    {
        $this->clearNormalizerCache();

        foreach ($this->arrayDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"factory", "cold", "typical"})
     */
    public function benchCallableDefinitions(): void
    {
        $this->clearNormalizerCache();

        foreach ($this->callableDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"value", "warm"})
     */
    public function benchWarmObjectDefinitions(): void
    {
        foreach ($this->objectDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"value", "cold"})
     */
    public function benchColdObjectDefinitions(): void
    {
        $this->clearNormalizerCache();

        foreach ($this->objectDefinitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    /**
     * @Groups({"definition", "cold", "typical"})
     */
    public function benchArrayDefinitionsWithInferredClass(): void
    {
        $this->clearNormalizerCache();

        foreach ($this->arrayDefinitions as $definition) {
            unset($definition['class']);
            Normalizer::normalize($definition, Car::class);
        }
    }

    /**
     * @Groups({"mixed", "cold", "typical"})
     */
    public function benchMixedApplicationDefinitionsCold(): void
    {
        $this->clearNormalizerCache();
        $this->normalizeMixedDefinitions();
    }

    /**
     * @Groups({"mixed", "warm", "typical"})
     */
    public function benchMixedApplicationDefinitionsWarm(): void
    {
        $this->normalizeMixedDefinitions();
    }

    /**
     * @param iterable<mixed> $definitions
     */
    private function warmUpNormalizer(iterable $definitions): void
    {
        foreach ($definitions as $definition) {
            Normalizer::normalize($definition);
        }
    }

    private function normalizeMixedDefinitions(): void
    {
        foreach ($this->mixedDefinitions as [$definition, $class]) {
            Normalizer::normalize($definition, $class);
        }
    }

    private function clearNormalizerCache(): void
    {
        if (method_exists(Normalizer::class, 'clearCache')) {
            Normalizer::clearCache();
        }
    }
}
