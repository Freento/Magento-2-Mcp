<?php
declare(strict_types=1);

namespace Freento\Mcp\Api\Data;

interface AclRoleInterface
{
    public const ROLE_ID = 'role_id';
    public const NAME = 'name';
    public const ACCESS_TYPE = 'access_type';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const ACCESS_TYPE_ALL = 'all';
    public const ACCESS_TYPE_SPECIFIED = 'specified';

    public function getRoleId(): ?int;
    public function setRoleId(int $roleId): self;

    public function getName(): ?string;
    public function setName(string $name): self;

    public function getAccessType(): string;
    public function setAccessType(string $accessType): self;

    public function getCreatedAt(): ?string;
    public function setCreatedAt(string $createdAt): self;

    public function getUpdatedAt(): ?string;
    public function setUpdatedAt(string $updatedAt): self;
}
