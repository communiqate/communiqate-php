<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces;

use Generator;
use UnexpectedValueException;

enum Pagination
{
    case Page;
    case Cursor;

    public function items(array $query, callable $fetch): Generator
    {
        $seen = [];
        do {
            $page = $fetch($query);
            if (!is_array($page['data'] ?? null) || !array_key_exists('next', $page['links'] ?? [])) {
                throw new UnexpectedValueException('Invalid pagination envelope.');
            }
            foreach ($page['data'] as $row) {
                yield $row;
            }
            if ($page['links']['next'] === null) {
                return;
            }
            if ($this === self::Page) {
                $current = $page['meta']['current_page'] ?? null;
                if (!is_int($current) || $current !== (int) ($query['page'] ?? 1)) {
                    throw new UnexpectedValueException('Unexpected current page.');
                }
                $query['page'] = $current + 1;
            } else {
                $next = $page['meta']['next_cursor'] ?? null;
                if (!is_string($next) || $next === '' || isset($seen[$next]) || $next === ($query['cursor'] ?? null)) {
                    throw new UnexpectedValueException('Missing or repeated next cursor.');
                }
                $seen[$next] = true;
                $query['cursor'] = $next;
            }
        } while (true);
    }
}
