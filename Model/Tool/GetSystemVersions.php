<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Exception;
use Freento\Mcp\Api\ToolInterface;
use Freento\Mcp\Api\ToolResultInterface;
use Freento\Mcp\Model\ToolResultFactory;
use Magento\AdvancedSearch\Model\Client\ClientResolver as SearchClientResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Session\SaveHandler\Redis\Config as RedisConfig;

/**
 * Get system versions tool
 *
 * Returns versions of key system components:
 * - Magento
 * - PHP
 * - MySQL/MariaDB
 * - Composer
 * - OpenSearch/ElasticSearch
 * - Cache backend (Redis or Filesystem)
 * - Session storage (Redis, Files, or DB)
 */
class GetSystemVersions implements ToolInterface
{
    private ResourceConnection $resourceConnection;
    private ToolResultFactory $resultFactory;
    private ProductMetadataInterface $productMetadata;
    private ComposerInformation $composerInformation;
    private ScopeConfigInterface $scopeConfig;
    private SearchClientResolver $searchClientResolver;
    private DeploymentConfig $deploymentConfig;
    private RedisConfig $redisConfig;
    private Curl $curl;
    private Json $json;

    public function __construct(
        ResourceConnection $resourceConnection,
        ToolResultFactory $resultFactory,
        ProductMetadataInterface $productMetadata,
        ComposerInformation $composerInformation,
        ScopeConfigInterface $scopeConfig,
        SearchClientResolver $searchClientResolver,
        DeploymentConfig $deploymentConfig,
        RedisConfig $redisConfig,
        Curl $curl,
        Json $json
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->resultFactory = $resultFactory;
        $this->productMetadata = $productMetadata;
        $this->composerInformation = $composerInformation;
        $this->scopeConfig = $scopeConfig;
        $this->searchClientResolver = $searchClientResolver;
        $this->deploymentConfig = $deploymentConfig;
        $this->redisConfig = $redisConfig;
        $this->curl = $curl;
        $this->json = $json;
    }

    public function getName(): string
    {
        return 'get_system_versions';
    }

