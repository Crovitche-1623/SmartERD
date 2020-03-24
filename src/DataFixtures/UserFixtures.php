<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends BaseFixture
{
    private const DEFAULT_USER_PASSWORD = 'SmartERD';

    /**
     * {@inheritDoc}
     */
    public function loadData(ObjectManager $manager): void
    {
        $manager->persist(
            (new User())
                ->setUsername('admin')
                ->setEmail('admin@smarterd.io')
                ->setPlainPassword(self::DEFAULT_USER_PASSWORD)
                ->setIsAdmin(true)
        );

        $this->createMany(User::class, 100, function (User $u) {
            $u
                ->setUsername($this->faker->unique()->userName)
                ->setEmail($this->faker->unique()->companyEmail)
                ->setPlainPassword(self::DEFAULT_USER_PASSWORD)
            ;
        });

        $manager->flush();
    }
}