<?php
declare(strict_types=1);

namespace Freento\Mcp\Service;

class TokenGenerator
{
    /**
     * Generate a random token (64 hex characters)
     */
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Hash a token for storage
     */
    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
