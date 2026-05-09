<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\OAuth\Client;

use Freento\Mcp\Model\OAuth\Client;
use Freento\Mcp\Model\ResourceModel\OAuth\Client as ClientResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(Client::class, ClientResource::class);
    }

    /**
     * Join Role Name
     *
     * @return Collection
     */
    public function joinRoleName()
    {
        $roleTable = $this->getTable('freento_mcp_acl_role');
        $this->getSelect()->joinLeft(
            ['role' => $roleTable],
            'role.role_id = main_table.role_id',
            ['role_name' => 'name']
        );

        return $this;
    }

    /**
     * Join Admin Data
     *
     * @return Collection
     */
    public function joinAdminData()
    {
        $adminUser = $this->getTable('admin_user');
        $this->getSelect()->joinLeft(
            ['admin_user' => $adminUser],
            'admin_user.user_id = main_table.admin_user_id',
            ['admin_email' => 'email', 'admin_username' => 'username']
        );

        return $this;
    }
}
