<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class UserToken extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('freento_mcp_user_token', 'id');
    }
}
