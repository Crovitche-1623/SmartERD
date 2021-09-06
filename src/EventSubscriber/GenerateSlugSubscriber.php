<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AbstractEntity;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\{Event\LifecycleEventArgs, Events};
use Hidehalo\Nanoid\Client;

/**
 * The goal of this listener is to generate a slug if the case the field is
 * empty. To do this, we check if the class have the slug trait, and if it's
 * true, we check if the field is null.
 */
final class GenerateSlugSubscriber implements EventSubscriber
{
    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->generateSlugIfNecessary($args->getEntity());
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->generateSlugIfNecessary($args->getEntity());
    }

    private function generateSlugIfNecessary($entity): void
    {
        if (!$this->entityUseSlug($entity)) {
            return;
        }

        if (null === $entity->getSlug()) {
            $entity->setSlug(self::generateSlug());
        }
    }

    private function entityUseSlug(object $entity): bool
    {
        // Here is noted all the ways to know if the entity is using a slug, a
        // trait could have been used and then a check could have been made to
        // see if the correct trait had been used but in this case, checking if
        // the entity extends the abstract class is sufficient.
        return $entity instanceof AbstractEntity;
    }

    private static function generateSlug(): string
    {
        return (new Client)->generateId(size: 21, mode: Client::MODE_DYNAMIC);
    }
}
