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
 * Strings for component 'filter_multilang2', language 'fr'
 *
 * @package    filter_multilang2
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 *             2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['filtername'] = 'Contenu Multilingue (v2)';
$string['parentlangalwaysen'] = 'Utiliser toujours les langues parentes, y compris \'en\'.';
$string['parentlangbehaviour'] = 'Comportement des langues parentes.';
$string['parentlangbehaviour_desc'] = '
<p>
  Le filtre détermine si un bloc de langue doit être affiché ou non en
  fonction des langues indiquées dans ledit bloc et de la langue
  langue actuelle utilisée par l\'utilisateur (ci-après «la langue de
  l\'utilisateur"). Cette correspondance peut être effectuée de trois
  manières différentes, que le filtre appelle <em>comportement des
  langues parentes</em>:
</p>
<ol>
  <li>
    <b>Utiliser toujours les langues parentes, à l\'exclusion de \'en\'</b>.
    <ul>
      <li>
        Il s\'agit de la valeur par défaut du paramètre. Le filtre
        utilise à la fois les langues specifiées dans le bloc de
        langue <code>{mlang ...}</code>, ainsi que tous ses langues
        parentes (jusqu\'à, mais sans inclure, la langue
        racine <code>en</code>).
      </li>
      <li>
        Exemple: Si un bloc de langue spécifie <code>{mlang
        en_us_k12}...{mlang}</code>, ce bloc sera uniquement affiché
        si la langue de l\'utilisateur est <code>en_us_k12</code>
        ou <code>en_us</code>, mais pas s\'il s\'agit
        de <code>en</code>.
      </li>
      <li>
        Remarque: Il est toujours possible d\'utiliser la langue
        anglaise (<code>en</code>), si cela est explicitement indiquée
        dans le bloc de langue. Par exemple, le bloc de
        langue <code>{mlang en}This text will be shown when the user’s
        current language is \'en\'.{mlang}</code> s\'affichera lorsque
        la langue de l\'utilisateur est <code>en</code>.
      </li>
    </ul>
  </li>
  <li>
    <b>Utiliser toujours les langues parentes, y compris \'en\'</b>.
    <ul>
      <li>
        Ce paramètre fonctionne comme le précédent, mais inclut la
        racine <code>en</code> comme une langue parente valide.
      </li>
      <li>
        Exemple: Si un bloc de langue spécifie <code>{mlang
        en_us_k12}...{mlang}</code>, ce bloc sera affiché si la langue
        de l\'utilisateur est <code>en_us_k12</code>, comme si elle
        est <code>en_us</code>, ou si elle est <code>en</code>.
      </li>
    </ul>
  </li>
  <li>
    <b>N\'utiliser jamais les langues parentes</b>.
    <ul>
      <li>
        Comme son nom l\'indique, les langues parentes ne sont jamais
        utilisées. Le filtre ne prend en compte que les langues
        indiquées explicitement dans le bloc de langue, sans prendre
        en compte aucune langue parente.
      </li>
      <li>
        Exemple: si un bloc de langue spécifie <code> {mlang
        en_us_k12}...{mlang}</code>, ce bloc sera affiché uniquement
        si la langue de l\'utilisateur est <code>en_us_k12</code>,
        mais pas si est <code>en_us</code> ou <code>en</code>.
      </li>
    </ul>
  </li>
</ol>';
$string['parentlangdefault'] = 'Utiliser toujours les langues parentes, à l\'exclusion de \'en\' (comportement traditionnel).';
$string['parentlangnever'] = 'N\'utiliser jamais les langues parentes.';
$string['pluginname'] = 'Filtre de Contenu Multilingue (v2)';
$string['privacy:metadata'] = 'Le plugin Filtre Contenu Multilingue (v2) n\'enregistre aucune donnée personnelle.';
