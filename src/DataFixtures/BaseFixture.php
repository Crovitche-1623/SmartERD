<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\UniqueStringableInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\{Factory, Generator};
use InvalidArgumentException, ReflectionClass, ReflectionException;

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
            $this->addReference($className.'_'.$i, $entity);
        }
    }

    /**
     * To have unique reference and to avoid collisions, each reference is
     * prefixed by the name of his class. To be sure there will be no
     * collisions, a "toUniqueString" method MUST return something unique (which
     * identify the data). The goal of ths function is to get reference by
     * giving a simple unique which identify the data.
     *
     * @param  UniqueStringableInterface  $entity
     *
     * @throws  ReflectionException In the case where a bad parameter would be
     *                              sent to the ReflectionClass
     */
    public function addSafeReference(UniqueStringableInterface $entity): void
    {
        $function = new ReflectionClass(get_class($entity));

        // TO DEBUG, UNCOMMENT THE FOLLOWING LINE :
        // print_r($function->getShortName().'_'.$entity->toUniqueString().PHP_EOL);

        $this->addReference(
            $function->getShortName() . '_' . $entity->toUniqueString(),
            $entity
        );
    }

    /**
     * A simpler way to get safe references.
     *
     * @param  string  $className  The name of the class
     * @param  string  $uniquePart The unique part initially used to create the
     *                             safe reference. This part has normally been
     *                             created with "toUniqueString()" method.
     * @return  object  The entity (reference) you are searching.
     *
     * @throws  ReflectionException  In the case where a bad parameter would be
     *                               sent to the ReflectionClass
     * @throws  InvalidArgumentException  In the case where the class doesn't
     *                                    implement the
     *                                    UniqueStringableInterface
     */
    public function getSafeReference(
        string $className,
        string $uniquePart
    ): object
    {
        $entity = new $className;
        if (!$entity instanceof UniqueStringableInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    "The entity %s must implement the %s.",
                    get_class($entity),
                    UniqueStringableInterface::class
                )
            );
        }
        $function = new ReflectionClass(get_class($entity));
        unset($entity);

        // TO DEBUG, UNCOMMENT THE FOLLOWING LINE :
        // print_r($function->getShortName().'_'.$entity->toUniqueString().PHP_EOL);

        return $this->getReference(
            $function->getShortName() . '_' . $uniquePart
        );
    }
}
