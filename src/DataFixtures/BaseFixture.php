<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\{Factory,Generator};
use InvalidArgumentException;
use ReflectionClass,ReflectionException;

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

    abstract protected function loadData(ObjectManager $manager): void;

    protected function createMany(
        string $className,
        int $count,
        callable $factory
    ): void
    {
        for ($i = 0; $i < $count; $i++) {
            $entity = new $className();
            $factory($entity, $i);
            $this->manager->persist($entity);
            // store for usage later as App\Entity\ClassName_#COUNT#
            $this->addSafeReference($entity);
        }
    }

    /**
     * Afin d'avoir des références uniques et éviter des collisions, chaque référence est préfixé par le nom de la
     * classe qu'il possède. Pour s'assurer qu'il y aura jamais aucune collisions, la méthode "__toString()" doit
     * obligatoirement retourné quelque chose d'unique (ce qui caractérise la donnée).
     * Le but de cette fonction est d'ensuite pouvoir récupérer des références simplement en donnant simplement la
     * donnée qui les définit.
     *
     * @param  object  $entity
     * @throws  InvalidArgumentException  Dans le cas où l'entité
     *                                    n'implémenterait pas la méthode
     *                                    "__toString()".
     * @throws  ReflectionException  Dans le cas où un mauvais paramètre serait envoyé à la
     *                               la ReflectionClass
     */
    public function addSafeReference(object $entity): void
    {
        $function = new ReflectionClass(get_class($entity));

        // Return an error if the entity class doesn't have an unique toString()
        // method.
        if (!$function->hasMethod('__toString')) {
            throw new InvalidArgumentException(
                sprintf(
                    "The entity %s must implement a __toString() method.",
                    get_class($entity)
                )
            );
        }

        /**
         * TO DEBUG, UNCOMMENT THE FOLLOWING LINE :
         * print_r($function->getShortName() . '_' . strval($object) . PHP_EOL);
         */

        parent::addReference(
            $function->getShortName() . '_' . strval($entity),
            $entity
        );
    }
}
