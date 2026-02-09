<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * TinyMCE custom steps definitions for the multilang plugin.
 *
 * It's basically all what the TinyMCE steps provide, but with this extensions:
 * - New step to select the inner text of an element, e.g. the paragraph without
 *   the paragraph tags.
 * - Menu items can have sub items that need to be clicked as well. In this case
 *   the menu to be clicked is Format -> Language -> some language.
 *
 * @package    tiny_multilang2
 * @category   test
 * @copyright  2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../../behat/behat_base.php');
require_once(__DIR__ . '/../../../../tests/behat/editor_tiny_helpers.php');


/**
 * Extends general TinyMCE test to test the tiny_multilang2 plugin.
 */
class behat_editor_tiny_multilang2 extends behat_base {
    use editor_tiny_helpers;

    /**
     * The menu element of the TinyMCE editor.
     * @var \Behat\Mink\Element\NodeElement
     */
    private $menubar;

    /**
     * The menu items in the TinyMCE editor that should be clicked.
     * @var string[]
     */
    private $menus;

    /**
     * Click on a button for the specified TinyMCE editor.
     *
     * phpcs:disable
     * @When /^I click on the "(?P<menuitem_string>(?:[^"]|\\")*)" submenu item for the "(?P<locator_string>(?:[^"]|\\")*)" TinyMCE editor$/
     * phpcs:enable
     *
     * @param string $menuitem The label of the menu item
     * @param string $locator The locator for the editor
     */
    public function i_click_on_submenuitem_in_menu(string $menuitem, string $locator): void {
        global $CFG;

        $this->require_tiny_tags();
        $container = $this->get_editor_container_for_locator($locator);

        $this->menubar = $container->find('css', '[role="menubar"]');

        $this->menus = array_map(function(string $value): string {
            return trim($value);
        }, explode('>', $menuitem));

        if ($CFG->version < 2024100700) {
            $this->before_four_five();
        } else {
            $this->four_five_and_later();
        }
    }

    /**
     * Click the TinyMCE menu prior to Moodle 4.5
     */
    private function before_four_five() {
        // Open the menu bar.
        $mainmenu = array_shift($this->menus);
        $this->execute('behat_general::i_click_on_in_the', [$mainmenu, 'button', $this->menubar, 'NodeElement']);

        foreach ($this->menus as $menuitem) {
            // Find the menu that was opened.
            $openmenu = $this->find('css', '.tox-selected-menu');

            // Move the mouse to the first item in the list.
            // This is required because WebDriver takes the shortest path to the next click location,
            // which will mean crossing across other menu items.
            $firstlink = $openmenu->find('css', "[role^='menuitem'] .tox-collection__item-icon");
            if ($firstlink) {
                $firstlink->mouseover();
            }

            // Now match by title where the role matches any menuitem, or menuitemcheckbox, or menuitem*.
            $link = $openmenu->find('css', "[title='{$menuitem}'][role^='menuitem']");
            $this->execute('behat_general::i_click_on', [$link, 'NodeElement']);
        }
    }

    /**
     * Click the TinyMCE menu in Moodle 4.5 and later.
     */
    private function four_five_and_later() {
        // Open the menu bar.
        $mainmenu = array_shift($this->menus);
        $button = $this->menubar->find('xpath', "//span[text()='{$mainmenu}']");
        $this->execute('behat_general::i_click_on', [$button, 'NodeElement']);

        // Find the menu that was opened.
        $openmenu = $this->find('css', '.tox-selected-menu');

        foreach ($this->menus as $i => $menuitem) {
            $item = $openmenu->find('css', "[aria-label='{$menuitem}']");
            $item->mouseover();
            $this->execute('behat_general::i_click_on', [$item, 'NodeElement']);
        }
    }

    /**
     * Select the first child of an element type/index for the specified TinyMCE editor.
     * Note that this works only if the content is plain text without formatting. Otherwise, the first child
     * selects the part until the next sibling that would be a formatting node such as bold, italics etc. Also,
     * a linebreak (<br/>) intercepts the selection.
     *
     * phpcs:disable
     * @When /^I select the inner "(?P<textlocator_string>(?:[^"]|\\")*)" element in position "(?P<position_int>(?:[^"]|\\")*)" of the "(?P<locator_string>(?:[^"]|\\")*)" TinyMCE editor$/
     * phpcs:enable
     *
     * @param string $textlocator The type of element to select (for example `p` or `span`)
     * @param int $position The zero-indexed position
     * @param string $locator The editor to select within
     */
    public function select_text_inner(string $textlocator, int $position, string $locator): void {
        $this->require_tiny_tags();

        $editor = $this->get_textarea_for_locator($locator);
        $editorid = $editor->getAttribute('id');

        // Ensure that a name is set on the iframe relating to the editorid.
        $js = <<<EOF
            const element = instance.dom.select("{$textlocator}")[{$position}].firstChild;
            instance.selection.select(element);
        EOF;

        $this->execute_javascript_for_editor($editorid, $js);
    }
}
