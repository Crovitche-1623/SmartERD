<?php

declare(strict_types=1);

namespace App\Tests;

use App\DataFixtures\EntityFixtures;
use App\Validator\MaxEntries;
use App\Validator\NoHolesInPosition;
use App\Validator\Position;
use App\Entity\{Attribute, Entity};
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AttributesTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private bool $fixturesHaveBeenLoaded = false;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        $container = self::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->validator = $container->get('test.validator');
        $databaseTool = $container->get(DatabaseToolCollection::class)->get();
        if (!$this->fixturesHaveBeenLoaded) {
            $databaseTool->loadFixtures([
                // Because ProjectFixtures need UserFixtures, UserFixtures are
                // automatically loaded.
                EntityFixtures::class
            ]);

            $this->fixturesHaveBeenLoaded = true;
        }

        parent::setUp();
    }

    public function testSortablePosition(): void
    {
        $entity = $this->getEntityReference();

        $attributeOne = new Attribute;
        $attributeOne->setName('AttributeOne');
        $attributeOne->setEntity($entity);
        $this->entityManager->persist($attributeOne);

        $attributeTwo = new Attribute;
        $attributeTwo->setName('AttributeTwo');
        $attributeTwo->setEntity($entity);
        $this->entityManager->persist($attributeTwo);

        $this->entityManager->flush();

        self::assertEquals(0, $attributeOne->getPosition());
        self::assertEquals(1, $attributeTwo->getPosition());
    }

    // Some sortable libraries put the position at the end even if the position
    // specified is higher than the greatest value so the behaviour must be
    // checked.
    public function testSetPositionManually(): void
    {
        $attribute = new Attribute;
        $attribute->setName('Attribute');
        $attribute->setEntity($this->getEntityReference());
        $attribute->setPosition(127);

        $violations = $this->validator->validate($attribute);

        $errors = (string) $violations;

        $expectedError = str_replace(
            ['{{ lastPosition }}', '{{ givenPosition }}'],
            [0, 127],
            NoHolesInPosition::MESSAGE
        );

        self::assertCount(1, $violations);
        self::assertStringContainsString($expectedError, $errors);

    }

    public function testPushAttributeAtTheEnd(): void
    {
        $numberOfAttributes = 10;
        $entity = $this->getEntityReference();
        $this->createAttributes($entity, $numberOfAttributes);

        $attribute = new Attribute;
        $attribute->setName('PushedAtTheEnd');
        $attribute->setEntity($entity);
        $attribute->setPosition(-1);

        $this->entityManager->persist($attribute);
        $this->entityManager->flush();

        self::assertEquals($numberOfAttributes, $attribute->getPosition());
    }

    public function testPushAttributeWhenMaxIsReachedDoesNotOverflow(): void
    {
        $numberOfAttributes = 127;
        $entity = $this->getEntityReference();
        $this->createAttributes($entity, $numberOfAttributes);

        $attribute = new Attribute;
        $attribute->setName('TryToOverflow');
        $attribute->setEntity($entity);
        $attribute->setPosition(-1);

        $violations = $this->validator->validate($attribute);

        $errors = (string) $violations;

        $expectedError = str_replace(
            ['{{ entityName }}', '{{ sortableGroupProperty }}'],
            ['Attribute', 'entity'],
            Position::MESSAGE_IF_SORTABLE_GROUP
        );

        self::assertCount(1, $violations);
        self::assertStringContainsString($expectedError, $errors);
    }

    public function testPositionsAreRecalculatedIfAnAttributeIsDeleted(): void
    {
        $entity = $this->getEntityReference();
        $this->createAttributes($entity, 3);

        /** @var  Attribute  $firstAttribute */
        $firstAttribute = $entity->getAttributes()->first();

        self::assertEquals([0, 1, 2], $this->getPositions($entity));

        $this->entityManager->remove($firstAttribute);
        $this->entityManager->flush();

        $entity = $this->getEntityReference();

        self::assertEquals([0, 1], $this->getPositions($entity));
    }


    public function testSortableGroup(): void
    {
        $entityOne = $this->getEntityReference();

        $attributeInEntityOne = new Attribute;
        $attributeInEntityOne->setName('AttributeInEntityOne');
        $attributeInEntityOne->setEntity($entityOne);

        $this->entityManager->persist($attributeInEntityOne);
        $this->entityManager->flush();

        $entityTwo = $this->getEntityReference(EntityFixtures::ANOTHER_USER_PROJECT_ENTITY_NAME);

        $attributeInEntityTwo = new Attribute;
        $attributeInEntityTwo->setName('AttributeInEntityTwo');
        $attributeInEntityTwo->setEntity($entityTwo);

        $this->entityManager->persist($attributeInEntityTwo);
        $this->entityManager->flush();

        self::assertEquals(0, $attributeInEntityOne->getPosition());
        self::assertEquals(0, $attributeInEntityOne->getPosition());
    }

    public function testPositionsAreRecalculatedWhenAttributeIsSetAtBeginning(): void
    {
        $entity = $this->getEntityReference();

        $this->createAttributes($entity, 3);

        $attribute = new Attribute;
        $attribute->setName('IllBePushedAtTheStart');
        $attribute->setEntity($entity);
        $attribute->setPosition(0);
        $this->entityManager->persist($attribute);
        $this->entityManager->flush();

        // Entity need to be refreshed from database
        $entity = $this->getEntityReference();

        // [0, 1, 2] become [0, 1, 2, 3] so we check if there are no differences
        self::assertCount(0, array_diff([0, 1, 2, 3], $this->getPositions($entity)));
    }

    /**
     * @param  Entity  $entity
     *
     * @return  int[]  positions
     */
    private function getPositions(Entity $entity): array
    {
        $attributes = $entity->getAttributes();

        $positions = [];

        /** @var  Attribute  $attribute */
        foreach ($attributes as $attribute) {
            $positions[] = $attribute->getPosition();
        }

        return $positions;
    }

    private function createAttributes(
        Entity $entity,
        int $attributeCount
    ): void
    {
        for ($i = 0; $i < $attributeCount; $i++) {
            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            $name = str_replace('-', '', $formatter->format($i));
            $this->entityManager->persist(
                (new Attribute)
                    ->setName($name)
                    ->setEntity($entity)
            );
        }


        $this->entityManager->flush();
    }

    private function getEntityReference(string $name = EntityFixtures::USER_PROJECT_ENTITY_NAME): Entity
    {
        $entityRepository = $this->entityManager->getRepository(Entity::class);

        return $entityRepository->findOneBy(['name' => $name]);
    }
}
