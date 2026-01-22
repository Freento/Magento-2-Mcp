<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\Protocol;

use Freento\Mcp\Exception\ParseErrorException;

class JsonRpcParser
{
    /**
     * @throws ParseErrorException
     */
    public function parse(string $jsonRpcRequest): array
    {
        $data = json_decode($jsonRpcRequest, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParseErrorException('Invalid JSON');
        }

        return [
            'id' => $data['id'] ?? null,
            'method' => $data['method'] ?? null,
            'params' => $data['params'] ?? []
        ];
    }
}
