<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Benchmark;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\ValueDefinition;
use Yiisoft\Definitions\DefinitionStorage;

#[BeforeMethods('setUp')]
final class DefinitionStorageBench
{
    private DefinitionStorage $storage;

    public function setUp(): void
    {
        $this->storage = new DefinitionStorage();
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchSetAndGet(): void
    {
        $this->storage->set('test', new ValueDefinition('value'));
        $this->storage->get('test');
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchHas(): void
    {
        $this->storage->set('test', new ValueDefinition('value'));
        $this->storage->has('test');
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchSetMultipleDefinitions(): void
    {
        $definitions = [
            'test1' => new ValueDefinition('value1'),
            'test2' => new ValueDefinition('value2'),
            'test3' => new ValueDefinition('value3'),
        ];
        $storage = new DefinitionStorage($definitions);
        $storage->has('test1');
        $storage->has('test2');
        $storage->has('test3');
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchGetBuildStack(): void
    {
        $this->storage->set('test1', new ValueDefinition('value1'));
        $this->storage->set('test2', ArrayDefinition::fromConfig([
            'class' => 'TestClass',
            '__construct()' => [
                'test1',
            ],
        ]));
        $this->storage->has('test2');
        $this->storage->getBuildStack();
    }
}
