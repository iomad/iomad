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
 * Strings for component 'filter_multilang2', language 'en'
 *
 * @package    filter_multilang2
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 *             2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['filtername'] = 'Multi-Language Content (v2)';
$string['parentlangalwaysen'] = 'Always use parent languages, including \'en\'.';
$string['parentlangbehaviour'] = 'Parent languages behaviour';
$string['parentlangbehaviour_desc'] = '
<p>
  The filter determines whether a language block should be displayed
  based on the languages specified in the block and the current
  language being used by the user ("the user\'s current
  language"). This matching process can follow three different
  approaches, known as "<em>parent languages behaviour</em>":
</p>
<ol>
  <li>
    <b>Always use parent languages, excluding \'en\'.</b>
    <ul>
      <li>
        This is the default setting. The filter considers the
        languages listed in the language block\'s <code>{mlang
        ...}</code> tag, and all of their parent languages (up to,
        but not including, the root <code>en</code> language).
      </li>
      <li>
        Example: If a language block specifies <code>{mlang
        en_us_k12}</code>, it will only display if the user\'s
        current language is <code>en_us_k12</code>
        or <code>en_us</code> but not <code>en</code>.
      </li>
      <li>
        Note: English can still be used explicitly in the language
        block. For example, <code>{mlang en}This text will be shown
        when the user’s current language is \'en\'.{mlang}</code>
        will display the content when the user\'s current language
        is <code>en</code>.
      </li>
    </ul>
  </li>
  <li>
    <b>Always use parent languages, including \'en\'.</b>
    <ul>
      <li>
        This setting works like the previous one, but includes the
        root <code>en</code> as a valid parent language.
      </li>
      <li>
        Example: If a language block specifies <code>{mlang
        en_us_k12}</code>, it will display if the user\'s current
        language is either <code>en_us_k12</code>,
        <code>en_us</code> or <code>en</code>.
      </li>
    </ul>
  </li>
  <li>
    <b>Never use parent languages.</b>
    <ul>
      <li>
        As the name suggests, no parent languages are used. The
        filter only matches the languages explicitly listed in the
        language block, without considering any parent languages.
      </li>
      <li>
        Example: If a language block specifies <code>{mlang
        en_us_k12}</code>, it will only display if the user\'s
        current language is <code>en_us_k12</code>,
        not <code>en_us</code>
        or <code>en</code>.
      </li>
    </ul>
  </li>
</ol>';
$string['parentlangdefault'] = 'Always use parent languages, excluding \'en\' (traditional behaviour).';
$string['parentlangnever'] = 'Never use parent languages.';
$string['pluginname'] = 'Multi-Language Content (v2) Filter';
$string['privacy:metadata'] = 'The Multi-Language Content (v2) Filter plugin does not store any personal data.';
