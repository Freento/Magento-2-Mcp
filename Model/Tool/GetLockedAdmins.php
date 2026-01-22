<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Api\ToolInterface;
use Freento\Mcp\Api\ToolResultInterface;
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
    private ResourceConnection $resourceConnection;
    private ToolResultFactory $resultFactory;
    private DateTime $dateTime;

    public function __construct(
        ResourceConnection $resourceConnection,
        ToolResultFactory $resultFactory,
        DateTime $dateTime
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->resultFactory = $resultFactory;
        $this->dateTime = $dateTime;
    }

    public function getName(): string
    {
        return 'get_locked_admins';
    }

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

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'include_expired' => [
                    'type' => 'boolean',
                    'description' => 'Include accounts with expired locks or any failed attempts history (default: false, shows only currently locked)'
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'Filter by email. Supports wildcards.'
                ],
                'username' => [
                    'type' => 'string',
                    'description' => 'Filter by username. Supports wildcards.'
                ],
                'min_failures' => [
                    'type' => 'integer',
                    'description' => 'Minimum number of failed attempts to include (default: 1)'
                ]
            ],
            'examples' => [
                new \stdClass(),
                ['include_expired' => true],
                ['min_failures' => 3],
                ['email' => '%@example.com']
            ]
        ];
    }

    public function execute(array $arguments): ToolResultInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $adminTable = $this->resourceConnection->getTableName('admin_user');

        $appliedFilters = [];
        $includeExpired = !empty($arguments['include_expired']);
        $currentTime = $this->dateTime->gmtDate();

        $select = $connection->select()
            ->from(['admin' => $adminTable], [
                'user_id',
                'firstname',
                'lastname',
                'email',
                'username',
                'is_active',
                'failures_num',
                'first_failure',
                'lock_expires'
            ]);

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

        // Email filter
        if (!empty($arguments['email'])) {
            $email = $arguments['email'];
            if (strpos($email, '%') !== false) {
                $select->where('admin.email LIKE ?', $email);
                $appliedFilters[] = "email LIKE: {$email}";
            } else {
                $select->where('admin.email = ?', $email);
                $appliedFilters[] = "email: {$email}";
            }
        }

        // Username filter
        if (!empty($arguments['username'])) {
            $username = $arguments['username'];
            if (strpos($username, '%') !== false) {
                $select->where('admin.username LIKE ?', $username);
                $appliedFilters[] = "username LIKE: {$username}";
            } else {
                $select->where('admin.username = ?', $username);
                $appliedFilters[] = "username: {$username}";
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

        foreach ($admins as $admin) {
            $name = trim(($admin['firstname'] ?? '') . ' ' . ($admin['lastname'] ?? ''));
            if (empty($name)) {
                $name = 'N/A';
            }

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

            $lines[] = "Admin ID: {$admin['user_id']}";
            $lines[] = "  Username: {$admin['username']}";
            $lines[] = "  Name: {$name}";
            $lines[] = "  Email: {$admin['email']}";
            $lines[] = "  Account Status: {$accountStatus}";
            $lines[] = "  Lock Status: {$lockStatus}";
            $lines[] = "  Failed Attempts: {$failuresNum}";
            $lines[] = "  First Failure: {$firstFailure}";
            $lines[] = "";
        }

        if (!empty($appliedFilters)) {
            $lines[] = "Filters applied: " . implode(', ', $appliedFilters);
        }

        $lines[] = "";
        $lines[] = "Note: To unlock an admin, reset failures_num to 0 and clear lock_expires in admin_user table.";

        return implode("\n", $lines);
    }
}
