<?php
declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\Data\UserTokenInterface;
use Freento\Mcp\Model\ResourceModel\UserToken as UserTokenResource;
use Magento\Framework\Model\AbstractModel;

class UserToken extends AbstractModel implements UserTokenInterface
{
    protected function _construct(): void
    {
        $this->_init(UserTokenResource::class);
    }

    public function getId(): ?int
    {
        $value = parent::getId();
        return $value !== null ? (int)$value : null;
    }

    public function setId($id): UserTokenInterface
    {
        return $this->setData(self::ID, $id);
    }

    public function getAdminUserId(): ?int
    {
        $value = $this->getData(self::ADMIN_USER_ID);
        return $value !== null ? (int)$value : null;
    }

    public function setAdminUserId(int $adminUserId): UserTokenInterface
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    public function getTokenHash(): ?string
    {
        return $this->getData(self::TOKEN_HASH);
    }

    public function setTokenHash(string $tokenHash): UserTokenInterface
    {
        return $this->setData(self::TOKEN_HASH, $tokenHash);
    }

    public function getRoleId(): ?int
    {
        $value = $this->getData(self::ROLE_ID);
        return $value !== null && $value !== '' ? (int)$value : null;
    }

    public function setRoleId(?int $roleId): UserTokenInterface
    {
        return $this->setData(self::ROLE_ID, $roleId);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): UserTokenInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): UserTokenInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
