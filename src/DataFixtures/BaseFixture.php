<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\{Factory, Generator};
use InvalidArgumentException;
use ReflectionClass, ReflectionException;

abstract class BaseFixture extends Fixture
{
    private ObjectManager $manager;
    protected Generator $faker;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->faker = Factory::create('fr_CH');

        $this->loadData($manager);
    }

    /**
     * This method is called by the mandatory "load" method but the load method
     * include faker before.
     *
     * @param  ObjectManager  $manager
     */
    abstract protected function loadData(ObjectManager $manager): void;

    protected function createMany(
        string $className,
        int $count,
        callable $factory
    ): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $entity = new $className;
            $factory($entity, $i);
            $this->manager->persist($entity);

            // store for usage later as App\Entity\ClassName_#COUNT#
            $this->addReference($className . '_' . $i, $entity);
        }
    }

    /**
     * To have unique reference and to avoid collisions, each reference is
     * prefixed by the name of his class. To be sure there will be no
     * collisions, the "__toString()" method MUST return something unique (which
     * identify the data). The goal of ths function is to get reference by
     * giving a simple unique which identify the data.
     *
     * @param  object  $entity
     * @throws  InvalidArgumentException  In the case where entity would not
     *                                    have implemented the "__toString()"
     *                                    method.
     * @throws  ReflectionException  In the case where a bad parameter would be
     *                               sent to the ReflectionClass
     */
    public function addSafeReference(object $entity): void
    {
        $function = new ReflectionClass(get_class($entity));

        // Return an error if the entity class doesn't have an unique toString()
        // method
        if (!$function->hasMethod('__toString')) {
            throw new InvalidArgumentException(
                sprintf(
                    "The entity %s must implement a __toString() method.",
                    get_class($entity)
                )
            );
        }

        // TO DEBUG, UNCOMMENT THE FOLLOWING LINE :
        // print_r($function->getShortName() . '_' . strval($entity) . PHP_EOL);

        parent::addReference(
            $function->getShortName() . '_' . strval($entity),
            $entity
        );
    }
}
