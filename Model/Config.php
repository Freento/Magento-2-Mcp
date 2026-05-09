<?php

declare(strict_types=1);

namespace Freento\Mcp\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private const XML_PATH_ANONYMITY_ENABLED = 'freento_mcp/privacy/anonymity_enabled';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check if anonymity mode is enabled
     *
     * @return bool
     */
    public function isAnonymityEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ANONYMITY_ENABLED);
    }
}
