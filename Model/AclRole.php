<?php
declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\Data\AclRoleInterface;
use Freento\Mcp\Model\ResourceModel\AclRole as AclRoleResource;
use Magento\Framework\Model\AbstractModel;

class AclRole extends AbstractModel implements AclRoleInterface
{
    protected function _construct(): void
    {
        $this->_init(AclRoleResource::class);
    }

    public function getRoleId(): ?int
    {
        $value = $this->getData(self::ROLE_ID);
        return $value !== null ? (int)$value : null;
    }

    public function setRoleId(int $roleId): AclRoleInterface
    {
        return $this->setData(self::ROLE_ID, $roleId);
    }

    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    public function setName(string $name): AclRoleInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getAccessType(): string
    {
        return $this->getData(self::ACCESS_TYPE) ?? self::ACCESS_TYPE_SPECIFIED;
    }

    public function setAccessType(string $accessType): AclRoleInterface
    {
        return $this->setData(self::ACCESS_TYPE, $accessType);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): AclRoleInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): AclRoleInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
