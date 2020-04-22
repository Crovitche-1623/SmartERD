<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends BaseFixture
{
    public const DEFAULT_USER_PASSWORD = '5m4rt3RD_P4$$w0rd';
    public const USERS_NUMBER = 100;

    /**
     * {@inheritDoc}
     */
    public function loadData(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setUsername('admin')
            ->setEmail('admin@smarterd.io')
            ->setPlainPassword(self::DEFAULT_USER_PASSWORD)
            ->setIsAdmin(true)
        ;

        $manager->persist($admin);

        $this->addSafeReference($admin);

        $manager->persist(
            (new User())
                ->setUsername('user')
                ->setEmail('user@smarterd.io')
                ->setPlainPassword(self::DEFAULT_USER_PASSWORD)
        );

        $this->createMany(User::class, self::USERS_NUMBER, function (User $u) {
            $u
                ->setUsername($this->faker->unique()->userName)
                ->setEmail($this->faker->unique()->companyEmail)
                ->setPlainPassword(self::DEFAULT_USER_PASSWORD)
            ;
        });

        $manager->flush();
    }
}