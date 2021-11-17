<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\SlugInterface;
use Doctrine\ORM\{EntityManagerInterface, NonUniqueResultException, NoResultException};
use Symfony\Component\{Validator\Constraint, Validator\ConstraintValidator};
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * TODO: Check concurrency access with COUNT
 */
final class MaxEntriesValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {}

    /**
     * {@inheritDoc}
     *
     * @throws  NonUniqueResultException
     * @throws  \ReflectionException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MaxEntries) {
            throw new UnexpectedTypeException($constraint, MaxEntries::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) to take care of that
        if (null === $value || '' === $value) {
            return;
        }

        // In case the constraint is used on wrong property.
        if (!is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        if (!($value instanceof SlugInterface)) {
            throw new UnexpectedTypeException($value, SlugInterface::class);
        }
        /** @var  SlugInterface   $value */

        $property = !$this->context->getPropertyName() ?
            $constraint->property :
            $this->context->getPropertyName();

        if (!$this->context->getObject()) {
            throw new \UnexpectedValueException('The current context validated must be an object');
        }

        $parentFqdn = get_class($this->context->getObject());

        $query = $this->entityManager->createQuery(/** @lang DQL */"
            SELECT
                COUNT(c0.id)
            FROM
                $parentFqdn c0
                JOIN c0.$property d1
            WHERE
                d1.slug = :slug
        ");

        $query->setParameter('slug', $value->getSlug());

        $childShortName = (new \ReflectionClass(get_class($value)))->getShortName();

        try {
            /** @var  int  $entriesCount */
            $entriesCount = $query->getSingleScalarResult();

            if ($entriesCount >= $constraint->max) {
                $this->context->buildViolation($constraint::MESSAGE)
                    ->setParameter('{{ max }}', (string) $constraint->max)
                    ->setParameter('{{ childName }}', $childShortName)
                    ->setParameter('{{ parentName }}', (new \ReflectionClass($parentFqdn))->getShortName())
                    ->addViolation();
            }
        } catch (NoResultException) {
            $this->context->buildViolation('The %childName% is probably not defined.', [
                   '%childName%' => $childShortName
                ])
                ->addViolation();
        }
    }
}
