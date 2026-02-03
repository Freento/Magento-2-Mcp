# Freento MCP for Magento 2 — User Guide

Connect your Magento 2 store to AI assistants like Claude and ChatGPT using the Model Context Protocol (MCP).

## Table of Contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Claude Code](#claude-code)
  - [Claude Desktop](#claude-desktop)
  - [ChatGPT](#chatgpt)
- [Available Tools](#available-tools)
- [Usage Examples](#usage-examples)
- [Filtering & Operators](#filtering--operators)
- [Aggregation & Analytics](#aggregation--analytics)
- [Troubleshooting](#troubleshooting)
- [Security](#security)

## Overview

Freento MCP is a Magento 2 extension that implements the [Model Context Protocol](https://modelcontextprotocol.io/) — an open standard for connecting AI assistants to external data sources. With this extension, you can:

- Query orders, products, customers, and inventory using natural language
- Generate sales reports and analytics on the fly
- Monitor system health (PHP, MySQL, cache, search engine versions)
- Audit admin users and security settings

**How it works:**

```
┌─────────────────┐         ┌─────────────────┐         ┌─────────────────┐
│  AI Assistant   │  HTTP   │  Freento MCP    │         │   Magento 2 /   │
│  (Claude/GPT)   │ ◄─────► │  Server         │ ◄─────► │Server Resources │
└─────────────────┘ JSON-RPC└─────────────────┘         └─────────────────┘
```

The MCP server acts as a secure bridge between AI assistants and your Magento installation, providing access to various store resources including the database, configuration, and other Magento subsystems.

## Requirements

- Magento 2.4.x (Open Source or Commerce)
- PHP 8.1 or higher
- An MCP-compatible AI client (Claude Code, Claude Desktop, or ChatGPT with MCP plugin)

## Installation

### Via Composer (Recommended)

```bash
composer require freento/module-mcp
php bin/magento module:enable Freento_Mcp
php bin/magento setup:upgrade
php bin/magento cache:flush
```

### Manual Installation

1. Download the module and extract to `app/code/Freento/Mcp/`
2. Enable the module:

```bash
php bin/magento module:enable Freento_Mcp
php bin/magento setup:upgrade
php bin/magento cache:flush
```

### Verify Installation

```bash
php bin/magento module:status Freento_Mcp
```

Expected output: `Module is enabled`

## Configuration

### Step 1: Create ACL Role

1. In Magento Admin, go to **System > Freento MCP > ACL Rules**
2. Click **Add New Role**
3. Enter a name (e.g., "AI Assistant")
4. Select which tools the role can access:
   - Sales tools (orders, quotes, credit memos)
   - Catalog tools (products, stock)
   - Customer tools
   - Admin tools
   - System tools
5. **Save** the role

### Step 2: Create OAuth Client

1. Go to **System > Freento MCP > AI MCP Clients**
2. Click **Add New Client**
3. Enter a name (e.g., "Claude Code")
4. Select the ACL Role created in Step 1
5. **Save** the client
6. Copy the **Client ID** and **Client Secret**

### Step 3: Generate Access Token

1. Open the OAuth Client you created
2. Click **Generate OTP** — copy the one-time password (valid 24 hours)
3. Click **Generate Token** — enter the OTP when prompted
4. Copy the generated **Access Token**

### Claude Code

Add to your project's `.mcp.json`:

```json
{
  "mcpServers": {
    "magento": {
      "type": "http",
      "url": "https://your-store.com/freento_mcp/index/index",
      "headers": {
        "Authorization": "Bearer YOUR_ACCESS_TOKEN"
      }
    }
  }
}
```

Then reconnect MCP in Claude Code:

```
/mcp
```

### Claude Desktop

Edit your Claude Desktop config (`~/.config/claude/claude_desktop_config.json` on Linux/Mac or `%APPDATA%\Claude\claude_desktop_config.json` on Windows):

```json
{
  "mcpServers": {
    "magento": {
      "type": "http",
      "url": "https://your-store.com/freento_mcp/index/index",
      "headers": {
        "Authorization": "Bearer YOUR_ACCESS_TOKEN"
      }
    }
  }
}
```

Restart Claude Desktop to apply changes.

### ChatGPT and Other Web Clients

For web-based AI tools that support OAuth 2.0:

1. Register your store's MCP endpoint: `https://your-store.com/freento_mcp/index/index`
2. Enter the **Client ID** and **Client Secret** from Step 2
3. When prompted to authorize, enter the **OTP** generated from the OAuth Client page
4. Complete the OAuth authorization flow

## Available Tools

Each tool supports flexible filtering, sorting, and pagination. Combined with AI, these capabilities become virtually unlimited — the AI can execute multiple queries, cross-reference data, group and aggregate results, and provide intelligent analysis.

**AI can:**
- Run multiple queries across different entities in one conversation
- Filter by any field using operators: `eq`, `neq`, `in`, `like`, `gt`, `gte`, `lt`, `lte`
- Sort and paginate results
- Aggregate with `sum`, `count`, `avg`, `min`, `max`
- Group by field or time period (day, month)
- Combine and analyze data from multiple sources

### Sales Tools

| Tool | Description |
|------|-------------|
| `get_orders` | Query orders with filtering, pagination, and aggregation |
| `get_order_items` | Get order line items (products in orders) |
| `get_quotes` | Query shopping carts (active and abandoned) |
| `get_quote_items` | Get cart line items |
| `get_creditmemos` | Query credit memos |

### Marketing Tools

| Tool | Description |
|------|-------------|
| `get_cart_price_rules` | Query cart price rules |
| `get_coupons` | Query coupons |

### Catalog Tools

| Tool | Description |
|------|-------------|
| `get_products` | Query products with attribute filtering |
| `get_stock_single_stock` | Get inventory/stock levels |

### Customer Tools

| Tool | Description |
|------|-------------|
| `get_customers` | Query customer accounts |

### Admin Tools

| Tool | Description |
|------|-------------|
| `get_admins` | List admin users and their roles |
| `get_locked_admins` | Find locked admin accounts (failed login attempts) |

### System Tools

| Tool | Description |
|------|-------------|
| `get_system_versions` | Get Magento, PHP, MySQL, Redis, OpenSearch versions |

## Usage Examples

Once configured, you can ask your AI assistant questions in natural language:

### Orders & Sales

```
"How many orders were placed last month?"
"Show me the 10 most recent orders"
"Find all orders over $500 that are still processing"
"What's the total revenue by payment method this year?"
"List orders for customer john@example.com"
```

### Products & Inventory

```
"Show me out of stock products"
"Find products with SKU starting with 'ABC'"
"List products with less than 10 items in stock"
"Get all configurable products updated this week"
```

### Customers

```
"How many customers registered this month?"
"Find customer with email jane@example.com"
"List customers in the Wholesale group"
```

### System & Admin

```
"What PHP version is running?"
"Show me all admin users"
"Are there any locked admin accounts?"
"What search engine is configured?"
```

### Analytics & Reports

```
"Revenue by month for the last 12 months"
"Top 10 customers by total order value"
"Average order value by payment method"
"Order count by status"
```

### Advanced AI-Powered Analysis

The real power comes from combining data with AI reasoning. Ask complex business questions and get actionable insights:

**Customer Intelligence:**
```
"Analyze my top 10 customers from the last 6 months. Who are they,
what do they buy, and how can I increase sales?"
```

The AI will retrieve the data and provide analysis like:

> **Mike Johnson** — $4,250 total, 8 orders
> *Profile: Professional drummer buying cymbals and drumsticks every 5-6 weeks*
>
> Recommendation: Set up auto-replenishment for drumsticks, offer early access to new cymbal arrivals, consider a "drummer's loyalty" discount tier.

**Churn Prevention:**
```
"Find customers who were active but haven't ordered in 90 days.
What patterns do you see and how can I win them back?"
```

**Inventory Optimization:**
```
"Analyze sales velocity vs current stock levels.
What should I reorder and what's at risk of becoming dead stock?"
```

**Revenue Opportunities:**
```
"What patterns exist in high-value orders? How can I get more customers
to spend at that level?"
```

**Abandoned Cart Analysis:**
```
"Look at abandoned carts from this week. What are people leaving behind
and what might be causing it?"
```

This transforms your store data into strategic business intelligence — insights that would typically require hours with spreadsheets or a dedicated analyst.

## Filtering & Operators

All list tools support powerful filtering via the `filters` parameter.

### Filter Structure

```json
{
  "filters": {
    "field_name": { "operator": "value" }
  }
}
```

### Available Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `eq` | Equals | `{"status": {"eq": "processing"}}` |
| `neq` | Not equals | `{"status": {"neq": "canceled"}}` |
| `in` | In list | `{"status": {"in": ["processing", "complete"]}}` |
| `nin` | Not in list | `{"status": {"nin": ["canceled", "closed"]}}` |
| `like` | SQL LIKE pattern | `{"email": {"like": "%@gmail.com"}}` |
| `nlike` | SQL NOT LIKE | `{"sku": {"nlike": "TEST%"}}` |
| `gt` | Greater than | `{"grand_total": {"gt": 100}}` |
| `gte` | Greater or equal | `{"qty": {"gte": 10}}` |
| `lt` | Less than | `{"created_at": {"lt": "2024-01-01"}}` |
| `lte` | Less or equal | `{"price": {"lte": 50}}` |

### Combining Filters

Multiple filters are combined with AND logic:

```json
{
  "filters": {
    "status": {"in": ["processing", "pending"]},
    "grand_total": {"gte": 100},
    "created_at": {"gte": "2024-01-01"}
  }
}
```

### Date Filtering

Use `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS` format:

```json
{
  "filters": {
    "created_at": {"gte": "2024-01-01", "lt": "2024-02-01"}
  }
}
```

## Aggregation & Analytics

The `get_orders` tool supports aggregation for analytics:

### Parameters

| Parameter | Values | Description |
|-----------|--------|-------------|
| `function` | `count`, `sum`, `avg`, `min`, `max` | Aggregation function |
| `field` | `grand_total`, `total_qty_ordered`, `total_item_count` | Field to aggregate |
| `group_by` | `status`, `month`, `day`, `customer_email`, `store_id`, `payment_method` | Grouping |

### Examples

**Total order count:**
```json
{"function": "count"}
```

**Revenue by month:**
```json
{
  "function": "sum",
  "field": "grand_total",
  "group_by": "month"
}
```

**Average order value by payment method:**
```json
{
  "function": "avg",
  "field": "grand_total",
  "group_by": "payment_method"
}
```

**Top 10 customers by spending:**
```json
{
  "function": "sum",
  "field": "grand_total",
  "group_by": "customer_email",
  "filters": {
    "status": {"nin": ["canceled", "closed"]}
  },
  "limit": 10
}
```

## Troubleshooting

### Tools not appearing in AI assistant

1. Verify the module is enabled:
   ```bash
   php bin/magento module:status Freento_Mcp
   ```

2. Flush Magento cache:
   ```bash
   php bin/magento cache:flush
   ```

3. Reconnect MCP in your AI client (e.g., `/mcp` in Claude Code)

### "Authentication failed" error

- Verify your access token is correct
- Check the OAuth Client is enabled in Magento Admin
- Regenerate the token if it has expired

### "Access denied" error

The ACL Role lacks required permissions. Edit the ACL Role in **System > Freento MCP > ACL Rules** and grant access to the necessary tools.

### Connection timeout

- Verify your Magento store is accessible from the internet
- Check firewall rules allow incoming connections
- For local development, use a tunnel service like ngrok

### Test the endpoint manually

```bash
curl -X POST https://your-store.com/freento_mcp/index/index \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## Security

### Best Practices

1. **Use HTTPS** — Always use HTTPS in production to encrypt API communications

2. **Minimal Permissions** — Grant only the tools needed for your use case via ACL Roles

3. **Separate Clients** — Create separate OAuth Clients for different users/purposes

4. **Regular Audits** — Periodically review active clients and disable unused ones

5. **Token Rotation** — Regenerate access tokens periodically

### Token Security

- Never commit tokens to version control
- Use environment variables or secure secret management
- Rotate tokens periodically
- Revoke tokens immediately if compromised

## Support

**Contact:** [https://freento.com/contact](https://freento.com/contact)

## License

MIT License — see [LICENSE](LICENSE) for details.
