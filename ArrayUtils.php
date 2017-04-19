<?php

namespace GeoSocio\Core\Utils;

use Doctrine\Common\Collections\Collection;

/**
 * Array Trait
 */
class ArrayUtils
{

    /**
     * Search through a colleciton and return the first instance.
     *
     * @param iterable $collection
     * @param callable $callback
     */
    public static function search(iterable $collection, callable $callback)
    {
        $collection = $collection instanceof Collection ? $collection->toArray() : $collection;
        $item = reset($collection);
        while ($item !== false) {
            if ($callback($item)) {
                return $item;
            };
            $item = next($collection);
        }

        return null;
    }
}
