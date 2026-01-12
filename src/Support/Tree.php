<?php

declare(strict_types=1);

namespace XditnModule\Support;

class Tree
{
    protected static string $pk = 'id';

    public static function setPk(string $pk): Tree
    {
        self::$pk = $pk;

        return new self();
    }

    /**
     * return done.
     */
    public static function done(array $items, int $pid = 0, string $pidField = 'parent_id', string $child = 'children', $id = 'id'): array
    {
        self::$pk = $id;

        $tree = [];

        foreach ($items as $item) {
            if ($item[$pidField] == $pid) {
                $children = self::done($items, $item[self::$pk], $pidField, $child, self::$pk);

                if (count($children)) {
                    $item[$child] = $children;
                }

                $tree[] = $item;
            }
        }

        return $tree;
    }
}
