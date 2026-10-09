<?php

namespace App\Services;

use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class JwtService
{
    private const ALGORITHM = 'HS256';

    /**
     * Create a signed token for a signed-in user.
     * The token carries who the user is, which tenant database they belong to, and their role.
     */
    public function createToken(int $userId, int $familyId, string $role): string
    {
        $issuedAt = time();

        $payload = [
            'iss' => config('app.url'),
            'iat' => $issuedAt,
            'exp' => $issuedAt + (config('jwt.ttl_minutes') * 60),
            'sub' => (string) $userId,
            'family_id' => $familyId,
            'role' => $role,
        ];

        return JWT::encode($payload, $this->secret(), self::ALGORITHM);
    }

    /**
     * Check a token and return its data, or null if it is invalid or expired.
     */
    public function decodeToken(string $token): ?array
    {
        // Read the secret outside the try block, so a missing secret fails loudly.
        $secret = $this->secret();

        try {
            $decoded = JWT::decode($token, new Key($secret, self::ALGORITHM));
        } catch (UnexpectedValueException|DomainException|InvalidArgumentException $exception) {
            // Covers expired, wrong signature, and malformed tokens.
            return null;
        }

        return (array) $decoded;
    }

    private function secret(): string
    {
        $secret = config('jwt.secret');

        if (empty($secret)) {
            throw new RuntimeException('JWT_SECRET is not set in .env');
        }

        return $secret;
    }
}