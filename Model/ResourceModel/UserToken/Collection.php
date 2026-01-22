<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\UserToken;

use Freento\Mcp\Model\UserToken;
use Freento\Mcp\Model\ResourceModel\UserToken as UserTokenResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct(): void
    {
        $this->_init(UserToken::class, UserTokenResource::class);
    }
}
