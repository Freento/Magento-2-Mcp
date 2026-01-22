<?php
declare(strict_types=1);

namespace Freento\Mcp\Ui\DataProvider;

use Freento\Mcp\Api\AclRoleRepositoryInterface;
use Freento\Mcp\Model\ResourceModel\AclRole\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class AclRoleFormDataProvider extends AbstractDataProvider
{
    private AclRoleRepositoryInterface $roleRepository;
    private RequestInterface $request;
    private ?array $loadedData = null;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        AclRoleRepositoryInterface $roleRepository,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->roleRepository = $roleRepository;
        $this->request = $request;
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        $roleId = $this->request->getParam('role_id');

        if ($roleId) {
            $items = $this->collection->getItems();
            foreach ($items as $role) {
                $roleData = $role->getData();
                $roleData['tools'] = $this->roleRepository->getRoleTools((int)$role->getRoleId());
                $this->loadedData[$role->getRoleId()] = $roleData;
            }
        }

        return $this->loadedData;
    }
}
