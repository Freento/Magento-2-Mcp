<?php
declare(strict_types=1);

namespace Freento\Mcp\Test\Unit\Model\EntityTool;

use Freento\Mcp\Model\EntityTool\ConditionApplier;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConditionApplierTest extends TestCase
{
    private ConditionApplier $subject;
    private Select|MockObject $selectMock;

    protected function setUp(): void
    {
        $this->subject = new ConditionApplier();
        $this->selectMock = $this->createMock(Select::class);
    }

    // =========================================================================
    // apply() - Simple values
    // =========================================================================

    public function testApplySimpleValueUsesEquals(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status = ?', 'pending');

        $this->subject->apply($this->selectMock, 'main_table.status', 'pending');
    }

    public function testApplySimpleIntegerValue(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.store_id = ?', 1);

        $this->subject->apply($this->selectMock, 'main_table.store_id', 1);
    }

    // =========================================================================
    // apply() - Operator: eq, neq
    // =========================================================================

    public function testApplyEqOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status = ?', 'processing');

        $this->subject->apply($this->selectMock, 'main_table.status', ['eq' => 'processing']);
    }

    public function testApplyNeqOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status != ?', 'closed');

        $this->subject->apply($this->selectMock, 'main_table.status', ['neq' => 'closed']);
    }

    // =========================================================================
    // apply() - Operator: in, nin
    // =========================================================================

    public function testApplyInOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status IN (?)', ['pending', 'processing']);

        $this->subject->apply($this->selectMock, 'main_table.status', ['in' => ['pending', 'processing']]);
    }

    public function testApplyNinOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status NOT IN (?)', ['closed', 'canceled']);

        $this->subject->apply($this->selectMock, 'main_table.status', ['nin' => ['closed', 'canceled']]);
    }

    public function testApplyNotInOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status NOT IN (?)', ['closed']);

        $this->subject->apply($this->selectMock, 'main_table.status', ['not_in' => ['closed']]);
    }

    public function testApplyInWithEmptyArrayDoesNothing(): void
    {
        $this->selectMock->expects($this->never())->method('where');

        $this->subject->apply($this->selectMock, 'main_table.status', ['in' => []]);
    }

    public function testApplyNinWithEmptyArrayDoesNothing(): void
    {
        $this->selectMock->expects($this->never())->method('where');

        $this->subject->apply($this->selectMock, 'main_table.status', ['nin' => []]);
    }

    // =========================================================================
    // apply() - Operator: like, nlike
    // =========================================================================

    public function testApplyLikeOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.email LIKE ?', '%@gmail.com');

        $this->subject->apply($this->selectMock, 'main_table.email', ['like' => '%@gmail.com']);
    }

    public function testApplyNlikeOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.email NOT LIKE ?', '%@test.com');

        $this->subject->apply($this->selectMock, 'main_table.email', ['nlike' => '%@test.com']);
    }

    public function testApplyNotLikeOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.sku NOT LIKE ?', 'TEST%');

        $this->subject->apply($this->selectMock, 'main_table.sku', ['not_like' => 'TEST%']);
    }

    // =========================================================================
    // apply() - Operator: gt, gte, lt, lte
    // =========================================================================

    public function testApplyGtOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.grand_total > ?', 100);

        $this->subject->apply($this->selectMock, 'main_table.grand_total', ['gt' => 100]);
    }

    public function testApplyGteOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.grand_total >= ?', 100);

        $this->subject->apply($this->selectMock, 'main_table.grand_total', ['gte' => 100]);
    }

    public function testApplyLtOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.grand_total < ?', 500);

        $this->subject->apply($this->selectMock, 'main_table.grand_total', ['lt' => 500]);
    }

    public function testApplyLteOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.grand_total <= ?', 500);

        $this->subject->apply($this->selectMock, 'main_table.grand_total', ['lte' => 500]);
    }

    // =========================================================================
    // apply() - Operator: null
    // =========================================================================

    public function testApplyNullOperatorTrue(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.deleted_at IS NULL');

        $this->subject->apply($this->selectMock, 'main_table.deleted_at', ['null' => true]);
    }

    public function testApplyNullOperatorFalse(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.deleted_at IS NOT NULL');

        $this->subject->apply($this->selectMock, 'main_table.deleted_at', ['null' => false]);
    }

    // =========================================================================
    // apply() - Multiple operators
    // =========================================================================

    public function testApplyMultipleOperators(): void
    {
        $this->selectMock->expects($this->exactly(2))
            ->method('where')
            ->willReturnCallback(function ($condition, $value = null) {
                static $calls = [];
                $calls[] = [$condition, $value];

                if (count($calls) === 1) {
                    $this->assertEquals('main_table.grand_total >= ?', $condition);
                    $this->assertEquals(100, $value);
                }
                if (count($calls) === 2) {
                    $this->assertEquals('main_table.grand_total <= ?', $condition);
                    $this->assertEquals(500, $value);
                }

                return $this->selectMock;
            });

        $this->subject->apply($this->selectMock, 'main_table.grand_total', ['gte' => 100, 'lte' => 500]);
    }

    // =========================================================================
    // apply() - Case insensitive operators
    // =========================================================================

    public function testApplyOperatorCaseInsensitive(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status = ?', 'pending');

        $this->subject->apply($this->selectMock, 'main_table.status', ['EQ' => 'pending']);
    }

    // =========================================================================
    // apply() - JSON string decoding
    // =========================================================================

    public function testApplyDecodesJsonObject(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status = ?', 'pending');

        $this->subject->apply($this->selectMock, 'main_table.status', '{"eq": "pending"}');
    }

    public function testApplyDecodesJsonArray(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status IN (?)', ['pending', 'processing']);

        $this->subject->apply($this->selectMock, 'main_table.status', '{"in": ["pending", "processing"]}');
    }

    public function testApplyDoesNotDecodeInvalidJson(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status = ?', '{invalid json}');

        $this->subject->apply($this->selectMock, 'main_table.status', '{invalid json}');
    }

    public function testApplyDoesNotDecodeNonJsonString(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status = ?', 'pending');

        $this->subject->apply($this->selectMock, 'main_table.status', 'pending');
    }

    // =========================================================================
    // applyString() - Auto-wildcard detection
    // =========================================================================

    public function testApplyStringWithWildcardUsesLike(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.email LIKE ?', '%@gmail.com');

        $this->subject->applyString($this->selectMock, 'main_table.email', '%@gmail.com');
    }

    public function testApplyStringWithoutWildcardUsesEquals(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.email = ?', 'test@example.com');

        $this->subject->applyString($this->selectMock, 'main_table.email', 'test@example.com');
    }

    public function testApplyStringWithMiddleWildcard(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.sku LIKE ?', 'ABC%123');

        $this->subject->applyString($this->selectMock, 'main_table.sku', 'ABC%123');
    }

    public function testApplyStringWithExplicitLikeOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.email LIKE ?', '%test%');

        $this->subject->applyString($this->selectMock, 'main_table.email', ['like' => '%test%']);
    }

    public function testApplyStringWithExplicitEqOperator(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.email = ?', 'exact@example.com');

        $this->subject->applyString($this->selectMock, 'main_table.email', ['eq' => 'exact@example.com']);
    }

    // =========================================================================
    // applyDate() - Date normalization
    // =========================================================================

    public function testApplyDateNormalizesGteWithStartOfDay(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.created_at >= ?', '2024-01-01 00:00:00');

        $this->subject->applyDate($this->selectMock, 'main_table.created_at', ['gte' => '2024-01-01']);
    }

    public function testApplyDateNormalizesGtWithStartOfDay(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.created_at > ?', '2024-01-01 00:00:00');

        $this->subject->applyDate($this->selectMock, 'main_table.created_at', ['gt' => '2024-01-01']);
    }

    public function testApplyDateNormalizesEqWithStartOfDay(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.created_at = ?', '2024-01-01 00:00:00');

        $this->subject->applyDate($this->selectMock, 'main_table.created_at', ['eq' => '2024-01-01']);
    }

    public function testApplyDateNormalizesLteWithEndOfDay(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.created_at <= ?', '2024-01-31 23:59:59');

        $this->subject->applyDate($this->selectMock, 'main_table.created_at', ['lte' => '2024-01-31']);
    }

    public function testApplyDateNormalizesLtWithEndOfDay(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.created_at < ?', '2024-01-31 23:59:59');

        $this->subject->applyDate($this->selectMock, 'main_table.created_at', ['lt' => '2024-01-31']);
    }

    public function testApplyDatePreservesExistingTime(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.created_at >= ?', '2024-01-01 12:30:00');

        $this->subject->applyDate($this->selectMock, 'main_table.created_at', ['gte' => '2024-01-01 12:30:00']);
    }

    public function testApplyDateRangeNormalization(): void
    {
        $this->selectMock->expects($this->exactly(2))
            ->method('where')
            ->willReturnCallback(function ($condition, $value = null) {
                static $calls = [];
                $calls[] = [$condition, $value];

                if (count($calls) === 1) {
                    $this->assertEquals('main_table.created_at >= ?', $condition);
                    $this->assertEquals('2024-01-01 00:00:00', $value);
                }
                if (count($calls) === 2) {
                    $this->assertEquals('main_table.created_at <= ?', $condition);
                    $this->assertEquals('2024-01-31 23:59:59', $value);
                }

                return $this->selectMock;
            });

        $this->subject->applyDate($this->selectMock, 'main_table.created_at', [
            'gte' => '2024-01-01',
            'lte' => '2024-01-31'
        ]);
    }

    public function testApplyDateWithSimpleValue(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.created_at = ?', '2024-01-15');

        // Simple value without operators - no normalization
        $this->subject->applyDate($this->selectMock, 'main_table.created_at', '2024-01-15');
    }

    // =========================================================================
    // Edge cases
    // =========================================================================

    public function testApplyUnknownOperatorIsIgnored(): void
    {
        $this->selectMock->expects($this->never())->method('where');

        $this->subject->apply($this->selectMock, 'main_table.status', ['unknown_op' => 'value']);
    }

    public function testApplyWithZeroValue(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.qty = ?', 0);

        $this->subject->apply($this->selectMock, 'main_table.qty', 0);
    }

    public function testApplyWithEmptyString(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.comment = ?', '');

        $this->subject->apply($this->selectMock, 'main_table.comment', '');
    }

    public function testApplyStringWithIntegerValue(): void
    {
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.store_id = ?', 1);

        // Integer value should use equals, not LIKE
        $this->subject->applyString($this->selectMock, 'main_table.store_id', 1);
    }
}
