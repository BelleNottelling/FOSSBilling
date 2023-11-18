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

use PhpMyAdmin\MoTranslator\MoParser;
use PhpMyAdmin\MoTranslator\Cache\CacheInterface;

final class TranslateCache implements CacheInterface
{
    private string $loadKey = 'LOADED';
    private $cachePool;
    private MoParser $parser;
    private string $locale;

    public function __construct(MoParser $parser, $cache, string $locale)
    {
        $this->cachePool = $cache;
        $this->parser = $parser;
        $this->locale = $locale;
        $this->parseIntoCache();
    }

    public function get(string $msgid): string
    {
        $org = $msgid;
        $this->prependPrefix($msgid);
        $cacheItem = $this->cachePool->getItem($msgid);

        if (!$cacheItem->isHit()) {
            return $org;
        }

        return $cacheItem->get();
    }

    public function set(string $msgid, string $msgstr): void
    {
        $this->prependPrefix($msgid);
        $cacheItem = $this->cachePool->getItem($msgid);
        $cacheItem->set($msgstr);
        $this->cachePool->save($cacheItem);
    }

    public function has(string $msgid): bool
    {
        $this->prependPrefix($msgid);
        return $this->cachePool->hasItem($msgid);
    }

    public function setAll(array $translations): void
    {
        foreach ($translations as $msgid => $msgstr) {
            $cacheItem = $this->cachePool->getItem($msgid);
            $cacheItem->set($msgstr);
            $this->cachePool->saveDeferred($cacheItem);
        }
        $this->cachePool->commit();
    }

    public function parseIntoCache()
    {
        if ($this->has($this->loadKey)) {
            return;
        }

        $this->parser->parseIntoCache($this);
        $this->set($this->loadKey, '1');
    }

    /**
     * Modifies a string to attach a prefix to it. Used to prevent string conflicts.
     * 
     * @param mixed $string 
     * @return void 
     */
    private function prependPrefix(&$string)
    {
        $string = 'mo_' . $this->locale . "_$string";
    }
}
