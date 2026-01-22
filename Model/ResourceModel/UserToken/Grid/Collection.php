<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\UserToken\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'admin_user',
        $resourceModel = null,
        $identifierName = 'user_id',
        $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    protected function _initSelect(): self
    {
        parent::_initSelect();

        $this->getSelect()
            ->joinLeft(
                ['ut' => $this->getTable('freento_mcp_user_token')],
                'main_table.user_id = ut.admin_user_id',
                [
                    'token_id' => 'ut.id',
                    'token_hash' => 'ut.token_hash',
                    'role_id' => 'ut.role_id'
                ]
            )
            ->joinLeft(
                ['r' => $this->getTable('freento_mcp_acl_role')],
                'ut.role_id = r.role_id',
                ['mcp_role_name' => 'r.name']
            )
            ->where('main_table.is_active = ?', 1);

        $this->addFilterToMap('user_id', 'main_table.user_id');
        $this->addFilterToMap('username', 'main_table.username');
        $this->addFilterToMap('email', 'main_table.email');
        $this->addFilterToMap('mcp_role_name', 'r.name');

        return $this;
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'has_token') {
            if (is_array($condition) && isset($condition['eq'])) {
                if ($condition['eq'] === '1') {
                    $this->getSelect()->where('ut.token_hash IS NOT NULL');
                } else {
                    $this->getSelect()->where('ut.token_hash IS NULL');
                }
                return $this;
            }
        }

        return parent::addFieldToFilter($field, $condition);
    }

    protected function _afterLoad(): self
    {
        parent::_afterLoad();

        foreach ($this->_items as $item) {
            $hasToken = !empty($item->getData('token_hash')) ? '1' : '0';
            $item->setData('has_token', $hasToken);

            if (empty($item->getData('mcp_role_name'))) {
                $item->setData('mcp_role_name', '—');
            }
        }

        return $this;
    }
}
