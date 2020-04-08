<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\{Project, User};
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ProjectFixtures extends BaseFixture implements
    DependentFixtureInterface
{
    public const PROJECTS_NUMBER = 20;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    /**
     * {@inheritDoc}
     */
    public function loadData(ObjectManager $manager): void
    {
        $this->createMany(
            Project::class,
            self::PROJECTS_NUMBER,
            function (Project $project) {

            // That's not the ID ! It's the number used in fixture !
            $userNumber = $this->faker->numberBetween(
                1,
                UserFixtures::USERS_NUMBER
            );

            /**
             * @var  User  $creator
             */
            $creator = $this->getReference('App\\Entity\\User_' . $userNumber);

            $project
                ->setTitle($this->faker->company)
                ->setUser($creator)
            ;
        });

        $manager->flush();
    }
}