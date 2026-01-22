<?php
declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\Data\UserTokenInterface;
use Freento\Mcp\Api\UserTokenRepositoryInterface;
use Freento\Mcp\Model\ResourceModel\UserToken as UserTokenResource;
use Freento\Mcp\Model\ResourceModel\UserToken\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class UserTokenRepository implements UserTokenRepositoryInterface
{
    private UserTokenResource $resource;
    private UserTokenFactory $tokenFactory;
    private CollectionFactory $collectionFactory;

    public function __construct(
        UserTokenResource $resource,
        UserTokenFactory $tokenFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->tokenFactory = $tokenFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function getById(int $id): UserTokenInterface
    {
        $token = $this->tokenFactory->create();
        $this->resource->load($token, $id);

        if (!$token->getId()) {
            throw new NoSuchEntityException(__('User Token with id "%1" does not exist.', $id));
        }

        return $token;
    }

    public function getByAdminUserId(int $adminUserId): UserTokenInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('admin_user_id', $adminUserId);
        $token = $collection->getFirstItem();

        if (!$token->getId()) {
            throw new NoSuchEntityException(__('User Token for admin user "%1" does not exist.', $adminUserId));
        }

        return $token;
    }

    public function getByTokenHash(string $tokenHash): ?UserTokenInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('token_hash', $tokenHash);
        $token = $collection->getFirstItem();

        if (!$token->getId()) {
            return null;
        }

        return $token;
    }

    public function save(UserTokenInterface $userToken): UserTokenInterface
    {
        try {
            $this->resource->save($userToken);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save User Token: %1', $e->getMessage()));
        }

        return $userToken;
    }

    public function delete(UserTokenInterface $userToken): bool
    {
        try {
            $this->resource->delete($userToken);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete User Token: %1', $e->getMessage()));
        }

        return true;
    }

    public function deleteByAdminUserId(int $adminUserId): bool
    {
        try {
            $token = $this->getByAdminUserId($adminUserId);
            return $this->delete($token);
        } catch (NoSuchEntityException $e) {
            return true; // Already doesn't exist
        }
    }
}
