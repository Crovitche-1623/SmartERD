<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\{Attribute, Entity};
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class AttributeFixtures extends BaseFixture implements
    DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [EntityFixtures::class];
    }

    /**
     * {@inheritDoc}
     */
    protected function loadData(ObjectManager $manager): void
    {
        /**
         * @var  Entity  $entity
         */
        $entity = $this->getSafeReference(
            className: Entity::class,
            uniquePart:  EntityFixtures::USER_PROJECT_ENTITY_NAME . ' ' . ProjectFixtures::USER_PROJECT_NAME_1
        );

        $manager->persist(
            (new Attribute)
                ->setName('fullName')
                ->setEntity($entity)
        );

        $manager->persist(
            (new Attribute)
                ->setName('birthDate')
                ->setEntity($entity)
        );

        $manager->flush();
    }
}
