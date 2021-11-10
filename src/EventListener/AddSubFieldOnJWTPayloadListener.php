<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\Security\Core\Security;

final class AddSubFieldOnJWTPayloadListener
{
    public function __construct(private Security $security)
    {}

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        /** @var  User  $user */
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        $payload = $event->getData();
        $payload['sub'] = $user->getSlug();
        $event->setData($payload);
    }
}
