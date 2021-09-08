<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AbstractEntity;
use App\Entity\SlugTrait;
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
        $usedTraits = self::retrieveUsedTraits($entity);

        return in_array(SlugTrait::class, $usedTraits);
    }

    private static function retrieveUsedTraits(object $class): array
    {
        $traits = [];

        do {
            $traits = array_merge(class_uses($class, true), $traits);
        } while ($class = get_parent_class($class));

        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, true), $traits);
        }

        return array_unique($traits);
    }

    private static function generateSlug(): string
    {
        return (new Client)->generateId(size: 21, mode: Client::MODE_DYNAMIC);
    }
}
