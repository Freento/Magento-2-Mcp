<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Api\ToolInterface;
use Freento\Mcp\Api\ToolResultInterface;
use Freento\Mcp\Model\Config;
use Freento\Mcp\Model\ToolResultFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Get locked admin accounts
 *
 * This tool has special time-based logic and doesn't fit AbstractEntityTool pattern well.
 * It returns accounts with lock/failure history, not paginated entity list.
 */
class GetLockedAdmins implements ToolInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param ToolResultFactory $resultFactory
     * @param DateTime $dateTime
     * @param Config $config
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly ToolResultFactory $resultFactory,
        private readonly DateTime $dateTime,
        private readonly Config $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'get_locked_admins';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Get list of locked admin accounts from Magento store.

Use this tool when you need to:
- Find currently locked admin accounts
- Check failed login attempts
- Audit security events
- Troubleshoot admin login issues

Returns locked admins with lock details: email, username, failure count,
first failure time, and lock expiration time.

By default shows only currently locked accounts. Use include_expired=true
to also show accounts with expired locks or failed attempts history.

Example prompts:
- "Show me locked admin accounts"
- "Are there any locked administrators?"
- "Check for failed admin login attempts"
- "Which admin accounts are currently locked?"';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        $properties = [
            'include_expired' => [
                'type' => 'boolean',
                'description' => 'Include accounts with expired locks or any failed attempts history'
                                . ' (default: false, shows only currently locked)'
            ],
            'min_failures' => [
                'type' => 'integer',
                'description' => 'Minimum number of failed attempts to include (default: 1)'
            ]
        ];

        $examples = [
            new \stdClass(),
            ['include_expired' => true],
            ['min_failures' => 3],
        ];

        if (!$this->config->isAnonymityEnabled()) {
            $properties['email'] = [
                'type' => 'string',
                'description' => 'Filter by email. Supports wildcards.'
            ];
            $properties['username'] = [
                'type' => 'string',
                'description' => 'Filter by username. Supports wildcards.'
            ];
            $examples[] = ['email' => '%@example.com'];
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'examples' => $examples
        ];
    }

    /**
     * @inheritDoc
     */
    public function execute(array $arguments): ToolResultInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $adminTable = $this->resourceConnection->getTableName('admin_user');

        $appliedFilters = [];
        $includeExpired = !empty($arguments['include_expired']);
        $currentTime = $this->dateTime->gmtDate();
        $columns = $this->getAvailableColumns();

        $select = $connection->select()
            ->from(['admin' => $adminTable], $columns);

        if ($includeExpired) {
            $select->where(
                'admin.failures_num > 0 OR admin.lock_expires IS NOT NULL OR admin.first_failure IS NOT NULL'
            );
            $appliedFilters[] = "include_expired: true";
        } else {
            $select->where('admin.lock_expires IS NOT NULL');
            $select->where('admin.lock_expires > ?', $currentTime);
            $appliedFilters[] = "currently_locked: true";
        }

        foreach (['email', 'username'] as $textField) {
            if (empty($arguments[$textField]) || in_array($textField, $columns)) {
                continue;
            }

            $filterValue = $arguments[$textField];
            if (strpos($filterValue, '%') !== false) {
                $select->where("admin.$textField LIKE ?", $filterValue);
                $appliedFilters[] = "{$textField} LIKE: {$filterValue}";
            } else {
                $select->where("admin.$textField = ?", $filterValue);
                $appliedFilters[] = "{$textField}: {$filterValue}";
            }
        }

        // Minimum failures filter
        if (isset($arguments['min_failures']) && $arguments['min_failures'] > 0) {
            $minFailures = (int)$arguments['min_failures'];
            $select->where('admin.failures_num >= ?', $minFailures);
            $appliedFilters[] = "min_failures: {$minFailures}";
        }

        $select->order('admin.lock_expires DESC');

        $admins = $connection->fetchAll($select);

        $result = $this->formatLockedAdmins($admins, $appliedFilters, $currentTime, $includeExpired);

        return $this->resultFactory->createText($result);
    }

    /**
     * Get available columns
     *
     * @return string[]
     */
    private function getAvailableColumns(): array
    {
        $columns = [
            'user_id',
            'is_active',
            'failures_num',
            'first_failure',
            'lock_expires'
        ];

        if (!$this->config->isAnonymityEnabled()) {
            $columns += ['firstname', 'lastname', 'email', 'username'];
        }

        return $columns;
    }

    /**
     * Format Locked Admins
     *
     * @param array $admins
     * @param array $appliedFilters
     * @param string $currentTime
     * @param bool $includeExpired
     * @return string
     */
    private function formatLockedAdmins(
        array $admins,
        array $appliedFilters,
        string $currentTime,
        bool $includeExpired
    ): string {
        $count = count($admins);

        if ($count === 0) {
            $result = $includeExpired
                ? "No admin accounts with failed attempts or lock history found."
                : "No currently locked admin accounts found.";
            if (!empty($appliedFilters)) {
                $result .= "\nFilters applied: " . implode(', ', $appliedFilters);
            }

            return $result;
        }

        $lines = [];
        $currentlyLocked = 0;
        $expiredLocks = 0;

        foreach ($admins as $admin) {
            $isCurrentlyLocked = !empty($admin['lock_expires']) && $admin['lock_expires'] > $currentTime;
            if ($isCurrentlyLocked) {
                $currentlyLocked++;
            } else {
                $expiredLocks++;
            }
        }

        if ($includeExpired) {
            $lines[] = "Found {$count} admin account(s) with lock/failure history:";
            $lines[] = "  Currently locked: {$currentlyLocked}";
            $lines[] = "  Expired/cleared: {$expiredLocks}";
        } else {
            $lines[] = "Found {$currentlyLocked} currently locked admin account(s):";
        }
        $lines[] = "";
        $lines += $this->getAdminsDataLines($admins, $currentTime);
        if (!empty($appliedFilters)) {
            $lines[] = "Filters applied: " . implode(', ', $appliedFilters);
        }

        $lines[] = "";
        $lines[] = "Note: To unlock an admin, reset failures_num to 0 and clear lock_expires in admin_user table.";

        return implode("\n", $lines);
    }

    /**
     * Get Admins Data Lines
     *
     * @param array $admins
     * @param string $currentTime
     * @return array
     */
    private function getAdminsDataLines(array $admins, string $currentTime): array
    {
        $lines = [];
        foreach ($admins as $admin) {
            $lines += $this->getAdminDataLines($admin, $currentTime);
        }

        return $lines;
    }

    /**
     * Get Admin Data Lines
     *
     * @param array $admin
     * @param string $currentTime
     * @return array
     */
    private function getAdminDataLines(array $admin, string $currentTime): array
    {
        $userId = $admin['user_id'] ?? '?';
        $accountStatus = $admin['is_active'] ? 'Active' : 'Inactive';
        $failuresNum = (int)($admin['failures_num'] ?? 0);
        $firstFailure = $admin['first_failure'] ?: 'N/A';
        $lockExpires = $admin['lock_expires'] ?: 'N/A';

        $isCurrentlyLocked = !empty($admin['lock_expires']) && $admin['lock_expires'] > $currentTime;
        if ($isCurrentlyLocked) {
            $lockStatus = "LOCKED (expires: {$lockExpires})";
        } elseif (!empty($admin['lock_expires'])) {
            $lockStatus = "Lock expired: {$lockExpires}";
        } elseif ($failuresNum > 0) {
            $lockStatus = "Has failed attempts (not locked)";
        } else {
            $lockStatus = "Cleared";
        }

        $lines = [];
        $lines[] = "Admin ID: {$userId}";
        $lines += $this->getAdminPersonalDataLines($admin);
        $lines[] = "  Account Status: {$accountStatus}";
        $lines[] = "  Lock Status: {$lockStatus}";
        $lines[] = "  Failed Attempts: {$failuresNum}";
        $lines[] = "  First Failure: {$firstFailure}";
        $lines[] = "";
        return $lines;
    }

    /**
     * Get admin personal data such as Username, Firstname and Lastname and email
     *
     * @param array $admin
     * @return array
     */
    private function getAdminPersonalDataLines(array $admin): array
    {
        $lines = [];
        if (isset($admin['username'])) {
            $lines[] = "  Username: {$admin['username']}";
        }
        if (isset($admin['firstname']) || isset($admin['lastname'])) {
            $name = trim(($admin['firstname'] ?? '') . ' ' . ($admin['lastname'] ?? ''));
            $lines[] = "  Name: " . ($name !== '' ? $name : 'N/A');
        }
        if (isset($admin['email'])) {
            $lines[] = "  Email: {$admin['email']}";
        }

        return $lines;
    }
}
