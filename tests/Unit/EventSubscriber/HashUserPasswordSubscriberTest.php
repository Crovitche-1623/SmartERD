<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\{DataFixtures\UserFixtures, Entity\User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class HashUserPasswordSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->em = self::getContainer()->get('doctrine')->getManager();

        parent::setUp();
    }

    public function testPrePersist(): void
    {
        $user = (new User)
            ->setUsername('test_pre_persist')
            ->setEmail('test_pre_persist@smarterd.io')
            ->setPlainPassword(UserFixtures::DEFAULT_USER_PASSWORD);

        $this->em->persist($user);

        // User has been persisted so the hashed password should be set.
        $this->assertNotNull($user->getPassword());
        $this->assertNotEmpty($user->getPassword());
        // The plainPassword should not be saved !
        $this->assertNull($user->getPlainPassword());
    }

    public function testPreUpdate(): void
    {
        // 1. Create a fresh new user
        $user = (new User)
            ->setUsername('test_pre_update')
            ->setEmail('test_pre_update@smarterd.io')
            ->setPlainPassword(UserFixtures::DEFAULT_USER_PASSWORD);

        $this->em->persist($user);
        $this->em->flush();

        // 2. Obtain the user and update it
        // 2.1 Set the hashed password to a blank string for the test
        $obtainedUser = $this->em
            ->getRepository(User::class)
            ->findOneBy(['username' => $user->getUsername()]);
        unset($user);

        $obtainedUser->setHashedPassword('');
        $obtainedUser->setPlainPassword('I did an update !');

        // 3. Apply changes
        $this->em->persist($obtainedUser);
        $this->em->flush();

        // 4. Get the updated user
        $updatedUser = $this->em
            ->getRepository(User::class)
            ->find($obtainedUser->getId());
        unset($obtainedUser);

        $this->assertNotNull($updatedUser->getPassword());
        $this->assertNotEmpty($updatedUser->getPassword());

        // The plain password should not be saved !
        $this->assertNull($updatedUser->getPlainPassword());
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->em->close();
        unset($this->em);
    }
}
