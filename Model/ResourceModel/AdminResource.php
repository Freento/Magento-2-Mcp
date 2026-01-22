<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel;

use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Magento\Framework\DB\Select;

class AdminResource extends AbstractResource
{
    protected function applyRequiredJoins(Select $select, Schema $schema): void
    {
        $authRoleTable = $this->resourceConnection->getTableName('authorization_role');

        $select->joinLeft(
            ['user_role' => $authRoleTable],
            "admin.user_id = user_role.user_id AND user_role.role_type = 'U'",
            []
        );
        $select->joinLeft(
            ['role' => $authRoleTable],
            "user_role.parent_id = role.role_id AND role.role_type = 'G'",
            []
        );
    }

    protected function fetchAll(Select $select, Schema $schema, array $arguments): array
    {
        $rows = parent::fetchAll($select, $schema, $arguments);

        foreach ($rows as &$row) {
            $row['role_name'] = $row['role_name'] ?? 'No Role';
            $row['logdate'] = $row['logdate'] ?: 'Never';
        }

        return $rows;
    }
}
