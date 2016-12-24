<?php
/**
 * components
 *
 * @author    Wolfy-J
 */
namespace Spiral\Tests\ODM\Traits;

use Mockery as m;
use Spiral\Core\Container;
use Spiral\Core\MemoryInterface;
use Spiral\Models\Reflections\ReflectionEntity;
use Spiral\ODM\Configs\MutatorsConfig;
use Spiral\ODM\MongoManager;
use Spiral\ODM\ODM;
use Spiral\ODM\Schemas\DocumentSchema;
use Spiral\ODM\Schemas\SchemaBuilder;
use Spiral\ODM\Schemas\SchemaLocator;

trait ODMTrait
{
    /**
     * @return SchemaBuilder
     */
    protected function makeBuilder()
    {
        return new SchemaBuilder(m::mock(MongoManager::class));
    }

    /**
     * @param string $class
     *
     * @return DocumentSchema
     */
    protected function makeSchema(string $class): DocumentSchema
    {
        return new DocumentSchema(new ReflectionEntity($class), $this->mutatorsConfig());
    }

    /**
     * @param MongoManager|null $manager
     *
     * @return ODM
     */
    protected function makeODM(MongoManager $manager = null, SchemaLocator $locator = null)
    {
        $memory = m::mock(MemoryInterface::class);
        $memory->shouldReceive('loadData')->with(ODM::MEMORY)->andReturn([]);

        return new ODM(
            $manager ?? m::mock(MongoManager::class),
            $locator ?? m::mock(SchemaLocator::class),
            $memory,
            new Container()
        );
    }

    /**
     * @return MutatorsConfig
     */
    protected function mutatorsConfig()
    {
        return new MutatorsConfig([
            /*
            * Set of mutators to be applied for specific field types.
            */
            'mutators' => [
                'int'     => ['setter' => 'intval'],
                'float'   => ['setter' => 'floatval'],
                'string'  => ['setter' => 'strval'],
                'bool'    => ['setter' => 'boolval'],

                //Automatic casting of mongoID
                'MongoId' => ['setter' => [MongoManager::class, 'mongoID']],

                //'array'     => ['accessor' => ScalarArray::class],
                //'MongoDate' => ['accessor' => Accessors\MongoTimestamp::class],
                //'timestamp' => ['accessor' => Accessors\MongoTimestamp::class],
                /*{{mutators}}*/
            ],
            /*
             * Mutator aliases can be used to declare custom getter and setter filter methods.
             */
            'aliases'  => [
                'integer' => 'int',
                'long'    => 'int',
                'text'    => 'string',

                /*{{mutators.aliases}}*/
            ]
        ]);
    }
}