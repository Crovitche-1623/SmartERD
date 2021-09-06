<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AbstractEntityTest extends ApiTestCase
{
    private ValidatorInterface $validator;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        self::bootKernel();

        $container = AbstractEntityTest::getContainer();

        $this->validator = $container->get('test.validator');
    }

    public function testSlugDoesNotContainsInvalidCharacters(): void
    {
        $user = (new User)
            ->setUsername('lele')
            ->setSlug('éèàéèàéèà')
            ->setEmail('test@test.com')
            ->setPlainPassword('turlututu')
        ;

        $errors = $this->validator->validate($user);

        self::assertStringContainsString(<<<TXT
Object(App\Entity\User).slug:
    This value is not valid.
TXT, (string) $errors);
    }
}
