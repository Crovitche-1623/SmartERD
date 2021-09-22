<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Attribute;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

final class AttributeSortableRepository extends SortableRepository
{
    /**
     * {@inheritDoc}
     */
    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct(
            em: $manager,
            class: $manager->getClassMetadata(Attribute::class)
        );
    }
}
