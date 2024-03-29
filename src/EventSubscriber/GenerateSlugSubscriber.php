<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\SlugInterface;
use App\Entity\SlugTrait;
use App\Service\SlugGeneratorService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\{Event\LifecycleEventArgs, Events};

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
        self::generateSlugIfNecessary($args->getEntity());
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        self::generateSlugIfNecessary($args->getEntity());
    }

    private static function generateSlugIfNecessary(object $entity): void
    {
        if (!self::entityUseSlug($entity)) {
            return;
        }

        /** @var  SlugInterface  $entity */
        if (null === $entity->getSlug()) {
            $entity->setSlug(self::generateSlug());
        }
    }

    private static function entityUseSlug(object $entity): bool
    {
        $usedTraits = self::retrieveUsedTraits($entity);

        return in_array(
            needle: SlugTrait::class,
            haystack: $usedTraits,
            strict: true
        );
    }

    /**
     * @return  array<string, string>
     */
    private static function retrieveUsedTraits(object $class): array
    {
        $traits = [];

        do {
            /** @phpstan-ignore-next-line  */
            $traits = array_merge(class_uses($class, true), $traits);
        } while ($class = get_parent_class($class));

        foreach ($traits as $trait => $same) {
            /** @phpstan-ignore-next-line  */
            $traits = array_merge(class_uses($trait, true), $traits);
        }

        return array_unique($traits);
    }

    private static function generateSlug(): string
    {
        return (new SlugGeneratorService)();
    }
}
