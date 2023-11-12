<?php

declare(strict_types=1);
/**
 * Copyright 2022-2023 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace FOSSBilling;

use Fiber;

/**
 * This class defines FOSSBilling's specific implementation for fibers to allow multiple tasks to be performed at once.
 * This class implements fiber allocation to help prevent issues from fibers creating additional fibers.
 * Use responsibly as this can easily speed up functionality or severly impact the server's performance.
 * 
 * @package FOSSBilling
 */
class Fibers
{
    /**
     * Handles the allocation of fibers
     * 
     * @param int $requested (optional) The percentage of available fibers to allocate (0 to 100). Defaults to 50% if not specified or if 0% is requested.
     * @return int The number of fibers that have been allocated.
     */
    private static function getAllocation(int $requested = 0): int
    {
        global $fiberUsed;
        $fiberUsed ??= 0;
        $fiberLimit = intval(defined('FIBER_LIMIT') ? FIBER_LIMIT : 1);

        if (!$requested) {
            $requested = 50;
        }

        if ($requested > 100) {
            $requested = 100;
        }

        $free = $fiberLimit - $fiberUsed;
        if ($free <= 0) {
            // We have used up all of our fibers, so only allow a single one to be created (mimicking normal behavior without fibers)
            return 1;
        }

        $toAllocate = intval(ceil($free * ($requested / 100)));
        $fiberUsed += $toAllocate;
        return $toAllocate;
    }

    /**
     * Frees up allocated fibers
     * 
     * @param int $returned The number of fibers that are being returned
     */
    private static function returnAllocation(int $returned = 0): void
    {
        global $fiberUsed;

        if ($fiberUsed - $returned < 0) {
            trigger_error('Something has gone wrong, we have allocated too many fibers.', E_WARNING);
            $fiberUsed = 0;
        } else {
            $fiberUsed - $returned;
        }
    }

    /**
     * An implementation of PHP's `foreach` function which splits the work across fibers.
     * This is only suitable for when you do not need to handle the output of your closure in any way.
     * Information is chunked between the fibers and each fiber then passes each item to your closure individually.
     * Your closure is expected to recieve the item as it's first parameter. 
     * 
     * @param array $array 
     * @param callable $closure The callable object to pass 
     * @param int $maxFiberPercent The maximum percentage of the available fibers to allocate to this task. If
     * @param int $pollRate How frequently to poll a given fiber to check it's status in miliseconds.
     *                  Make this too short and you will slow down the server through interrupts. If you make it too long, your code can get stuck waiting while nothing happens as this function cannot exit until it polls the fibers and validates they have terminated.
     *                  The default value for this is 100ms, meaning the fibers will be polled 10 times per second.
     */
    public static function fiberForEach(array $array, callable $closure, int $maxFiberPercent = 0, int $pollRate = 100): void
    {
        $allocated = self::getAllocation($maxFiberPercent);
        $fibers = [];
        $queueLength = count($array);
        $chunkPerFiber = intval(ceil($queueLength / $allocated));

        if (DEBUG) {
            error_log("Allocated fibers: " . $allocated);
        }

        // Create the number of fibers we've allocated
        for ($i = 0; $i - 1 < $allocated - 1; $i++) {
            $start = ($i) * $chunkPerFiber;
            $items = array_slice($array, $start, $chunkPerFiber);

            if (DEBUG) {
                error_log("Creating fiber #$i start: $start end: " . ($start + $chunkPerFiber) . " total to do: $queueLength");
            }

            $fibers[] = new Fiber(function () use ($items, $closure): void {
                foreach ($items as $item) {
                    $closure($item);
                }
            });
        }

        foreach ($fibers as $fiber) {
            $fiber->start();
        }

        $fibersComplete = 0;
        while ($fibersComplete < $allocated) {
            usleep($pollRate);
            foreach ($fibers as $key => $fiber) {
                if ($fiber->isSuspended()) {
                    $fiber->resume();
                } else if ($fiber->isTerminated()) {
                    if (DEBUG) {
                        error_log("Fiber #$key is now complete.");
                    }
                    unset($fibers[$key]);
                    $fibersComplete++;
                }
            }
        }

        self::returnAllocation($allocated);
    }
}
