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
        $uniquePart = ProjectFixtures::USER_PROJECT_NAME_1.' '.UserFixtures::USER_USERNAME;
        /**
         * @var  Project  $userProject
         */
        $userProject = $this->getSafeReference(Project::class, $uniquePart);
        unset($uniquePart);

        $entities = self::getEntitiesOfUserProject();
        $total = count($entities);
        for ($i = 0; $i < $total; ++$i) {
            $entity = (new Entity)
                ->setName($entities[$i])
                ->setProject($userProject);
            $manager->persist($entity);
            $this->addSafeReference($entity);
        }
        unset($total, $entities, $userProject);

        $uniquePart = ProjectFixtures::ADMIN_PROJECT_NAME.' '.UserFixtures::ADMIN_USERNAME;
        /**
         * @var  Project  $adminProject
         */
        $adminProject = $this->getSafeReference(Project::class, $uniquePart);
        unset($uniquePart);

        $entities = self::getEntitiesOfAdminProject();
        $total = count($entities);
        for ($i = 0; $i < $total; ++$i) {
            $entity = (new Entity)
                ->setName($entities[$i])
                ->setProject($adminProject);
            $manager->persist($entity);
            $this->addSafeReference($entity);
        }
        unset($total, $entities, $adminProject);

        $manager->flush();
    }

    private static function getEntitiesOfUserProject(): array
    {
        return [self::USER_PROJECT_ENTITY_NAME, 'Course', 'Registration'];
    }

    private static function getEntitiesOfAdminProject(): array
    {
        return [self::ADMIN_PROJECT_ENTITY_NAME, 'Ingredients', 'Book'];
    }
}
