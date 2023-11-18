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
use PhpMyAdmin\MoTranslator\Translator;

class Translate
{
    static string $domain  = 'messages';
    static string $codeset = 'UTF-8';

    public static function setupFunctions(\Pimple\Container $di, $locale): Translator
    {
        if (empty($locale)) {
            //We are using the standard PHP Exception here rather than our custom one as ours requires translations to be setup, which we cannot do without the locale being defined.
            throw new Exception("Unable to setup FOSSBilling translation functionality, locale was undefined.");
        }

        @putenv('LANG=' . $locale . '.' . self::$codeset);
        @putenv('LANGUAGE=' . $locale . '.' . self::$codeset);

        $parser = new MoParser(PATH_LANGS . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES'.  DIRECTORY_SEPARATOR . 'messages.mo');
        $cache = new TranslateCache($parser, $di['cache'], $locale);
        return new Translator($cache);
    }
}
