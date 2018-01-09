<?php

namespace josegonzalez\Queuesadilla\Utility;

use InvalidArgumentException;

trait DsnParserTrait
{

    public function parseDsn($dsn)
    {
        if (empty($dsn)) {
            return [];
        }
        if (!is_string($dsn)) {
            throw new InvalidArgumentException('Only strings can be passed to parseDsn');
        }
        $scheme = null;
        if (preg_match("/^([\w\\\]+)/", $dsn, $matches)) {
            $scheme = $matches[1];
            $dsn = preg_replace("/^([\w\\\]+)/", 'file', $dsn);
        }
        $parsed = parse_url($dsn);
        if ($parsed === false) {
            return [];
        }
        $parsed['scheme'] = $scheme;
        $query = '';
        if (isset($parsed['query'])) {
            $query = $parsed['query'];
            unset($parsed['query']);
        }

        if (!empty($parsed['path'])) {
            $parsed['database'] = substr($parsed['path'], 1);
            unset($parsed['path']);
        }

        $stringMap = [
            'true' => true,
            'false' => false,
            'null' => null,
        ];

        parse_str($query, $queryArgs);
        foreach ($queryArgs as $key => $value) {
            if (isset($stringMap[$value])) {
                $queryArgs[$key] = $stringMap[$value];
            }
        }

        $parsed = $queryArgs + $parsed;

        return $parsed;
    }
}
