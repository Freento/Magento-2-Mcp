<?php
declare(strict_types=1);

namespace Freento\Mcp\Service;

use Freento\Mcp\Api\AclRoleRepositoryInterface;
use Freento\Mcp\Api\Data\AclRoleInterface;
use Freento\Mcp\Api\Data\UserTokenInterface;
use Freento\Mcp\Api\UserTokenRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class AclValidator
{
    private UserTokenRepositoryInterface $userTokenRepository;
    private AclRoleRepositoryInterface $aclRoleRepository;
    private TokenGenerator $tokenGenerator;

    public function __construct(
        UserTokenRepositoryInterface $userTokenRepository,
        AclRoleRepositoryInterface $aclRoleRepository,
        TokenGenerator $tokenGenerator
    ) {
        $this->userTokenRepository = $userTokenRepository;
        $this->aclRoleRepository = $aclRoleRepository;
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * Validate token and return user token entity or null
     */
    public function validateToken(string $token): ?UserTokenInterface
    {
        $tokenHash = $this->tokenGenerator->hash($token);
        return $this->userTokenRepository->getByTokenHash($tokenHash);
    }

    /**
     * Check if user can use specific tool
     */
    public function canUseTool(UserTokenInterface $userToken, string $toolName): bool
    {
        $roleId = $userToken->getRoleId();

        // No role assigned = no access
        if ($roleId === null) {
            return false;
        }

        try {
            $role = $this->aclRoleRepository->getById($roleId);
        } catch (NoSuchEntityException $e) {
            return false;
        }

        // Access type 'all' = full access
        if ($role->getAccessType() === AclRoleInterface::ACCESS_TYPE_ALL) {
            return true;
        }

        // Access type 'specified' = check role tools
        $allowedTools = $this->aclRoleRepository->getRoleTools($roleId);
        return in_array($toolName, $allowedTools, true);
    }

    /**
     * Get list of allowed tools for user token
     * @return string[]|null Returns null if user has access to all tools
     */
    public function getAllowedTools(UserTokenInterface $userToken): ?array
    {
        $roleId = $userToken->getRoleId();

        if ($roleId === null) {
            return [];
        }

        try {
            $role = $this->aclRoleRepository->getById($roleId);
        } catch (NoSuchEntityException $e) {
            return [];
        }

        // Access type 'all' = null means all tools allowed
        if ($role->getAccessType() === AclRoleInterface::ACCESS_TYPE_ALL) {
            return null;
        }

        return $this->aclRoleRepository->getRoleTools($roleId);
    }
}
