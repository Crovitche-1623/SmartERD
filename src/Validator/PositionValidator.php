<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\AbstractEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * FIXME: Check concurrency and if a transaction is needed.
 */
final class PositionValidator extends ConstraintValidator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {}

    /**
     * {@inheritDoc}
     *
     * @param  int  $value  it's the position
     *
     * @throws  \ReflectionException
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Position) {
            throw new UnexpectedTypeException($constraint, Position::class);
        }

        if (-1 !== $value) {
            return;
        }

        if (null === $parentFqdnObject = $this->context->getObject()) {
            throw new UnexpectedTypeException($parentFqdnObject, 'object');
        }

        /** @var  object  $parentFqdnObject */
        $parentFqdnName = get_class($parentFqdnObject);

        $property = $constraint->sortableGroupProperty;

        if (null === $property) {
            // We use MAX() instead of count in case the position has been
            // defined manually
            $dql = /** @lang  DQL */"SELECT MAX(c0.position) FROM $parentFqdnName c0";

            $query = $this->entityManager->createQuery($dql);

            $message = $constraint::MESSAGE;
        } else {
            $methodToCall = 'get'. ucfirst($property);

            /** @var  AbstractEntity  $sortableGroupObject */
            $sortableGroupObject = $parentFqdnObject->$methodToCall();

            // We use MAX() instead of count in case the position has been
            // defined manually
            $dql = /** @lang  DQL */"SELECT MAX(c0.position) FROM $parentFqdnName c0 WHERE c0.$property = :id";

            $query = $this->entityManager->createQuery($dql);

            $query->setParameter('id', $sortableGroupObject->getId());

            $message = $constraint::MESSAGE_IF_SORTABLE_GROUP;
        }

        try {
            // We add +1 because position start at 0.
            $entriesCount = (int) $query->getSingleScalarResult() + 1;

        } catch (NoResultException|NonUniqueResultException) {
            $this->context->buildViolation('Request server side is wrong formatted. Please contact the website administrator.')
                ->addViolation();

            return;
        }

        if ($entriesCount < $constraint->max) {
            return;
        }

        $violation = $this->context->buildViolation($message)
            ->setParameter('{{ entityName }}', (new \ReflectionClass($parentFqdnName))->getShortName());

        if ($property) {
            $violation->setParameter('{{ sortableGroupProperty }}', $property);
        }

        $violation->addViolation();
    }
}
