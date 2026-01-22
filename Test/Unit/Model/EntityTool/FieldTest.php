<?php
declare(strict_types=1);

namespace Freento\Mcp\Test\Unit\Model\EntityTool;

use Freento\Mcp\Model\EntityTool\Field;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    /**
     * Test basic getters with all parameters
     */
    public function testConstructorWithAllParameters(): void
    {
        $field = new Field(
            name: 'grand_total',
            type: 'currency',
            column: 'orders.grand_total',
            filter: true,
            sortable: true,
            description: 'Order total amount',
            allowAggregate: true,
            allowGroupBy: true,
            groupByOptions: ['month', 'day']
        );

        $this->assertEquals('grand_total', $field->getName());
        $this->assertEquals('currency', $field->getType());
        $this->assertEquals('orders.grand_total', $field->getColumn());
        $this->assertTrue($field->isFilterable());
        $this->assertTrue($field->isSortable());
        $this->assertEquals('Order total amount', $field->getDescription());
        $this->assertTrue($field->allowsAggregate());
        $this->assertTrue($field->allowsGroupBy());
        $this->assertEquals(['month', 'day'], $field->getGroupByOptions());
    }

    /**
     * Test default values
     */
    public function testDefaultValues(): void
    {
        $field = new Field(name: 'status');

        $this->assertEquals('status', $field->getName());
        $this->assertEquals('string', $field->getType());
        $this->assertTrue($field->getColumn()); // default: true
        $this->assertTrue($field->isFilterable());
        $this->assertTrue($field->isSortable());
        $this->assertNull($field->getDescription());
        $this->assertFalse($field->allowsAggregate());
        $this->assertFalse($field->allowsGroupBy());
        $this->assertEquals([], $field->getGroupByOptions());
    }

    /**
     * Test hasColumn with different column values
     *
     * @dataProvider columnDataProvider
     */
    public function testHasColumn(bool|string $column, bool $expected): void
    {
        $field = new Field(name: 'test', column: $column);

        $this->assertEquals($expected, $field->hasColumn());
    }

    public static function columnDataProvider(): array
    {
        return [
            'true has column' => [true, true],
            'false has no column' => [false, false],
            'string has column' => ['other.column', true],
        ];
    }

    /**
     * Test getSelectColumn with column: true
     */
    public function testGetSelectColumnDefault(): void
    {
        $field = new Field(name: 'status', column: true);

        $this->assertEquals('main.status', $field->getSelectColumn('main'));
        $this->assertEquals('orders.status', $field->getSelectColumn('orders'));
    }

    /**
     * Test getSelectColumn with custom column string
     */
    public function testGetSelectColumnCustom(): void
    {
        $field = new Field(name: 'payment_method', column: 'payment.method');

        $this->assertEquals('payment.method', $field->getSelectColumn('main'));
        $this->assertEquals('payment.method', $field->getSelectColumn('any_alias'));
    }

    /**
     * Test getSelectColumn with column: false returns null
     */
    public function testGetSelectColumnNoColumn(): void
    {
        $field = new Field(name: 'computed', column: false);

        $this->assertNull($field->getSelectColumn('main'));
    }

    /**
     * Test getFilterColumn with default column
     */
    public function testGetFilterColumnDefault(): void
    {
        $field = new Field(name: 'status', column: true);

        $this->assertEquals('main.status', $field->getFilterColumn('main'));
    }

    /**
     * Test getFilterColumn with custom column
     */
    public function testGetFilterColumnCustom(): void
    {
        $field = new Field(name: 'method', column: 'payment.method');

        $this->assertEquals('payment.method', $field->getFilterColumn('main'));
    }

    /**
     * Test getFilterColumn with column: false still returns expression
     */
    public function testGetFilterColumnNoColumn(): void
    {
        $field = new Field(name: 'virtual', column: false);

        // Even with column: false, filter column uses table.name
        $this->assertEquals('main.virtual', $field->getFilterColumn('main'));
    }

    /**
     * Test isSortable requires column
     */
    public function testIsSortableRequiresColumn(): void
    {
        $withColumn = new Field(name: 'status', column: true, sortable: true);
        $noColumn = new Field(name: 'computed', column: false, sortable: true);
        $notSortable = new Field(name: 'notes', column: true, sortable: false);

        $this->assertTrue($withColumn->isSortable());
        $this->assertFalse($noColumn->isSortable()); // no column = not sortable
        $this->assertFalse($notSortable->isSortable());
    }

    /**
     * Test isFilterable is independent of column
     */
    public function testIsFilterableIndependentOfColumn(): void
    {
        $withColumn = new Field(name: 'status', column: true, filter: true);
        $noColumn = new Field(name: 'computed', column: false, filter: true);
        $notFilterable = new Field(name: 'internal', filter: false);

        $this->assertTrue($withColumn->isFilterable());
        $this->assertTrue($noColumn->isFilterable()); // filter works without column
        $this->assertFalse($notFilterable->isFilterable());
    }

    /**
     * Test hasGroupByOptions
     */
    public function testHasGroupByOptions(): void
    {
        $withOptions = new Field(
            name: 'created_at',
            groupByOptions: ['month', 'day']
        );
        $withoutOptions = new Field(name: 'status');
        $emptyOptions = new Field(name: 'test', groupByOptions: []);

        $this->assertTrue($withOptions->hasGroupByOptions());
        $this->assertFalse($withoutOptions->hasGroupByOptions());
        $this->assertFalse($emptyOptions->hasGroupByOptions());
    }

    /**
     * Test field types
     *
     * @dataProvider typeDataProvider
     */
    public function testFieldTypes(string $type): void
    {
        $field = new Field(name: 'test', type: $type);

        $this->assertEquals($type, $field->getType());
    }

    public static function typeDataProvider(): array
    {
        return [
            'string' => ['string'],
            'integer' => ['integer'],
            'numeric' => ['numeric'],
            'currency' => ['currency'],
            'date' => ['date'],
        ];
    }

    /**
     * Test aggregate field configuration
     */
    public function testAggregateField(): void
    {
        $aggregatable = new Field(
            name: 'grand_total',
            type: 'currency',
            allowAggregate: true
        );
        $notAggregatable = new Field(name: 'status');

        $this->assertTrue($aggregatable->allowsAggregate());
        $this->assertFalse($notAggregatable->allowsAggregate());
    }

    /**
     * Test group by field configuration
     */
    public function testGroupByField(): void
    {
        $simpleGroupBy = new Field(
            name: 'status',
            allowGroupBy: true
        );
        $timeGroupBy = new Field(
            name: 'created_at',
            type: 'date',
            allowGroupBy: true,
            groupByOptions: ['month', 'day']
        );
        $notGroupable = new Field(name: 'notes');

        $this->assertTrue($simpleGroupBy->allowsGroupBy());
        $this->assertFalse($simpleGroupBy->hasGroupByOptions());

        $this->assertTrue($timeGroupBy->allowsGroupBy());
        $this->assertTrue($timeGroupBy->hasGroupByOptions());
        $this->assertEquals(['month', 'day'], $timeGroupBy->getGroupByOptions());

        $this->assertFalse($notGroupable->allowsGroupBy());
    }

    /**
     * Test typical order field configuration
     */
    public function testTypicalOrderField(): void
    {
        $field = new Field(
            name: 'grand_total',
            type: 'currency',
            description: 'Order grand total amount',
            allowAggregate: true
        );

        $this->assertEquals('grand_total', $field->getName());
        $this->assertEquals('currency', $field->getType());
        $this->assertEquals('Order grand total amount', $field->getDescription());
        $this->assertTrue($field->allowsAggregate());
        $this->assertTrue($field->hasColumn());
        $this->assertTrue($field->isSortable());
        $this->assertTrue($field->isFilterable());
    }

    /**
     * Test typical joined field configuration
     */
    public function testTypicalJoinedField(): void
    {
        $field = new Field(
            name: 'payment_method',
            column: 'payment.method',
            sortable: false,
            description: 'Payment method code'
        );

        $this->assertEquals('payment_method', $field->getName());
        $this->assertEquals('payment.method', $field->getColumn());
        $this->assertEquals('payment.method', $field->getSelectColumn('ignored'));
        $this->assertEquals('payment.method', $field->getFilterColumn('ignored'));
        $this->assertFalse($field->isSortable());
    }

    /**
     * Test typical date field with time-based grouping
     */
    public function testTypicalDateField(): void
    {
        $field = new Field(
            name: 'created_at',
            type: 'date',
            description: 'Order creation date',
            allowGroupBy: true,
            groupByOptions: ['month', 'day']
        );

        $this->assertEquals('date', $field->getType());
        $this->assertTrue($field->allowsGroupBy());
        $this->assertTrue($field->hasGroupByOptions());
        $this->assertContains('month', $field->getGroupByOptions());
        $this->assertContains('day', $field->getGroupByOptions());
    }

    /**
     * Test computed/virtual field with no database column
     */
    public function testVirtualField(): void
    {
        $field = new Field(
            name: 'full_name',
            column: false,
            sortable: true, // will be false because no column
            filter: true
        );

        $this->assertFalse($field->hasColumn());
        $this->assertNull($field->getSelectColumn('main'));
        $this->assertFalse($field->isSortable()); // no column = not sortable
        $this->assertTrue($field->isFilterable()); // filtering can work via custom logic
    }
}
