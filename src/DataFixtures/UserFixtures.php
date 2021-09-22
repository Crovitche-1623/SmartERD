<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends BaseFixture
{
    public const DEFAULT_USER_PASSWORD = '5m4rt3RD_P4$$w0rd';
    public const USER_USERNAME = 'user1';
    public const ANOTHER_USER_USERNAME = 'user2';
    public const ADMIN_USERNAME = 'admin';
    public const USERS_NUMBER = 100;

    /**
     * {@inheritDoc}
     */
    public function loadData(ObjectManager $manager): void
    {
        $admin = (new User)
            ->setUsername('admin')
            ->setEmail('admin@smarterd.io')
            ->setPlainPassword(self::DEFAULT_USER_PASSWORD)
            ->setIsAdmin(true)
        ;
        $manager->persist($admin);
        $this->addSafeReference($admin);
        unset($admin);

        $user = (new User)
            ->setUsername(self::USER_USERNAME)
            ->setEmail('user1@smarterd.io')
            ->setPlainPassword(self::DEFAULT_USER_PASSWORD)
        ;
        $manager->persist($user);
        $this->addSafeReference($user);
        unset($user);

        $anotherUser = (new User)
            ->setUsername(self::ANOTHER_USER_USERNAME)
            ->setEmail('user2@smarterd.io')
            ->setPlainPassword(self::DEFAULT_USER_PASSWORD)
        ;
        $manager->persist($anotherUser);
        $this->addSafeReference($anotherUser);
        unset($anotherUser);

        $this->createMany(User::class, self::USERS_NUMBER, function (User $u) {
            $u
                ->setUsername($this->faker->unique()->userName())
                ->setEmail($this->faker->unique()->companyEmail())
                ->setPlainPassword(self::DEFAULT_USER_PASSWORD)
            ;
        });

        $manager->flush();
    }
}
