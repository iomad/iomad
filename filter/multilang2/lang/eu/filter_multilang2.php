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
 * Strings for component 'filter_multilang2', language 'eu'
 *
 * @package    filter_multilang2
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 *             2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['filtername'] = 'Eduki eleaniztuna (2.bertsioa)';
$string['parentlangalwaysen'] = 'Beti erabili guraso-hizkuntzak, \'en\' barne.';
$string['parentlangbehaviour'] = 'Guraso-hizkuntzen portaera';
$string['parentlangbehaviour_desc'] = '
<p>
  Iragazkiak hizkuntza-bloke bat erakutsi behar den blokean
  zehaztutako hizkuntzaren eta erabiltzailea une horretan erabiltzen
  den hizkuntzaren ("erabiltzailearen une horretako hizkuntza") arabera
  erabakitzen du. Bat-etortze prozesu hori "<em>guraso-hizkuntzen
  portaera</em>" deitutako hiru modutan egin daiteke:
</p>
<ol>
  <li>
    <b>Beti erabili guraso-hizkuntzak, \'en\' izan ezik.</b>
    <ul>
      <li>
        <em>Hau da pluginaren portaera tradizionala</em> eta
        lehenetsitako aukera. Iragazkiak <code>{mlang...}</code>
        etiketa arteko hizkuntza-blokean zehaztutako hizkuntzak eta
        euren guraso-hizkuntza guztiak hartuko ditu kontuan, jatorrizko
        <code>en</code> hizkuntza izan ezik.
      </li>
      <li>
        Adibidez: hizkuntza-bloke batek <code>{mlang en_us_k12}</code>
        zehazten badu, soilik erakutsiko da erabiltzailearen uneko
        hizkuntza <code>en_us_k12</code> ala <code>en_us</code> bada,
        baina ez hizkuntza <code>en</code> bada.
      </li>
      <li>
        Oharra: Ingelesa hizkuntza-blokeetan esplizituki ere erabili
        daiteke. Esaterako <code>{mlang en}This text will be shown
        when the user’s current language is \'en\'.{mlang}</code>
        hizkuntza-blokea erakutsi egingo da erabiltzailearen uneko
        hizkuntza <code>en</code> denean.
      </li>
    </ul>
  </li>
  <li>
    <b>Beti erabili guraso-hizkuntzak, \'en\' barne.</b>
    <ul>
      <li>
        Aukera honek "Beti erabili guraso-hizkuntzak, \'en\' izan ezik"
        ezarpena bezala funtzionatzen du, baina jatorrizko
        <code>en</code> hizkuntza baliozko guraso-hizkuntza gisa
        hartuko da.
      </li>
      <li>
        Adibidez: hizkuntza-bloke batek <code>{mlang en}</code>
        erabiltzen badu, blokea erakutsi egingo da erabiltzailearen
        une hizkuntza <code>en_us_k12</code>, <code>en_us</code> ala
        <code>en</code> denean.
      </li>
    </ul>
  </li>
  <li>
    <b>Ez erabili inoiz guraso-hizkuntzak.</b>
    <ul>
      <li>
        Izenak iradokitzen duen moduan, aukera honekin guraso-hizkuntzak
        ez dira inoiz kontuan hartzen. Iragazkiak soilik
        hizkuntza-blokean esplizituki erabiltzen diren hizkuntzak
        hartuko ditu kontuan, inongo guraso-hizkuntzarik kontuan hartu
        gabe.
      </li>
      <li>
        Adibidez: hizkuntza-bloke batek <code>{mlang en_us_k12}</code>
        zehazten badu, soilik erakutsiko da erabiltzailearen uneko
        hizkuntza <code>en_us_k12</code> denean, eta ez
        <code>en_us</code> ala <code>en</code> bada.
      </li>
    </ul>
  </li>
</ol>';
$string['parentlangdefault'] = 'Beti erabili guraso-hizkuntzak, \'en\' izan ezik (portaera tradizionala).';
$string['parentlangnever'] = 'Ez erabili inoiz guraso-hizkuntzak.';
$string['pluginname'] = 'Eduki eleaniztunaren Iragazkia (2.bertsioa)';
$string['privacy:metadata'] = 'Eduki eleaniztunaren Iragazkia (2.bertsioa) pluginak ez du datu pertsonalik biltzen.';
