<?php

declare(strict_types=1);

namespace App\Entity;

use App\DataFixtures\BaseFixture;

/**
 * For DataFixtures reference or cache purpose, this stringable interface ensure
 * the entity implement a "toUniqueString" method who MUST return an unique
 * string within his class. This string can be built using id or natural
 * identifier.
 *
 * @see  BaseFixture::addSafeReference()
 */
interface UniqueStringableInterface
{
    /**
     * Used for DataFixtures reference or cache purpose.
     *
     * @return  string  an UNIQUE string within the current class. It MUST
     *                  identify the current instance.
     */
    public function toUniqueString(): string;
}
