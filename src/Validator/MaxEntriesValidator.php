<?php

declare(strict_types=1);

namespace App\Validator;

use Doctrine\ORM\{EntityManagerInterface, NoResultException};
use ReflectionClass;
use Symfony\Component\{Validator\Constraint, Validator\ConstraintValidator};
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class MaxEntriesValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {}

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

        $property = $this->context->getPropertyName();

        $parentFqdn = get_class($this->context->getObject());

        $query = $this->entityManager->createQuery(/** @lang DQL */"
            SELECT COUNT(c0.id) FROM $parentFqdn c0 WHERE c0.$property = :id
        ");

        $query->setParameter('id', $value->getId());

        $childShortName = (new ReflectionClass(get_class($value)))->getShortName();

        try {
            $entriesCount = (int) $query->getSingleScalarResult();

            if ($entriesCount >= $constraint->max) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ max }}', (string) $constraint->max)
                    ->setParameter('{{ childName }}', $childShortName)
                    ->setParameter('{{ parentName }}', (new ReflectionClass($parentFqdn))->getShortName())
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
