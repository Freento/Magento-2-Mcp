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
     * Get by id
     *
     * @param int $roleId
     * @return AclRoleInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $roleId): AclRoleInterface;

    /**
     * Save
     *
     * @param AclRoleInterface $role
     * @return AclRoleInterface
     * @throws CouldNotSaveException
     */
    public function save(AclRoleInterface $role): AclRoleInterface;

    /**
     * Delete
     *
     * @param AclRoleInterface $role
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(AclRoleInterface $role): bool;

    /**
     * Delete by id
     *
     * @param int $roleId
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $roleId): bool;

    /**
     * Get list
     *
     * @return AclRoleInterface[]
     */
    public function getList(): array;

    /**
     * Get tools assigned to role
     *
     * @param int $roleId
     * @return string[]
     */
    public function getRoleTools(int $roleId): array;

    /**
     * Save tools for role
     *
     * @param int $roleId
     * @param string[] $toolNames
     */
    public function saveRoleTools(int $roleId, array $toolNames): void;
}
