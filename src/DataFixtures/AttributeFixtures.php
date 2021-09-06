<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\{Factory, Generator};

final class AttributeFixtures extends BaseFixture
{
    private ObjectManager $manager;

    /**
     * {@inheritDoc}
     */
    protected function loadData(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->faker = Factory::create('fr_CH');
    }
}