    public function getDescription(): string
    {
        return 'Get versions of system components running on the server.

Use this tool when you need to:
- Check system compatibility
- Debug environment issues
- Verify server requirements
- Document infrastructure setup
- Check search engine version
- Check cache/session configuration

Returns: Magento, PHP, MySQL/MariaDB, Composer, OpenSearch/ElasticSearch, Cache backend, Session storage.

Example prompts:
- "What PHP version is running?"
- "Show me MySQL version"
- "What are the system versions?"
- "Check server environment"
- "What search engine is installed?"
- "What cache backend is used?"';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'examples' => [new \stdClass()]
        ];
    }

    public function execute(array $arguments): ToolResultInterface
    {
        $lines = [];

        // Magento version
        $lines[] = 'Magento: ' . $this->productMetadata->getVersion()
            . ' (' . $this->productMetadata->getEdition() . ')';

        // PHP version
        $lines[] = 'PHP: ' . PHP_VERSION;

        // MySQL/MariaDB version
        $connection = $this->resourceConnection->getConnection();
        $mysqlVersion = $connection->fetchOne('SELECT VERSION()');
        $lines[] = 'MySQL: ' . $mysqlVersion;

        // Composer version
        $lines[] = 'Composer: ' . $this->getComposerVersion();

        // Search engine (OpenSearch/ElasticSearch)
        $lines[] = 'Search Engine: ' . $this->getSearchEngineVersion();

        // Cache backend
        $lines[] = 'Cache: ' . $this->getCacheBackendInfo();

        // Session storage
        $lines[] = 'Session: ' . $this->getSessionStorageInfo();

        return $this->resultFactory->createText(implode("\n", $lines));
    }

    /**
     * Get Composer version
     */
    private function getComposerVersion(): string
    {
        try {
            // Try to get version from Composer\Composer class if available
            if (defined('Composer\Composer::VERSION')) {
                return \Composer\Composer::VERSION;
            }

            // Fallback: try to get from composer.lock
            $composerLockPath = BP . '/composer.lock';
            if (file_exists($composerLockPath)) {
                $lockContent = file_get_contents($composerLockPath);
                $lockData = $this->json->unserialize($lockContent);
                if (isset($lockData['plugin-api-version'])) {
                    return $lockData['plugin-api-version'] . ' (from lock)';
                }
            }

            return 'unknown';
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    /**
     * Get search engine version (OpenSearch or ElasticSearch)
     */
    private function getSearchEngineVersion(): string
    {
        try {
            $engine = $this->scopeConfig->getValue('catalog/search/engine');
            if (!$engine) {
                return 'not configured';
            }

            $searchClient = $this->searchClientResolver->create($engine);

            // OpenSearch client
            if (method_exists($searchClient, 'getOpenSearchClient')) {
                $info = $searchClient->getOpenSearchClient()->info();
                if (isset($info['version']['number'])) {
                    return 'OpenSearch ' . $info['version']['number'];
                }
            }

            // ElasticSearch - try HTTP request
            $host = $this->scopeConfig->getValue('catalog/search/' . $engine . '_server_hostname') ?: 'localhost';
            $port = $this->scopeConfig->getValue('catalog/search/' . $engine . '_server_port') ?: '9200';
            $url = 'http://' . $host . ':' . $port;

            $this->curl->setTimeout(5);
            $this->curl->get($url);
            $response = $this->json->unserialize($this->curl->getBody());

            $distribution = $response['version']['distribution'] ?? 'ElasticSearch';
            $version = $response['version']['number'] ?? 'unknown';

            return ucfirst($distribution) . ' ' . $version;
        } catch (Exception $e) {
            return 'unavailable (' . $e->getMessage() . ')';
        }
    }

    /**
     * Get cache backend info (Redis with version or Filesystem)
     */
    private function getCacheBackendInfo(): string
    {
        try {
            $cacheBackend = $this->deploymentConfig->get('cache/frontend/default/backend');

            if ($cacheBackend === 'Magento\\Framework\\Cache\\Backend\\Redis'
                || $cacheBackend === 'Cm_Cache_Backend_Redis'
            ) {
                $redisVersion = $this->getRedisVersionFromCache();
                return $redisVersion ? 'Redis ' . $redisVersion : 'Redis';
            }

            return 'Filesystem';
        } catch (Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Get session storage info (Redis with version, Files, or DB)
     */
    private function getSessionStorageInfo(): string
    {
        try {
            $sessionSave = $this->deploymentConfig->get('session/save');

            if ($sessionSave === 'redis') {
                $redisVersion = $this->getRedisVersionFromSession();
                return $redisVersion ? 'Redis ' . $redisVersion : 'Redis';
            }

            if ($sessionSave === 'db') {
                return 'Database';
            }

            return 'Files';
        } catch (Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Get Redis version from cache configuration
     */
    private function getRedisVersionFromCache(): ?string
    {
        try {
            $host = $this->deploymentConfig->get('cache/frontend/default/backend_options/server') ?: 'localhost';
            $port = $this->deploymentConfig->get('cache/frontend/default/backend_options/port') ?: 6379;
            $password = $this->deploymentConfig->get('cache/frontend/default/backend_options/password');
            $database = $this->deploymentConfig->get('cache/frontend/default/backend_options/database') ?: 0;

            return $this->getRedisVersion($host, (int)$port, $password, (int)$database);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get Redis version from session configuration
     */
    private function getRedisVersionFromSession(): ?string
    {
        try {
            $host = $this->redisConfig->getHost();
            $port = $this->redisConfig->getPort();
            $password = $this->redisConfig->getPassword();
            $database = $this->redisConfig->getDatabase();

            return $this->getRedisVersion($host, (int)$port, $password, (int)$database);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Connect to Redis and get version
     */
    private function getRedisVersion(string $host, int $port, ?string $password, int $database): ?string
    {
        try {
            $redisClient = new \Credis_Client(
                $host,
                $port,
                null,
                '',
                $database,
                $password ?: null
            );
            $redisClient->setMaxConnectRetries(1);
            $redisClient->connect();

            $info = $redisClient->info();
            return $info['redis_version'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
}
