<?php

declare(strict_types=1);

namespace App\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * FIXME: Check concurrency and if a transaction is needed.
 */
final class NoHolesInPositionValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {}

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoHolesInPosition) {
            throw new UnexpectedTypeException(
                value: $constraint,
                expectedType: NoHolesInPosition::class
            );
        }

        if (!is_int($value)) {
            throw new UnexpectedTypeException($value, 'int');
        }

        $givenPositon = $value;

        if (!($parentFqdnObject = $this->context->getObject())) {
            throw new UnexpectedTypeException($parentFqdnObject, 'object');
        }
        /** @var  object  $parentFqdnObject */

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('MAX(c0.position)')
            ->from(get_class($parentFqdnObject), 'c0');

        if ($property = $constraint->sortableGroupProperty) {
            $queryBuilder
                ->where(sprintf('c0.%s = :id', $property))
                ->setParameter(
                    'id',
                    $parentFqdnObject->{'get'. ucfirst($property)}()->getId()
                );
        }

        $query = $queryBuilder->getQuery();

        try {
            /** @var  int  $lastPosition */
            $lastPosition = $query->getSingleScalarResult();

        } catch (NoResultException) {
            if ($givenPositon > 0) {
                $this->context->buildViolation("You can't set the position higher than 0 because it'll create holes in positions.")
                    ->addViolation();
            }

            return;
        } catch (NonUniqueResultException) {
            $this->context->buildViolation('Request server side is wrong formatted. Please contact the website administrator.')
                ->addViolation();

            return;
        }

        if ($givenPositon < $lastPosition + 1) {
            return;
        }

        if (!$lastPosition) {
            $this->context
                ->buildViolation($constraint::MESSAGE_IF_NO_OTHER_ATTRIBUTES)
                ->addViolation();
            return;
        }

        $this->context->buildViolation($constraint::MESSAGE)
            ->setParameter('{{ lastPosition }}', (string) $lastPosition)
            ->setParameter('{{ givenPosition }}', (string) $value)
            ->addViolation();
    }
}
