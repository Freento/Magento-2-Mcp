<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\AclRole;

use Freento\Mcp\Model\AclRole;
use Freento\Mcp\Model\ResourceModel\AclRole as AclRoleResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'role_id';

    protected function _construct(): void
    {
        $this->_init(AclRole::class, AclRoleResource::class);
    }
}
