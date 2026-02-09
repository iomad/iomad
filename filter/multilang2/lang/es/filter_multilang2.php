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
 * Strings for component 'filter_multilang2', language 'es'
 *
 * @package    filter_multilang2
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 *             2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['filtername'] = 'Contenido Multi-Idioma (v2)';
$string['parentlangalwaysen'] = 'Usar idiomas padre siempre, incluído \'en\'.';
$string['parentlangbehaviour'] = 'Comportamiento de idiomas padre';
$string['parentlangbehaviour_desc'] = '
<p>
  El filtro determina si un bloque de idioma se debe visualizar o no
  basándose en los idiomas indicados en dicho bloque y en el idioma
  actual usado por el usuario (en adelante "el idioma del
  usuario"). Este emparejamiento se puede realizar de tres formas
  diferentes, a las que el filtro llama <em>comportamiento de idiomas
  padre</em>:
</p>
<ol>
  <li>
    <b>Usar idiomas padre siempre, excluído \'en\'.</b>
    <ul>
      <li>
        Este es el valor predeterminado para el ajuste. El filtro
        tiene en cuenta los idiomas indicados en la etiqueta del
        bloque de idioma <code>{mlang ...}</code>, así como todos sus
        idiomas padres (hasta, pero sin includir, el idioma
        raíz <code>en</code>).
      </li>
      <li>
        Ejemplo: si un bloque de idioma especifica <code>{mlang
        en_us_k12}...{mlang}</code>, dicho bloque sólo se visualizará
        si el idioma del usuario es <code>en_us_k12</code>
        o <code>en_us</code>, pero no si se trata de <code>en</code>.
      </li>
      <li>
        Nota: Siempre es posible usar el idioma Inglés
        (<code>en</code>), si éste se indica explícitamente en el
        bloque de idioma. Por ejemplo, el bloque de
        idioma <code>{mlang en}This text will be shown when the user’s
        current language is \'en\'.{mlang}</code> se mostrará cuando
        el idioma del usuario sea <code>en</code>.
      </li>
    </ul>
  </li>
  <li>
    <b>Usar idiomas padre siempre, incluído \'en\'.</b>
    <ul>
      <li>
        Este ajuste funciona como el anterior, pero incluye la
        raíz <code>en</code> como idioma padre válido.
      </li>
      <li>
        Ejemplo: si un bloque de idioma especifica <code>{mlang
        en_us_k12}...{mlang}</code>, dicho bloque se visualizará tanto
        si el idioma del usuario es <code>en_us_k12</code>, como si
        es <code>en_us</code>, o si es <code>en</code>.
      </li>
    </ul>
  </li>
  <li>
    <b>No usar idiomas padre nunca.</b>
    <ul>
      <li>
        Como su nombre indica, no se usan los idiomas padre nunca.
        El filtro sólo considera los idiomas indicados de forma
        explícita en el bloque de idioma, y no considera en ningún
        caso ningún idioma padre.
      </li>
      <li>
        Ejemplo: si un bloque de idioma especifica <code> {mlang
        en_us_k12}...{mlang}</code>, dicho bloque sólo se visualizará
        si el idioma del usuario es <code>en_us_k12</code>, pero no si
        es <code>en_us</code> o <code>en</code>.
      </li>
    </ul>
  </li>
</ol>';
$string['parentlangdefault'] = 'Usar idiomas padre siempre, excluído \'en\' (comportamiento tradicional).';
$string['parentlangnever'] = 'No usar idiomas padre nunca.';
$string['pluginname'] = 'Filtro Contenido Multi-Idioma (v2)';
$string['privacy:metadata'] = 'El plugin del filtro de Contenido Multi-idioma (v2) no almacena ningún dato personal.';
