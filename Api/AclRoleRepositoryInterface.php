<?php
declare(strict_types=1);

namespace Freento\Mcp\Api;

use Freento\Mcp\Api\Data\AclRoleInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface AclRoleRepositoryInterface
{
    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $roleId): AclRoleInterface;

    /**
     * @throws CouldNotSaveException
     */
    public function save(AclRoleInterface $role): AclRoleInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(AclRoleInterface $role): bool;

    /**
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $roleId): bool;

    /**
     * @return AclRoleInterface[]
     */
    public function getList(): array;

    /**
     * Get tools assigned to role
     * @return string[]
     */
    public function getRoleTools(int $roleId): array;

    /**
     * Save tools for role
     * @param string[] $toolNames
     */
    public function saveRoleTools(int $roleId, array $toolNames): void;
}
