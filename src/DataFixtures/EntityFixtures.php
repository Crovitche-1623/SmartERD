<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\{Entity, Project};
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class EntityFixtures extends BaseFixture implements
    DependentFixtureInterface
{
    public const ADMIN_PROJECT_ENTITY_NAME = 'Recipe';
    public const USER_PROJECT_ENTITY_NAME = 'Student';
    public const ANOTHER_USER_PROJECT_ENTITY_NAME = 'Course';

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [ProjectFixtures::class];
    }

    /**
     * {@inheritDoc}
     */
    protected function loadData(ObjectManager $manager): void
    {
        /** @var  Project  $userProject */
        $userProject = $this->getSafeReference(Project::class,
            ProjectFixtures::USER_PROJECT_NAME_1.' '.UserFixtures::USER_USERNAME
        );

        foreach (self::getEntitiesOfUserProject() as $entity) {
            $entity = (new Entity)
                ->setName($entity)
                ->setProject($userProject);
            $manager->persist($entity);
            $this->addSafeReference($entity);
        }

        /** @var  Project  $adminProject */
        $adminProject = $this->getSafeReference(Project::class,
            ProjectFixtures::ADMIN_PROJECT_NAME.' '.UserFixtures::ADMIN_USERNAME
        );

        foreach (self::getEntitiesOfAdminProject() as $entity) {
            $entity = (new Entity)
                ->setName($entity)
                ->setProject($adminProject);
            $manager->persist($entity);
            $this->addSafeReference($entity);
        }

        $manager->flush();
    }

    /**
     * @return  \Generator<string>
     */
    private static function getEntitiesOfUserProject(): \Generator
    {
        yield from [
            self::USER_PROJECT_ENTITY_NAME,
            self::ANOTHER_USER_PROJECT_ENTITY_NAME,
            'Registration',
        ];
    }

    /**
     * @return  \Generator<string>
     */
    private static function getEntitiesOfAdminProject(): \Generator
    {
        yield from [self::ADMIN_PROJECT_ENTITY_NAME, 'Ingredients', 'Book'];
    }
}
