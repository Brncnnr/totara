<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../behat/behat_base.php');

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\DriverException;

/**
 * Behat steps specifically related to the appearance of elements.
 */
class behat_styles extends behat_base {

    /**
     * Confirms that a specific element has a CSS property with a specific value.
     *
     * @Then element :element should have a css property :property with a value of :value
     *
     * @param string $element Element we look in
     * @param string $property CSS property to inspect
     * @param string $value Value that the CSS property should have
     */
    public function element_should_have_a_css_property_with_a_value_of(string $element, string $property, string $value) {
        // Javascript is a requirement.
        if (!$this->running_javascript()) {
            throw new DriverException('Ability to confirm CSS properties for an element is not available with Javascript disabled');
        }

        // Get the property value.
        $property_value = $this->getSession()->evaluateScript(
            "return getComputedStyle(document.querySelector('{$element}')).getPropertyValue('{$property}')"
        );
        if ($property_value === null) {
            throw new ExpectationException(
                "Property '{$property}' not found for element '{$element}'",
                $this->getSession()
            );
        }

        // Validate the property value.
        if (trim($property_value) !== $value) {
            throw new ExpectationException(
                "Element '{$element}' property '{$property}' with value '{$property_value}' does not match expected '{$value}'",
                $this->getSession()
            );
        }
    }

    /**
     * Confirms that an image has the correct URL.
     *
     * @Then the URL for image nested in :element should match :regex
     *
     * @param string $element Element we look in
     * @param string $regex Regex to match
     */
    public function nested_image_url_should_match(string $element, string $regex) {
        $page = $this->getSession()->getPage();
        $node_element = $page->find('css', $element);

        // Element not found.
        if (empty($node_element)) {
            throw new ElementNotFoundException($this->getSession(), $element);
        }

        // Find nested image element.
        $image_element = $node_element->find('css', 'img');
        if (empty($image_element)) {
            throw new ElementNotFoundException($this->getSession(), $element);
        }

        $image_url = $image_element->getAttribute('src');
        if (!preg_match($regex, $image_url)) {
            throw new ExpectationException(
                "Image nested in '{$element}' with URL '{$image_url}' does not match '{$regex}'",
                $this->getSession()
            );
        }
    }

}
