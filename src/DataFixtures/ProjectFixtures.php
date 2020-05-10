<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\{Project, User};
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ProjectFixtures extends BaseFixture implements
    DependentFixtureInterface
{
    /**
     * Used to reach reference in other fixtures file
     */
    public const RANDOM_PROJECTS_NUMBER = 20;

    /**
     * Used to test hydra response. + 3 because there is 2 user entity and an admin.
     */
    public const TOTAL_PROJECTS_NUMBER = self::RANDOM_PROJECTS_NUMBER + 3;
    public const ADMIN_PROJECT_NAME = 'A simple admin project for testing purpose';
    public const USER_PROJECT_NAME = 'A simple user project for testing purpose';

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
        /**
         * @var  User  $admin
         */
        $admin = $this->getReference('User_admin');
        $manager->persist(
            (new Project)
                ->setTitle(self::ADMIN_PROJECT_NAME)
                ->setUser($admin)
        );
        unset($admin);

        /**
         * @var  User  $user
         */
        $user = $this->getReference('User_user');
        $manager->persist(
            (new Project)
                ->setTitle(self::USER_PROJECT_NAME)
                ->setUser($user)
        );
        $manager->persist(
            (new Project)
                ->setTitle('A second user project for testing purpose')
                ->setUser($user)
        );
        unset($user);

        $this->createMany(
            Project::class,
            self::RANDOM_PROJECTS_NUMBER,
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
            unset($userNumber);

            $project
                ->setTitle($this->faker->company)
                ->setUser($creator)
            ;
        });

        $manager->flush();
    }
}