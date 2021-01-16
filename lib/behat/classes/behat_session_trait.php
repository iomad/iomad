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
 * A trait containing functionality used by the behat base context, and form fields.
 *
 * @package    core
 * @category   test
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\Element;
use Behat\Mink\Session;

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/component_named_replacement.php');
require_once(__DIR__ . '/component_named_selector.php');

// Alias the WebDriver\Key  class to behat_keys to make future transition to a different WebDriver implementation
// easier.
class_alias('WebDriver\\Key', 'behat_keys');

/**
 * A trait containing functionality used by the behat base context, and form fields.
 *
 * This trait should be used by the behat_base context, and behat form fields, and it should be paired with the
 * behat_session_interface interface.
 *
 * It should not be necessary to use this trait, and the behat_session_interface interface in normal circumstances.
 *
 * @package    core
 * @category   test
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait behat_session_trait {

    /**
     * Locates url, based on provided path.
     * Override to provide custom routing mechanism.
     *
     * @see Behat\MinkExtension\Context\MinkContext
     * @param string $path
     * @return string
     */
    protected function locate_path($path) {
        $starturl = rtrim($this->getMinkParameter('base_url'), '/') . '/';
        return 0 !== strpos($path, 'http') ? $starturl . ltrim($path, '/') : $path;
    }

    /**
     * Returns the first matching element.
     *
     * @link http://mink.behat.org/#traverse-the-page-selectors
     * @param string $selector The selector type (css, xpath, named...)
     * @param mixed $locator It depends on the $selector, can be the xpath, a name, a css locator...
     * @param Exception $exception Otherwise we throw exception with generic info
     * @param NodeElement $node Spins around certain DOM node instead of the whole page
     * @param int $timeout Forces a specific time out (in seconds).
     * @return NodeElement
     */
    protected function find($selector, $locator, $exception = false, $node = false, $timeout = false) {
        if ($selector === 'NodeElement' && is_a($locator, NodeElement::class)) {
            // Support a NodeElement being passed in for use in step chaining.
            return $locator;
        }

        // Returns the first match.
        $items = $this->find_all($selector, $locator, $exception, $node, $timeout);
        return count($items) ? reset($items) : null;
    }

    /**
     * Returns all matching elements.
     *
     * Adapter to Behat\Mink\Element\Element::findAll() using the spin() method.
     *
     * @link http://mink.behat.org/#traverse-the-page-selectors
     * @param string $selector The selector type (css, xpath, named...)
     * @param mixed $locator It depends on the $selector, can be the xpath, a name, a css locator...
     * @param Exception $exception Otherwise we throw expcetion with generic info
     * @param NodeElement $container Restrict the search to just children of the specified container
     * @param int $timeout Forces a specific time out (in seconds). If 0 is provided the default timeout will be applied.
     * @return array NodeElements list
     */
    protected function find_all($selector, $locator, $exception = false, $container = false, $timeout = false) {
        // Throw exception, so dev knows it is not supported.
        if ($selector === 'named') {
            $exception = 'Using the "named" selector is deprecated as of 3.1. '
                .' Use the "named_partial" or use the "named_exact" selector instead.';
            throw new ExpectationException($exception, $this->getSession());
        }

        // Generic info.
        if (!$exception) {
            // With named selectors we can be more specific.
            if (($selector == 'named_exact') || ($selector == 'named_partial')) {
                $exceptiontype = $locator[0];
                $exceptionlocator = $locator[1];

                // If we are in a @javascript session all contents would be displayed as HTML characters.
                if ($this->running_javascript()) {
                    $locator[1] = html_entity_decode($locator[1], ENT_NOQUOTES);
                }

            } else {
                $exceptiontype = $selector;
                $exceptionlocator = $locator;
            }

            $exception = new ElementNotFoundException($this->getSession(), $exceptiontype, null, $exceptionlocator);
        }

        // How much we will be waiting for the element to appear.
        if (!$timeout) {
            $timeout = self::get_timeout();
            $microsleep = false;
        } else {
            // Spinning each 0.1 seconds if the timeout was forced as we understand
            // that is a special case and is good to refine the performance as much
            // as possible.
            $microsleep = true;
        }

        // Normalise the values in order to perform the search.
        $normalised = $this->normalise_selector($selector, $locator, $container ?: $this->getSession()->getPage());
        $selector = $normalised['selector'];
        $locator = $normalised['locator'];
        $container = $normalised['container'];

        if (empty($container)) {
            $container = $this->getSession()->getPage();
        }

        // Waits for the node to appear if it exists, otherwise will timeout and throw the provided exception.
        return $this->spin(
            function() use ($selector, $locator, $container) {
                return $container->findAll($selector, $locator);
            }, [], $timeout, $exception, $microsleep
        );
    }

    /**
     * Normalise the locator and selector.
     *
     * @param string $selector The type of thing to search
     * @param mixed $locator The locator value. Can be an array, but is more likely a string.
     * @param Element $container An optional container to search within
     * @return array The selector, locator, and container to search within
     */
    public function normalise_selector(string $selector, $locator, Element $container): array {
        // Check for specific transformations for this selector type.
        $transformfunction = "transform_find_for_{$selector}";
        if (method_exists('behat_selectors', $transformfunction)) {
            // A selector-specific transformation exists.
            // Perform initial transformation of the selector within the current container.
            $normalised = behat_selectors::{$transformfunction}($this, $locator, $container ?: $this->getSession()->getPage());
            $selector = $normalised['selector'];
            $locator = $normalised['locator'];
            $container = $normalised['container'];
        }

        // Normalise the css and xpath selector types.
        if ('css_element' === $selector) {
            $selector = 'css';
        } else if ('xpath_element' === $selector) {
            $selector = 'xpath';
        }

        // Convert to a named selector where the selector type is not a known selector.
        $converttonamed = !$this->getSession()->getSelectorsHandler()->isSelectorRegistered($selector);
        $converttonamed = $converttonamed && 'xpath' !== $selector;
        if ($converttonamed) {
            if (behat_partial_named_selector::is_deprecated_selector($selector)) {
                if ($replacement = behat_partial_named_selector::get_deprecated_replacement($selector)) {
                    error_log("The '{$selector}' selector has been replaced with {$replacement}");
                    $selector = $replacement;
                }
            } else if (behat_exact_named_selector::is_deprecated_selector($selector)) {
                if ($replacement = behat_exact_named_selector::get_deprecated_replacement($selector)) {
                    error_log("The '{$selector}' selector has been replaced with {$replacement}");
                    $selector = $replacement;
                }
            }

            $allowedpartialselectors = behat_partial_named_selector::get_allowed_selectors();
            $allowedexactselectors = behat_exact_named_selector::get_allowed_selectors();
            if (isset($allowedpartialselectors[$selector])) {
                $locator = behat_selectors::normalise_named_selector($allowedpartialselectors[$selector], $locator);
                $selector = 'named_partial';
            } else if (isset($allowedexactselectors[$selector])) {
                $locator = behat_selectors::normalise_named_selector($allowedexactselectors[$selector], $locator);
                $selector = 'named_exact';
            } else {
                throw new ExpectationException("The '{$selector}' selector type is not registered.", $this->getSession()->getDriver());
            }
        }

        return [
            'selector' => $selector,
            'locator' => $locator,
            'container' => $container,
        ];
    }

    /**
     * Send key presses straight to the currently active element.
     *
     * The `$keys` array contains a list of key values to send to the session as defined in the WebDriver and JsonWire
     * specifications:
     * - JsonWire: https://github.com/SeleniumHQ/selenium/wiki/JsonWireProtocol#sessionsessionidkeys
     * - W3C WebDriver: https://www.w3.org/TR/webdriver/#keyboard-actions
     *
     * This may be a combination of typable characters, modifier keys, and other supported keypoints.
     *
     * The NULL_KEY should be used to release modifier keys. If the NULL_KEY is not used then modifier keys will remain
     * in the pressed state.
     *
     * Example usage:
     *
     *      behat_base::type_keys($this->getSession(), [behat_keys::SHIFT, behat_keys::TAB, behat_keys::NULL_KEY]);
     *      behat_base::type_keys($this->getSession(), [behat_keys::ENTER, behat_keys::NULL_KEY]);
     *      behat_base::type_keys($this->getSession(), [behat_keys::ESCAPE, behat_keys::NULL_KEY]);
     *
     * It can also be used to send text input, for example:
     *
     *      behat_base::type_keys(
     *          $this->getSession(),
     *          ['D', 'o', ' ', 'y', 'o', 'u', ' ', 'p', 'l', 'a' 'y', ' ', 'G', 'o', '?', behat_base::NULL_KEY]
     *      );
     *
     *
     * Please note: This function does not use the element/sendKeys variants but sends keys straight to the browser.
     *
     * @param Session $session
     * @param string[] $keys
     */
    public static function type_keys(Session $session, array $keys) {
        $session->getDriver()->getWebDriverSession()->keys([
            'value' => $keys,
        ]);
    }

    /**
     * Finds DOM nodes in the page using named selectors.
     *
     * The point of using this method instead of Mink ones is the spin
     * method of behat_base::find() that looks for the element until it
     * is available or it timeouts, this avoids the false failures received
     * when selenium tries to execute commands on elements that are not
     * ready to be used.
     *
     * All steps that requires elements to be available before interact with
     * them should use one of the find* methods.
     *
     * The methods calls requires a {'find_' . $elementtype}($locator)
     * format, like find_link($locator), find_select($locator),
     * find_button($locator)...
     *
     * @link http://mink.behat.org/#named-selectors
     * @throws coding_exception
     * @param string $name The name of the called method
     * @param mixed $arguments
     * @return NodeElement
     */
    public function __call($name, $arguments) {
        if (substr($name, 0, 5) === 'find_') {
            return call_user_func_array([$this, 'find'], array_merge(
                [substr($name, 5)],
                $arguments
            ));
        }

        throw new coding_exception("The '{$name}' method does not exist");
    }

    /**
     * Escapes the double quote character.
     *
     * Double quote is the argument delimiter, it can be escaped
     * with a backslash, but we auto-remove this backslashes
     * before the step execution, this method is useful when using
     * arguments as arguments for other steps.
     *
     * @param string $string
     * @return string
     */
    public function escape($string) {
        return str_replace('"', '\"', $string);
    }

    /**
     * Executes the passed closure until returns true or time outs.
     *
     * In most cases the document.readyState === 'complete' will be enough, but sometimes JS
     * requires more time to be completely loaded or an element to be visible or whatever is required to
     * perform some action on an element; this method receives a closure which should contain the
     * required statements to ensure the step definition actions and assertions have all their needs
     * satisfied and executes it until they are satisfied or it timeouts. Redirects the return of the
     * closure to the caller.
     *
     * The closures requirements to work well with this spin method are:
     * - Must return false, null or '' if something goes wrong
     * - Must return something != false if finishes as expected, this will be the (mixed) value
     * returned by spin()
     *
     * The arguments of the closure are mixed, use $args depending on your needs.
     *
     * You can provide an exception to give more accurate feedback to tests writers, otherwise the
     * closure exception will be used, but you must provide an exception if the closure does not throw
     * an exception.
     *
     * @throws Exception If it timeouts without receiving something != false from the closure
     * @param Function|array|string $lambda The function to execute or an array passed to call_user_func (maps to a class method)
     * @param mixed $args Arguments to pass to the closure
     * @param int $timeout Timeout in seconds
     * @param Exception $exception The exception to throw in case it time outs.
     * @param bool $microsleep If set to true it'll sleep micro seconds rather than seconds.
     * @return mixed The value returned by the closure
     */
    protected function spin($lambda, $args = false, $timeout = false, $exception = false, $microsleep = false) {

        // Using default timeout which is pretty high.
        if (!$timeout) {
            $timeout = self::get_timeout();
        }

        $start = microtime(true);
        $end = $start + $timeout;

        do {
            // We catch the exception thrown by the step definition to execute it again.
            try {
                // We don't check with !== because most of the time closures will return
                // direct Behat methods returns and we are not sure it will be always (bool)false
                // if it just runs the behat method without returning anything $return == null.
                if ($return = call_user_func($lambda, $this, $args)) {
                    return $return;
                }
            } catch (Exception $e) {
                // We would use the first closure exception if no exception has been provided.
                if (!$exception) {
                    $exception = $e;
                }
            }

            if (!$this->running_javascript()) {
                break;
            }

            usleep(100000);

        } while (microtime(true) < $end);

        // Using coding_exception as is a development issue if no exception has been provided.
        if (!$exception) {
            $exception = new coding_exception('spin method requires an exception if the callback does not throw an exception');
        }

        // Throwing exception to the user.
        throw $exception;
    }

    /**
     * Gets a NodeElement based on the locator and selector type received as argument from steps definitions.
     *
     * Use behat_base::get_text_selector_node() for text-based selectors.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $selectortype
     * @param string $element
     * @return NodeElement
     */
    protected function get_selected_node($selectortype, $element) {
        return $this->find($selectortype, $element);
    }

    /**
     * Gets a NodeElement based on the locator and selector type received as argument from steps definitions.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $selectortype
     * @param string $element
     * @return NodeElement
     */
    protected function get_text_selector_node($selectortype, $element) {
        // Getting Mink selector and locator.
        list($selector, $locator) = $this->transform_text_selector($selectortype, $element);

        // Returns the NodeElement.
        return $this->find($selector, $locator);
    }

    /**
     * Gets the requested element inside the specified container.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param mixed $selectortype The element selector type.
     * @param mixed $element The element locator.
     * @param mixed $containerselectortype The container selector type.
     * @param mixed $containerelement The container locator.
     * @return NodeElement
     */
    protected function get_node_in_container($selectortype, $element, $containerselectortype, $containerelement) {
        if ($containerselectortype === 'NodeElement' && is_a($containerelement, NodeElement::class)) {
            // Support a NodeElement being passed in for use in step chaining.
            $containernode = $containerelement;
            $locatorexceptionmsg = $element;
        } else {
            // Gets the container, it will always be text based.
            $containernode = $this->get_text_selector_node($containerselectortype, $containerelement);
            $locatorexceptionmsg = $element . '" in the "' . $containerelement. '" "' . $containerselectortype. '"';
        }

        $exception = new ElementNotFoundException($this->getSession(), $selectortype, null, $locatorexceptionmsg);

        return $this->find($selectortype, $element, $exception, $containernode);
    }

    /**
     * Transforms from step definition's argument style to Mink format.
     *
     * Mink has 3 different selectors css, xpath and named, where named
     * selectors includes link, button, field... to simplify and group multiple
     * steps in one we use the same interface, considering all link, buttons...
     * at the same level as css selectors and xpath; this method makes the
     * conversion from the arguments received by the steps to the selectors and locators
     * required to interact with Mink.
     *
     * @throws ExpectationException
     * @param string $selectortype It can be css, xpath or any of the named selectors.
     * @param string $element The locator (or string) we are looking for.
     * @return array Contains the selector and the locator expected by Mink.
     */
    protected function transform_selector($selectortype, $element) {
        // Here we don't know if an allowed text selector is being used.
        $selectors = behat_selectors::get_allowed_selectors();
        if (!isset($selectors[$selectortype])) {
            throw new ExpectationException('The "' . $selectortype . '" selector type does not exist', $this->getSession());
        }

        $normalised = $this->normalise_selector($selectortype, $element, $this->getSession()->getPage());
        return [$normalised['selector'], $normalised['locator']];
    }

    /**
     * Transforms from step definition's argument style to Mink format.
     *
     * Delegates all the process to behat_base::transform_selector() checking
     * the provided $selectortype.
     *
     * @throws ExpectationException
     * @param string $selectortype It can be css, xpath or any of the named selectors.
     * @param string $element The locator (or string) we are looking for.
     * @return array Contains the selector and the locator expected by Mink.
     */
    protected function transform_text_selector($selectortype, $element) {

        $selectors = behat_selectors::get_allowed_text_selectors();
        if (empty($selectors[$selectortype])) {
            throw new ExpectationException('The "' . $selectortype . '" selector can not be used to select text nodes', $this->getSession());
        }

        return $this->transform_selector($selectortype, $element);
    }

    /**
     * Whether Javascript is available in the current Session.
     *
     * @return boolean
     */
    protected function running_javascript() {
        return self::running_javascript_in_session($this->getSession());
    }

    /**
     * Require that javascript be available in the current Session.
     *
     * @throws DriverException
     */
    protected function require_javascript() {
        return self::require_javascript_in_session($this->getSession());
    }

    /**
     * Whether Javascript is available in the specified Session.
     *
     * @param Session $session
     * @return boolean
     */
    protected static function running_javascript_in_session(Session $session): bool {
        return get_class($session->getDriver()) !== 'Behat\Mink\Driver\GoutteDriver';
    }

    /**
     * Require that javascript be available for the specified Session.
     *
     * @param Session $session
     * @throws DriverException
     */
    protected static function require_javascript_in_session(Session $session) {
        if (self::running_javascript_in_session($session)) {
            return;
        }

        throw new DriverException('Javascript is required');
    }

    /**
     * Spins around an element until it exists
     *
     * @throws ExpectationException
     * @param string $locator
     * @param string $selectortype
     * @return void
     */
    protected function ensure_element_exists($locator, $selectortype) {
        // Exception if it timesout and the element is still there.
        $msg = "The '{$locator}' element does not exist and should";
        $exception = new ExpectationException($msg, $this->getSession());

        // Normalise the values in order to perform the search.
        $normalised = $this->normalise_selector($selectortype, $locator, $this->getSession()->getPage());
        $selector = $normalised['selector'];
        $locator = $normalised['locator'];
        $container = $normalised['container'];

        // It will stop spinning once the find() method returns true.
        $this->spin(
            function() use ($selector, $locator, $container) {
                if ($container->find($selector, $locator)) {
                    return true;
                }
                return false;
            },
            [],
            self::get_extended_timeout(),
            $exception,
            true
        );
    }

    /**
     * Spins until the element does not exist
     *
     * @throws ExpectationException
     * @param string $locator
     * @param string $selectortype
     * @return void
     */
    protected function ensure_element_does_not_exist($locator, $selectortype) {
        // Exception if it timesout and the element is still there.
        $msg = "The '{$locator}' element exists and should not exist";
        $exception = new ExpectationException($msg, $this->getSession());

        // Normalise the values in order to perform the search.
        $normalised = $this->normalise_selector($selectortype, $locator, $this->getSession()->getPage());
        $selector = $normalised['selector'];
        $locator = $normalised['locator'];
        $container = $normalised['container'];

        // It will stop spinning once the find() method returns false.
        $this->spin(
            function() use ($selector, $locator, $container) {
                if ($container->find($selector, $locator)) {
                    return false;
                }
                return true;
            },
            // Note: We cannot use $this because the find will then be $this->find(), which leads us to a nested spin().
            // We cannot nest spins because the outer spin times out before the inner spin completes.
            [],
            self::get_extended_timeout(),
            $exception,
            true
        );
    }

    /**
     * Ensures that the provided node is visible and we can interact with it.
     *
     * @throws ExpectationException
     * @param NodeElement $node
     * @return void Throws an exception if it times out without the element being visible
     */
    protected function ensure_node_is_visible($node) {

        if (!$this->running_javascript()) {
            return;
        }

        // Exception if it timesout and the element is still there.
        $msg = 'The "' . $node->getXPath() . '" xpath node is not visible and it should be visible';
        $exception = new ExpectationException($msg, $this->getSession());

        // It will stop spinning once the isVisible() method returns true.
        $this->spin(
            function($context, $args) {
                if ($args->isVisible()) {
                    return true;
                }
                return false;
            },
            $node,
            self::get_extended_timeout(),
            $exception,
            true
        );
    }

    /**
     * Ensures that the provided node has a attribute value set. This step can be used to check if specific
     * JS has finished modifying the node.
     *
     * @throws ExpectationException
     * @param NodeElement $node
     * @param string $attribute attribute name
     * @param string $attributevalue attribute value to check.
     * @return void Throws an exception if it times out without the element being visible
     */
    protected function ensure_node_attribute_is_set($node, $attribute, $attributevalue) {

        if (!$this->running_javascript()) {
            return;
        }

        // Exception if it timesout and the element is still there.
        $msg = 'The "' . $node->getXPath() . '" xpath node is not visible and it should be visible';
        $exception = new ExpectationException($msg, $this->getSession());

        // It will stop spinning once the $args[1]) == $args[2], and method returns true.
        $this->spin(
            function($context, $args) {
                if ($args[0]->getAttribute($args[1]) == $args[2]) {
                    return true;
                }
                return false;
            },
            array($node, $attribute, $attributevalue),
            self::get_extended_timeout(),
            $exception,
            true
        );
    }

    /**
     * Ensures that the provided element is visible and we can interact with it.
     *
     * Returns the node in case other actions are interested in using it.
     *
     * @throws ExpectationException
     * @param string $element
     * @param string $selectortype
     * @return NodeElement Throws an exception if it times out without being visible
     */
    protected function ensure_element_is_visible($element, $selectortype) {

        if (!$this->running_javascript()) {
            return;
        }

        $node = $this->get_selected_node($selectortype, $element);
        $this->ensure_node_is_visible($node);

        return $node;
    }

    /**
     * Ensures that all the page's editors are loaded.
     *
     * @deprecated since Moodle 2.7 MDL-44084 - please do not use this function any more.
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @return void
     */
    protected function ensure_editors_are_loaded() {
        global $CFG;

        if (empty($CFG->behat_usedeprecated)) {
            debugging('Function behat_base::ensure_editors_are_loaded() is deprecated. It is no longer required.');
        }
        return;
    }

    /**
     * Change browser window size.
     *   - small: 640x480
     *   - medium: 1024x768
     *   - large: 2560x1600
     *
     * @param string $windowsize size of window.
     * @param bool $viewport If true, changes viewport rather than window size
     * @throws ExpectationException
     */
    protected function resize_window($windowsize, $viewport = false) {
        // Non JS don't support resize window.
        if (!$this->running_javascript()) {
            return;
        }

        switch ($windowsize) {
            case "small":
                $width = 1024;
                $height = 768;
                break;
            case "medium":
                $width = 1366;
                $height = 768;
                break;
            case "large":
                $width = 2560;
                $height = 1600;
                break;
            default:
                preg_match('/^(\d+x\d+)$/', $windowsize, $matches);
                if (empty($matches) || (count($matches) != 2)) {
                    throw new ExpectationException("Invalid screen size, can't resize", $this->getSession());
                }
                $size = explode('x', $windowsize);
                $width = (int) $size[0];
                $height = (int) $size[1];
        }
        if ($viewport) {
            // When setting viewport size, we set it so that the document width will be exactly
            // as specified, assuming that there is a vertical scrollbar. (In cases where there is
            // no scrollbar it will be slightly wider. We presume this is rare and predictable.)
            // The window inner height will be as specified, which means the available viewport will
            // actually be smaller if there is a horizontal scrollbar. We assume that horizontal
            // scrollbars are rare so this doesn't matter.
            $js = <<<EOF
return (function() {
    var before = document.body.style.overflowY;
    document.body.style.overflowY = "scroll";
    var result = {};
    result.x = window.outerWidth - document.body.offsetWidth;
    result.y = window.outerHeight - window.innerHeight;
    document.body.style.overflowY = before;
    return result;
})();
EOF;
            $offset = $this->evaluate_script($js);
            $width += $offset['x'];
            $height += $offset['y'];
        }

        $this->getSession()->getDriver()->resizeWindow($width, $height);
    }

    /**
     * Waits for all the JS to be loaded.
     *
     * @return  bool Whether any JS is still pending completion.
     */
    public function wait_for_pending_js() {
        return static::wait_for_pending_js_in_session($this->getSession());
    }

    /**
     * Waits for all the JS to be loaded.
     *
     * @param   Session $session The Mink Session where JS can be run
     * @return  bool Whether any JS is still pending completion.
     */
    public static function wait_for_pending_js_in_session(Session $session) {
        if (!self::running_javascript_in_session($session)) {
            // JS is not available therefore there is nothing to wait for.
            return false;
        }

        // We don't use behat_base::spin() here as we don't want to end up with an exception
        // if the page & JSs don't finish loading properly.
        for ($i = 0; $i < self::get_extended_timeout() * 10; $i++) {
            $pending = '';
            try {
                $jscode = trim(preg_replace('/\s+/', ' ', '
                    return (function() {
                        if (document.readyState !== "complete") {
                            return "incomplete";
                        }

                        if (typeof M !== "object" || typeof M.util !== "object" || typeof M.util.pending_js === "undefined") {
                            return "";
                        }

                        return M.util.pending_js.join(":");
                    })()'));
                $pending = self::evaluate_script_in_session($session, $jscode);
            } catch (NoSuchWindow $nsw) {
                // We catch an exception here, in case we just closed the window we were interacting with.
                // No javascript is running if there is no window right?
                $pending = '';
            } catch (UnknownError $e) {
                // M is not defined when the window or the frame don't exist anymore.
                if (strstr($e->getMessage(), 'M is not defined') != false) {
                    $pending = '';
                }
            }

            // If there are no pending JS we stop waiting.
            if ($pending === '') {
                return true;
            }

            // 0.1 seconds.
            usleep(100000);
        }

        // Timeout waiting for JS to complete. It will be caught and forwarded to behat_hooks::i_look_for_exceptions().
        // It is unlikely that Javascript code of a page or an AJAX request needs more than get_extended_timeout() seconds
        // to be loaded, although when pages contains Javascript errors M.util.js_complete() can not be executed, so the
        // number of JS pending code and JS completed code will not match and we will reach this point.
        throw new \Exception('Javascript code and/or AJAX requests are not ready after ' .
                self::get_extended_timeout() .
                ' seconds. There is a Javascript error or the code is extremely slow. ' .
                'If you are using a slow machine, consider setting $CFG->behat_increasetimeout.');
    }

    /**
     * Internal step definition to find exceptions, debugging() messages and PHP debug messages.
     *
     * Part of behat_hooks class as is part of the testing framework, is auto-executed
     * after each step so no features will splicitly use it.
     *
     * @throws Exception Unknown type, depending on what we caught in the hook or basic \Exception.
     * @see Moodle\BehatExtension\Tester\MoodleStepTester
     */
    public function look_for_exceptions() {
        // Wrap in try in case we were interacting with a closed window.
        try {

            // Exceptions.
            $exceptionsxpath = "//div[@data-rel='fatalerror']";
            // Debugging messages.
            $debuggingxpath = "//div[@data-rel='debugging']";
            // PHP debug messages.
            $phperrorxpath = "//div[@data-rel='phpdebugmessage']";
            // Any other backtrace.
            $othersxpath = "(//*[contains(., ': call to ')])[1]";

            $xpaths = array($exceptionsxpath, $debuggingxpath, $phperrorxpath, $othersxpath);
            $joinedxpath = implode(' | ', $xpaths);

            // Joined xpath expression. Most of the time there will be no exceptions, so this pre-check
            // is faster than to send the 4 xpath queries for each step.
            if (!$this->getSession()->getDriver()->find($joinedxpath)) {
                // Check if we have recorded any errors in driver process.
                $phperrors = behat_get_shutdown_process_errors();
                if (!empty($phperrors)) {
                    foreach ($phperrors as $error) {
                        $errnostring = behat_get_error_string($error['type']);
                        $msgs[] = $errnostring . ": " .$error['message'] . " at " . $error['file'] . ": " . $error['line'];
                    }
                    $msg = "PHP errors found:\n" . implode("\n", $msgs);
                    throw new \Exception(htmlentities($msg));
                }

                return;
            }

            // Exceptions.
            if ($errormsg = $this->getSession()->getPage()->find('xpath', $exceptionsxpath)) {

                // Getting the debugging info and the backtrace.
                $errorinfoboxes = $this->getSession()->getPage()->findAll('css', 'div.alert-error');
                // If errorinfoboxes is empty, try find alert-danger (bootstrap4) class.
                if (empty($errorinfoboxes)) {
                    $errorinfoboxes = $this->getSession()->getPage()->findAll('css', 'div.alert-danger');
                }
                // If errorinfoboxes is empty, try find notifytiny (original) class.
                if (empty($errorinfoboxes)) {
                    $errorinfoboxes = $this->getSession()->getPage()->findAll('css', 'div.notifytiny');
                }

                // If errorinfoboxes is empty, try find ajax/JS exception in dialogue.
                if (empty($errorinfoboxes)) {
                    $errorinfoboxes = $this->getSession()->getPage()->findAll('css', 'div.moodle-exception-message');

                    // If ajax/JS exception.
                    if ($errorinfoboxes) {
                        $errorinfo = $this->get_debug_text($errorinfoboxes[0]->getHtml());
                    }

                } else {
                    $errorinfo = $this->get_debug_text($errorinfoboxes[0]->getHtml()) . "\n" .
                        $this->get_debug_text($errorinfoboxes[1]->getHtml());
                }

                $msg = "Moodle exception: " . $errormsg->getText() . "\n" . $errorinfo;
                throw new \Exception(html_entity_decode($msg));
            }

            // Debugging messages.
            if ($debuggingmessages = $this->getSession()->getPage()->findAll('xpath', $debuggingxpath)) {
                $msgs = array();
                foreach ($debuggingmessages as $debuggingmessage) {
                    $msgs[] = $this->get_debug_text($debuggingmessage->getHtml());
                }
                $msg = "debugging() message/s found:\n" . implode("\n", $msgs);
                throw new \Exception(html_entity_decode($msg));
            }

            // PHP debug messages.
            if ($phpmessages = $this->getSession()->getPage()->findAll('xpath', $phperrorxpath)) {

                $msgs = array();
                foreach ($phpmessages as $phpmessage) {
                    $msgs[] = $this->get_debug_text($phpmessage->getHtml());
                }
                $msg = "PHP debug message/s found:\n" . implode("\n", $msgs);
                throw new \Exception(html_entity_decode($msg));
            }

            // Any other backtrace.
            // First looking through xpath as it is faster than get and parse the whole page contents,
            // we get the contents and look for matches once we found something to suspect that there is a backtrace.
            if ($this->getSession()->getDriver()->find($othersxpath)) {
                $backtracespattern = '/(line [0-9]* of [^:]*: call to [\->&;:a-zA-Z_\x7f-\xff][\->&;:a-zA-Z0-9_\x7f-\xff]*)/';
                if (preg_match_all($backtracespattern, $this->getSession()->getPage()->getContent(), $backtraces)) {
                    $msgs = array();
                    foreach ($backtraces[0] as $backtrace) {
                        $msgs[] = $backtrace . '()';
                    }
                    $msg = "Other backtraces found:\n" . implode("\n", $msgs);
                    throw new \Exception(htmlentities($msg));
                }
            }

        } catch (NoSuchWindow $e) {
            // If we were interacting with a popup window it will not exists after closing it.
        } catch (DriverException $e) {
            // Same reason as above.
        }
    }

    /**
     * Converts HTML tags to line breaks to display the info in CLI
     *
     * @param string $html
     * @return string
     */
    protected function get_debug_text($html) {

        // Replacing HTML tags for new lines and keeping only the text.
        $notags = preg_replace('/<+\s*\/*\s*([A-Z][A-Z0-9]*)\b[^>]*\/*\s*>*/i', "\n", $html);
        return preg_replace("/(\n)+/s", "\n", $notags);
    }

    /**
     * Helper function to execute api in a given context.
     *
     * @param string $contextapi context in which api is defined.
     * @param array $params list of params to pass.
     * @throws Exception
     */
    protected function execute($contextapi, $params = array()) {
        if (!is_array($params)) {
            $params = array($params);
        }

        // Get required context and execute the api.
        $contextapi = explode("::", $contextapi);
        $context = behat_context_helper::get($contextapi[0]);
        call_user_func_array(array($context, $contextapi[1]), $params);

        // NOTE: Wait for pending js and look for exception are not optional, as this might lead to unexpected results.
        // Don't make them optional for performance reasons.

        // Wait for pending js.
        $this->wait_for_pending_js();

        // Look for exceptions.
        $this->look_for_exceptions();
    }

    /**
     * Get the actual user in the behat session (note $USER does not correspond to the behat session's user).
     * @return mixed
     * @throws coding_exception
     */
    protected function get_session_user() {
        global $DB;

        $sid = $this->getSession()->getCookie('MoodleSession');
        if (empty($sid)) {
            throw new coding_exception('failed to get moodle session');
        }
        $userid = $DB->get_field('sessions', 'userid', ['sid' => $sid]);
        if (empty($userid)) {
            throw new coding_exception('failed to get user from seession id '.$sid);
        }
        return $DB->get_record('user', ['id' => $userid]);
    }

    /**
     * Trigger click on node via javascript instead of actually clicking on it via pointer.
     *
     * This function resolves the issue of nested elements with click listeners or links - in these cases clicking via
     * the pointer may accidentally cause a click on the wrong element.
     * Example of issue: clicking to expand navigation nodes when the config value linkadmincategories is enabled.
     * @param NodeElement $node
     */
    protected function js_trigger_click($node) {
        if (!$this->running_javascript()) {
            $node->click();
        }
        $this->ensure_node_is_visible($node); // Ensures hidden elements can't be clicked.
        $xpath = $node->getXpath();
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof \Moodle\BehatExtension\Driver\MoodleSelenium2Driver) {
            $script = "Syn.click({{ELEMENT}})";
            $driver->triggerSynScript($xpath, $script);
        } else {
            $driver->click($xpath);
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * You should override this as appropriate for your plugin. The method
     * {@link behat_navigation::resolve_core_page_url()} is a good example.
     *
     * Your overridden method should document the recognised page types with
     * a table like this:
     *
     * Recognised page names are:
     * | Page            | Description                                                    |
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_url(string $page): moodle_url {
        throw new Exception('Component "' . get_class($this) .
                '" does not support the generic \'When I am on the "' . $page .
                '" page\' navigation step.');
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * A typical example might be:
     *     When I am on the "Test quiz" "mod_quiz > Responses report" page
     * which would cause this method in behat_mod_quiz to be called with
     * arguments 'Responses report', 'Test quiz'.
     *
     * You should override this as appropriate for your plugin. The method
     * {@link behat_navigation::resolve_core_page_instance_url()} is a good example.
     *
     * Your overridden method should document the recognised page types with
     * a table like this:
     *
     * Recognised page names are:
     * | Type      | identifier meaning | Description                                     |
     *
     * @param string $type identifies which type of page this is, e.g. 'Attempt review'.
     * @param string $identifier identifies the particular page, e.g. 'Test quiz > student > Attempt 1'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        throw new Exception('Component "' . get_class($this) .
                '" does not support the generic \'When I am on the "' . $identifier .
                '" "' . $type . '" page\' navigation step.');
    }

    /**
     * Gets the required timeout in seconds.
     *
     * @param int $timeout One of the TIMEOUT constants
     * @return int Actual timeout (in seconds)
     */
    protected static function get_real_timeout(int $timeout) : int {
        global $CFG;
        if (!empty($CFG->behat_increasetimeout)) {
            return $timeout * $CFG->behat_increasetimeout;
        } else {
            return $timeout;
        }
    }

    /**
     * Gets the default timeout.
     *
     * The timeout for each Behat step (load page, wait for an element to load...).
     *
     * @return int Timeout in seconds
     */
    public static function get_timeout() : int {
        return self::get_real_timeout(6);
    }

    /**
     * Gets the reduced timeout.
     *
     * A reduced timeout for cases where self::get_timeout() is too much
     * and a simple $this->getSession()->getPage()->find() could not
     * be enough.
     *
     * @return int Timeout in seconds
     */
    public static function get_reduced_timeout() : int {
        return self::get_real_timeout(2);
    }

    /**
     * Gets the extended timeout.
     *
     * A longer timeout for cases where the normal timeout is not enough.
     *
     * @return int Timeout in seconds
     */
    public static function get_extended_timeout() : int {
        return self::get_real_timeout(10);
    }

    /**
     * Return a list of the exact named selectors for the component.
     *
     * Named selectors are what make Behat steps like
     *   Then I should see "Useful text" in the "General" "fieldset"
     * work. Here, "fieldset" is the named selector, and "General" is the locator.
     *
     * If you override this method in your plugin (e.g. mod_mymod), to define
     * new selectors specific to your plugin. For example, if you returned
     *   new behat_component_named_selector('Thingy',
     *           [".//some/xpath//img[contains(@alt, %locator%)]/.."])
     * then
     *   Then I should see "Useful text" in the "Whatever" "mod_mymod > Thingy"
     * would work.
     *
     * This method should return a list of {@link behat_component_named_selector} and
     * the docs on that class explain how it works.
     *
     * @return behat_component_named_selector[]
     */
    public static function get_exact_named_selectors(): array {
        return [];
    }

    /**
     * Return a list of the partial named selectors for the component.
     *
     * Like the exact named selectors above, but the locator only
     * needs to match part of the text. For example, the standard
     * "button" is a partial selector, so:
     *   When I click "Save" "button"
     * will activate "Save changes".
     *
     * @return behat_component_named_selector[]
     */
    public static function get_partial_named_selectors(): array {
        return [];
    }

    /**
     * Return a list of the Mink named replacements for the component.
     *
     * Named replacements allow you to define parts of an xpath that can be reused multiple times, or in multiple
     * xpaths.
     *
     * This method should return a list of {@link behat_component_named_replacement} and the docs on that class explain
     * how it works.
     *
     * @return behat_component_named_replacement[]
     */
    public static function get_named_replacements(): array {
        return [];
    }

    /**
     * Evaluate the supplied script in the current session, returning the result.
     *
     * @param string $script
     * @return mixed
     */
    public function evaluate_script(string $script) {
        return self::evaluate_script_in_session($this->getSession(), $script);
    }

    /**
     * Evaluate the supplied script in the specified session, returning the result.
     *
     * @param Session $session
     * @param string $script
     * @return mixed
     */
    public static function evaluate_script_in_session(Session $session, string $script) {
        self::require_javascript_in_session($session);

        return $session->evaluateScript($script);
    }

    /**
     * Execute the supplied script in the current session.
     *
     * No result will be returned.
     *
     * @param string $script
     */
    public function execute_script(string $script) {
        self::execute_script_in_session($this->getSession(), $script);
    }

    /**
     * Excecute the supplied script in the specified session.
     *
     * No result will be returned.
     *
     * @param Session $session
     * @param string $script
     */
    public static function execute_script_in_session(Session $session, string $script) {
        self::require_javascript_in_session($session);

        $session->executeScript($script);
    }

    /**
     * Get the session key for the current session via Javascript.
     *
     * @return string
     */
    public function get_sesskey(): string {
        $script = <<<EOF
return (function() {
if (M && M.cfg && M.cfg.sesskey) {
    return M.cfg.sesskey;
}
return '';
})()
EOF;

        return $this->evaluate_script($script);
    }
}
