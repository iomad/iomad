<?php
// This file is part of filter_multilang2 - https://moodle.org/
//
// filter_multilang2 is free software: you can redistribute it and/or
// modify it under the terms of the GNU General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// filter_multilang2 is distributed in the hope that it will be
// useful, but WITHOUT ANY WARRANTY; without even the implied warranty
// of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with filter_multilang2.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Actual test cases for the unit tests.
 *
 * @package    filter_multilang2
 * @category   test
 * @copyright  2022 onwards Iñaki Arenaza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the actual test cases for the unit tests.
 *
 * @return array of test cases
 */
function multilang2_actual_test_cases(): array {
    return [
        [
            'No multilang tags',
            'No multilang tags',
            'es',
        ],
        [
            'Todo el texto está en español',
            '{mlang es}Todo el texto está en español{mlang}',
            'es',
        ],
        [
            'Todo el texto está en español',
            '{MLANG es}Todo el texto está en español{MLANG}',
            'es',
        ],
        [
            'Todo el texto está en español',
            '{MLANG ES}Todo el texto está en español{MLANG}',
            'es',
        ],
        [
            'Todo el texto está en español',
            '{MlAnG Es}Todo el texto está en español{MlAnG}',
            'es',
        ],
        [
            'Some non-filtered content plus some content in Spanish (mejor dicho, en español)',
            'Some non-filtered content {mlang es}plus some content in Spanish (mejor dicho, en español){mlang}',
            'es',
        ],
        [
            'Zerbait euskeraz',
            '{mlang es}Algo en español{mlang}{mlang eu}Zerbait euskeraz{mlang}',
            'eu',
        ],
        [
            'Non-filtered {begin}EuskerazNon-filtered{end}',
            'Non-filtered {begin}{mlang es}En español{mlang}{mlang eu}Euskeraz{mlang}Non-filtered{end}',
            'eu',
        ],
        [
            '{mlang}Bad filter syntax{mlang}',
            '{mlang}Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang-es}Bad filter syntax{mlang}',
            '{mlang-es}Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang_es}Bad filter syntax{mlang}',
            '{mlang_es}Bad filter syntax{mlang}',
            'es',
        ],
        [
            '',
            '{mlang _es}Este texto está en español{mlang}',
            'es',
        ],
        [
            '',
            '{mlang -es}Este texto está en español{mlang}',
            'es',
        ],
        [
            '{mlang}Bad filter syntax{mlang}Algo de español',
            '{mlang}Bad filter syntax{mlang}{mlang es}Algo de español{mlang}',
            'es',
        ],
        [
            'Before {mlang}Bad filter syntax{mlang}Algo de español After',
            'Before {mlang}Bad filter syntax{mlang}{mlang es}Algo de español{mlang} After',
            'es',
        ],
        [
            'Before  After',
            'Before {mlang non-existent-language}Some content{mlang} After',
            'es',
        ],
        [
            'Before Some content After',
            'Before {mlang en_us}Some content{mlang} After',
            'en_us', ['en_us' => 'en'],
        ],
        [
            'Before Some content After',
            'Before {mlang en-us}Some content{mlang} After',
            'en_us', ['en_us' => 'en'],
        ],
        [
            'Before  After',
            'Before {mlang fr}French{mlang}{mlang it}Italian{mlang}{mlang de}Deutsch{mlang} After',
            'en',
        ],
        [
            'Before България
             Middle България
             After',
            'Before {mlang fr}Français{mlang}{mlang it}Italiano{mlang}{mlang de}Deutsch{mlang}{mlang other}България{mlang}
             Middle {mlang other}България{mlang}{mlang it}Italiano{mlang}{mlang de}Deutsch{mlang}{mlang fr}Français{mlang}
             After',
            'en',
        ],
        [
            'no other language declared',
            '{mlang de}Deutsch {mlang}no other language declared',
            'en',
        ],
        [
            'Other language',
            '{mlang other}Other language{mlang}',
            'en',
        ],
        [
            'Other language Middle Other language',
            '{mlang other}Other language{mlang} Middle {mlang de}Deutsch{mlang}{mlang other}Other language{mlang}',
            'en',
        ],
        [
            'Hello!
             This text is common for all languages because it is outside of all lang blocks.
             Bye!',
            '{mlang other}Hello!{mlang}{mlang es,es_mx}¡Hola!{mlang}
             This text is common for all languages because it is outside of all lang blocks.
             {mlang other}Bye!{mlang}{mlang it}Ciao!{mlang}',
            'fr',
        ],
        [
            '¡Hola!
             This text is common for all languages because it is outside of all lang blocks.
             ',
            '{mlang other}Hello!{mlang}{mlang es}¡Hola!{mlang}
             This text is common for all languages because it is outside of all lang blocks.
             {mlang other}Bye!{mlang}{mlang it}Ciao!{mlang}',
            'es_mx',
            ['es_mx' => 'es'],
        ],
        [
            '
             This text is common for all languages because it is outside of all lang blocks.
             Ciao!',
            '{mlang other}Hello!{mlang}{mlang es,es_mx}¡Hola!{mlang}
             This text is common for all languages because it is outside of all lang blocks.
             {mlang other}Bye!{mlang}{mlang it}Ciao!{mlang}',
            'it',
        ],
        [
            'Todo el texto está en español',
            '{mlang en,fr,es}Todo el texto está en español{mlang}',
            'es',
        ],
        [
            'Todo el texto está en español',
            '{mlang   en   ,      fr,es    }Todo el texto está en español{mlang}',
            'es',
        ],
        [
            'Todo el texto está en español',
            '{mLaNg   eN   ,      FR,Es    }Todo el texto está en español{mlang}',
            'es',
        ],
        [
            '{mlang   en  ,  ,  ,,,     fr,es,,     ,}Bad filter syntax{mlang}',
            '{mlang   en  ,  ,  ,,,     fr,es,,     ,}Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang   en   ,}Bad filter syntax{mlang}',
            '{mlang   en   ,}Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang   en,     }Bad filter syntax{mlang}',
            '{mlang   en,     }Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang en  ,   }Bad filter syntax{mlang}',
            '{mlang en  ,   }Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang   en,   ,  }Bad filter syntax{mlang}',
            '{mlang   en,   ,  }Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang en,,es}Bad filter syntax{mlang}',
            '{mlang en,,es}Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang en  ,,  es  }Bad filter syntax{mlang}',
            '{mlang en  ,,  es  }Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang en , , es}Bad filter syntax{mlang}',
            '{mlang en , , es}Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang en , , es , }Bad filter syntax{mlang}',
            '{mlang en , , es , }Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang en,es,}Bad filter syntax{mlang}',
            '{mlang en,es,}Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang en , es , }Bad filter syntax{mlang}',
            '{mlang en , es , }Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang,es}Bad filter syntax{mlang}',
            '{mlang,es}Bad filter syntax{mlang}',
            'es',
        ],
        [
            '{mlang ,es}Bad filter syntax{mlang}',
            '{mlang ,es}Bad filter syntax{mlang}',
            'es',
        ],
        [
            null,
            null,
            'es',
        ],
        [
            123,
            123,
            'es',
        ],
        [
            123.0,
            123.0,
            'es',
        ],
        [
            'Some Canadian French text.Some French text.',
            '{mlang fr_ca}Some Canadian French text.{mlang}'
            . '{mlang en}Some English text.{mlang}'
            . '{mlang es}Some Spanish text.{mlang}'
            . '{mlang en_kids}Some English for Kids text.{mlang}'
            . '{mlang fr}Some French text.{mlang}',
            'fr_ca',
            [
                'fr_ca' => 'fr',
                'en_kids' => 'en',
            ],
            'default',
        ],
        [
            'Some Canadian French text.',
            '{mlang fr_ca}Some Canadian French text.{mlang}'
            . '{mlang en}Some English text.{mlang}'
            . '{mlang es}Some Spanish text.{mlang}'
            . '{mlang en_kids}Some English for Kids text.{mlang}'
            . '{mlang fr}Some French text.{mlang}',
            'fr_ca',
            [
                'fr_ca' => 'fr',
                'en_kids' => 'en',
            ],
            'never',
        ],
        [
            'Some English for Kids text.',
            '{mlang fr_ca}Some Canadian French text.{mlang}'
            . '{mlang en}Some English text.{mlang}'
            . '{mlang es}Some Spanish text.{mlang}'
            . '{mlang en_kids}Some English for Kids text.{mlang}'
            . '{mlang fr}Some French text.{mlang}',
            'en_kids',
            [
                'fr_ca' => 'fr',
                'en_kids' => 'en',
            ],
            'default',
        ],
        [
            'Some English text.Some English for Kids text.',
            '{mlang fr_ca}Some Canadian French text.{mlang}'
            . '{mlang en}Some English text.{mlang}'
            . '{mlang es}Some Spanish text.{mlang}'
            . '{mlang en_kids}Some English for Kids text.{mlang}'
            . '{mlang fr}Some French text.{mlang}',
            'en_kids',
            [
                'fr_ca' => 'fr',
                'en_kids' => 'en',
            ],
            'include_en',
        ],
    ];
}
