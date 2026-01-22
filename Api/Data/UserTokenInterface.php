<?php
declare(strict_types=1);

namespace Freento\Mcp\Api\Data;

interface UserTokenInterface
{
    public const ID = 'id';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const TOKEN_HASH = 'token_hash';
    public const ROLE_ID = 'role_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getId(): ?int;
    public function setId(int $id): self;

    public function getAdminUserId(): ?int;
    public function setAdminUserId(int $adminUserId): self;

    public function getTokenHash(): ?string;
    public function setTokenHash(string $tokenHash): self;

    public function getRoleId(): ?int;
    public function setRoleId(?int $roleId): self;

    public function getCreatedAt(): ?string;
    public function setCreatedAt(string $createdAt): self;

    public function getUpdatedAt(): ?string;
    public function setUpdatedAt(string $updatedAt): self;
}
