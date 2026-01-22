<?php
declare(strict_types=1);

namespace Freento\Mcp\Api;

use Freento\Mcp\Api\Data\UserTokenInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface UserTokenRepositoryInterface
{
    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $id): UserTokenInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getByAdminUserId(int $adminUserId): UserTokenInterface;

    /**
     * Get by token hash
     */
    public function getByTokenHash(string $tokenHash): ?UserTokenInterface;

    /**
     * @throws CouldNotSaveException
     */
    public function save(UserTokenInterface $userToken): UserTokenInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(UserTokenInterface $userToken): bool;

    /**
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteByAdminUserId(int $adminUserId): bool;
}
