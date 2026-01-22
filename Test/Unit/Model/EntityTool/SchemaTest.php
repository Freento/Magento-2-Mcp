<?php
declare(strict_types=1);

namespace Freento\Mcp\Test\Unit\Model\EntityTool;

use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    /**
     * Test basic getters return constructor values
     */
    public function testBasicGetters(): void
    {
        $schema = new Schema(
            entity: 'order',
            table: 'sales_order',
            fields: [],
            tableAlias: 'so',
            defaultLimit: 25,
            maxLimit: 100
        );

        $this->assertEquals('order', $schema->getEntity());
        $this->assertEquals('sales_order', $schema->getTable());
        $this->assertEquals('so', $schema->getTableAlias());
        $this->assertEquals(25, $schema->getDefaultLimit());
        $this->assertEquals(100, $schema->getMaxLimit());
    }

    /**
     * Test default values for optional parameters
     */
    public function testDefaultValues(): void
    {
        $schema = new Schema(
            entity: 'product',
            table: 'catalog_product_entity',
            fields: []
        );

        $this->assertEquals('main_table', $schema->getTableAlias());
        $this->assertEquals(50, $schema->getDefaultLimit());
        $this->assertEquals(200, $schema->getMaxLimit());
    }

    /**
     * Test getFields returns all fields
     */
    public function testGetFields(): void
    {
        $field1 = new Field(name: 'id', type: 'integer');
        $field2 = new Field(name: 'name', type: 'string');

        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [$field1, $field2]
        );

        $fields = $schema->getFields();
        $this->assertCount(2, $fields);
        $this->assertSame($field1, $fields[0]);
        $this->assertSame($field2, $fields[1]);
    }

    /**
     * Test getField returns field by name
     */
    public function testGetFieldByName(): void
    {
        $field = new Field(name: 'status', type: 'string');
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [$field]
        );

        $this->assertSame($field, $schema->getField('status'));
        $this->assertNull($schema->getField('nonexistent'));
    }

    /**
     * Test hasField checks field existence
     */
    public function testHasField(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [new Field(name: 'id')]
        );

        $this->assertTrue($schema->hasField('id'));
        $this->assertFalse($schema->hasField('unknown'));
    }

    /**
     * Test normalizeLimit with various inputs
     *
     * @dataProvider limitDataProvider
     */
    public function testNormalizeLimit(int $input, int $expected, int $default, int $max): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [],
            defaultLimit: $default,
            maxLimit: $max
        );

        $this->assertEquals($expected, $schema->normalizeLimit($input));
    }

    public static function limitDataProvider(): array
    {
        return [
            'normal value' => [50, 50, 20, 100],
            'zero returns default' => [0, 20, 20, 100],
            'negative returns default' => [-5, 20, 20, 100],
            'exceeds max returns max' => [500, 100, 20, 100],
            'equals max' => [100, 100, 20, 100],
            'equals default' => [20, 20, 20, 100],
            'one is valid' => [1, 1, 20, 100],
        ];
    }

    /**
     * Test getSelectColumns with standard columns
     */
    public function testGetSelectColumnsStandard(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'id', column: true),
                new Field(name: 'name', column: true),
            ],
            tableAlias: 'main'
        );

        $columns = $schema->getSelectColumns();

        $this->assertEquals(['main.id', 'main.name'], $columns);
    }

    /**
     * Test getSelectColumns with custom column expressions
     */
    public function testGetSelectColumnsCustom(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'id', column: true),
                new Field(name: 'customer_name', column: 'customer.firstname'),
            ],
            tableAlias: 'main'
        );

        $columns = $schema->getSelectColumns();

        $this->assertContains('main.id', $columns);
        $this->assertArrayHasKey('customer_name', $columns);
        $this->assertEquals('customer.firstname', $columns['customer_name']);
    }

    /**
     * Test getSelectColumns excludes fields with column: false
     */
    public function testGetSelectColumnsExcludesNoColumn(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'id', column: true),
                new Field(name: 'computed', column: false),
            ],
            tableAlias: 'main'
        );

        $columns = $schema->getSelectColumns();

        $this->assertCount(1, $columns);
        $this->assertEquals(['main.id'], $columns);
    }

    /**
     * Test getFilterableFields returns only filterable fields
     */
    public function testGetFilterableFields(): void
    {
        $filterable = new Field(name: 'status', filter: true);
        $notFilterable = new Field(name: 'internal', filter: false);

        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [$filterable, $notFilterable]
        );

        $result = $schema->getFilterableFields();

        $this->assertCount(1, $result);
        $this->assertEquals('status', reset($result)->getName());
    }

    /**
     * Test getSortableFieldNames returns sortable field names
     */
    public function testGetSortableFieldNames(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'id', sortable: true),
                new Field(name: 'name', sortable: true),
                new Field(name: 'notes', sortable: false),
                new Field(name: 'computed', column: false, sortable: true), // no column = not sortable
            ]
        );

        $sortable = $schema->getSortableFieldNames();

        $this->assertEquals(['id', 'name'], $sortable);
    }

    /**
     * Test getAggregateFields returns fields with allowAggregate
     */
    public function testGetAggregateFields(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'id'),
                new Field(name: 'total', allowAggregate: true),
                new Field(name: 'qty', allowAggregate: true),
                new Field(name: 'status'),
            ]
        );

        $result = $schema->getAggregateFields();

        $this->assertCount(2, $result);
        $names = array_map(fn($f) => $f->getName(), $result);
        $this->assertContains('total', $names);
        $this->assertContains('qty', $names);
    }

    /**
     * Test getGroupByOptions returns simple field names
     */
    public function testGetGroupByOptionsSimple(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'id'),
                new Field(name: 'status', allowGroupBy: true),
                new Field(name: 'store_id', allowGroupBy: true),
            ]
        );

        $options = $schema->getGroupByOptions();

        $this->assertEquals(['status', 'store_id'], $options);
    }

    /**
     * Test getGroupByOptions with time-based options
     */
    public function testGetGroupByOptionsTimeBased(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'status', allowGroupBy: true),
                new Field(
                    name: 'created_at',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day']
                ),
            ]
        );

        $options = $schema->getGroupByOptions();

        $this->assertEquals(['status', 'month', 'day'], $options);
    }

    /**
     * Test hasGroupByOption validates options
     */
    public function testHasGroupByOption(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'status', allowGroupBy: true),
                new Field(
                    name: 'created_at',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day']
                ),
            ]
        );

        $this->assertTrue($schema->hasGroupByOption('status'));
        $this->assertTrue($schema->hasGroupByOption('month'));
        $this->assertTrue($schema->hasGroupByOption('day'));
        $this->assertFalse($schema->hasGroupByOption('year'));
        $this->assertFalse($schema->hasGroupByOption('created_at')); // has options, not direct
    }

    /**
     * Test getGroupByField for simple field
     */
    public function testGetGroupByFieldSimple(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'status', allowGroupBy: true),
            ],
            tableAlias: 'main'
        );

        $this->assertEquals('main.status', $schema->getGroupByField('status'));
    }

    /**
     * Test getGroupByField for custom column
     */
    public function testGetGroupByFieldCustomColumn(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'method', column: 'payment.method', allowGroupBy: true),
            ],
            tableAlias: 'main'
        );

        $this->assertEquals('payment.method', $schema->getGroupByField('method'));
    }

    /**
     * Test getGroupByField for time-based grouping
     */
    public function testGetGroupByFieldTimeBased(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(
                    name: 'created_at',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day']
                ),
            ],
            tableAlias: 'orders'
        );

        $this->assertEquals('orders.created_at', $schema->getGroupByField('month'));
        $this->assertEquals('orders.created_at', $schema->getGroupByField('day'));
    }

    /**
     * Test getGroupByField returns null for invalid option
     */
    public function testGetGroupByFieldInvalid(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'status', allowGroupBy: true),
            ]
        );

        $this->assertNull($schema->getGroupByField('invalid'));
    }

    /**
     * Test getGroupByType for time-based grouping
     */
    public function testGetGroupByType(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'status', allowGroupBy: true),
                new Field(
                    name: 'created_at',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day']
                ),
            ]
        );

        $this->assertEquals('month', $schema->getGroupByType('month'));
        $this->assertEquals('day', $schema->getGroupByType('day'));
        $this->assertNull($schema->getGroupByType('status')); // simple, not time-based
    }

    /**
     * Test getCurrencyFieldNames returns currency aggregate fields
     */
    public function testGetCurrencyFieldNames(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'id', type: 'integer'),
                new Field(name: 'grand_total', type: 'currency', allowAggregate: true),
                new Field(name: 'subtotal', type: 'currency', allowAggregate: true),
                new Field(name: 'qty', type: 'integer', allowAggregate: true),
                new Field(name: 'discount', type: 'currency'), // not aggregatable
            ]
        );

        $result = $schema->getCurrencyFieldNames();

        $this->assertEquals(['grand_total', 'subtotal'], $result);
    }

    /**
     * Test getSortableAggregateFields
     */
    public function testGetSortableAggregateFields(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'id'),
                new Field(name: 'total', allowAggregate: true, sortable: true),
                new Field(name: 'qty', allowAggregate: true, sortable: false),
            ]
        );

        $result = $schema->getSortableAggregateFields();

        $this->assertCount(1, $result);
        $this->assertEquals('total', reset($result)->getName());
    }

    /**
     * Test getSortableGroupByOptions
     */
    public function testGetSortableGroupByOptions(): void
    {
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [
                new Field(name: 'status', allowGroupBy: true, sortable: true),
                new Field(name: 'region', allowGroupBy: true, sortable: false),
                new Field(
                    name: 'created_at',
                    allowGroupBy: true,
                    sortable: true,
                    groupByOptions: ['month', 'day']
                ),
            ]
        );

        $options = $schema->getSortableGroupByOptions();

        $this->assertEquals(['status', 'month', 'day'], $options);
    }

    /**
     * Test field lookup is O(1) by checking same instance returned
     */
    public function testFieldLookupReturnsSameInstance(): void
    {
        $field = new Field(name: 'test_field');
        $schema = new Schema(
            entity: 'test',
            table: 'test_table',
            fields: [$field]
        );

        // Multiple lookups should return same instance
        $this->assertSame($field, $schema->getField('test_field'));
        $this->assertSame($field, $schema->getField('test_field'));
    }
}
