<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\Model\RefreshTokenInterface;

final readonly class RefreshTokenManager implements RefreshTokenManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?\DateInterval $expireCleanupDelay = null,
    ) {
        if (null === $expireCleanupDelay) {
            trigger_deprecation('league/oauth2-server-bundle', '2.0', 'Not passing a "%s" to "%s" is deprecated since version 2.0 and will be required in 3.0.', \DateInterval::class, self::class);
        }
    }

    public function find(string $identifier): ?RefreshTokenInterface
    {
        return $this->entityManager->find(RefreshToken::class, $identifier);
    }

    public function save(RefreshTokenInterface $refreshToken): void
    {
        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();
    }

    public function clearExpired(): int
    {
        $expiry = new \DateTimeImmutable();
        if (null !== $this->expireCleanupDelay) {
            $expiry = $expiry->sub($this->expireCleanupDelay);
        }

        /** @var int */
        return $this->entityManager->createQueryBuilder()
            ->delete(RefreshToken::class, 'rt')
            ->where('rt.expiry < :expiry')
            ->setParameter('expiry', $expiry, 'datetime_immutable')
            ->getQuery()
            ->execute();
    }
}
