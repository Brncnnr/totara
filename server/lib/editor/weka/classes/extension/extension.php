<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
namespace editor_weka\extension;

use coding_exception;
use context_system;
use JsonSerializable;

/**
 * An extension is a collection of nodes of json_editor. Don't get mixed up between the extension and the node,
 * as node is just single unit, where as the extension can be either a single unit or multiple units. However, the
 * extension in this very context is to tell the weka editor which front-end component to be pulled, so that
 * at the front-end the editor can be constructed properly.
 */
abstract class extension implements JsonSerializable {
    /**
     * This property is no longer used. However, it is in here to allow PHPStorm trigger warning.
     *
     * @var null
     * @deprecated since Totara 13.3
     */
    private $component;

    /**
     * This property is no longer used. However, it is in here to allow PHPStorm trigger warning.
     *
     * @var null
     * @deprecated since Totara 13.3
     */
    private $area;

    /**
     * This property is no longer used. However, it is in here to allow PHPStorm trigger warning.
     *
     * @var null
     * @deprecated since Totara 13.3
     */
    private $contextid;

    /**
     * extension constructor.
     * Preventing any complicated construction of the children.
     *
     * @param string|null $component    Deprecated
     * @param string|null $area         Deprecated
     * @param int|null    $contextid    Deprecated
     */
    public function __construct(?string $component = null, ?string $area = null,
                                      ?int $contextid = null) {
        if (!empty($component)) {
            debugging(
                "The parameter '\$component' had been deprecated and no longer used, please update your caller",
                DEBUG_DEVELOPER
            );
        }

        if (!empty($area)) {
            debugging(
                "The parameter '\$area' had been deprecated and no longer used, please update your caller",
                DEBUG_DEVELOPER
            );
        }

        if (!empty($contextid)) {
            debugging(
                "The parameter '\$contextid' had been deprecated and no longer used, please pdate your caller",
                DEBUG_DEVELOPER
            );
        }

        $this->component = null;
        $this->area = null;
        $this->contextid = null;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    final public function __get(string $name) {
        switch ($name) {
            case 'component':
                debugging(
                    "The property 'component' had been deprecated and there is no alternative. Please update all calls.",
                    DEBUG_DEVELOPER
                );

                // Default value for component.
                return 'editor_weka';

            case 'area':
                debugging(
                    "The property 'area' had been deprecated and there is no alternative. Please update all calls.",
                    DEBUG_DEVELOPER
                );

                // Default value for area
                return 'default';

            case 'contextid':
                debugging(
                    "The property 'contextid' had been deprecated and there is no alternative. Please update all calls.",
                    DEBUG_DEVELOPER
                );

                // Default value for contextid
                return context_system::instance()->id;

            default:
                throw new coding_exception(
                    "Magic function '__get' is not supported for property '{$name}'"
                );

        }
    }

    /**
     * @param string        $name
     * @param mixed|null    $value
     *
     * @return void
     */
    final public function __set(string $name, $value): void {
        switch ($name) {
            case 'component':
            case 'area':
            case 'contextid':
                debugging(
                    "The property '{$name}' had been deprecated and there is no alternative. Please update all calls.",
                    DEBUG_DEVELOPER
                );

                return;

            default:
                throw new coding_exception(
                    "Magic function '__set' is not supported for property '{$name}'"
                );
        }
    }

    /**
     * @param array $options
     * @return extension
     */
    public static function create(array $options): extension {
        $extension = new static();
        $extension->set_options($options);

        return $extension;
    }

    /**
     * @return string
     */
    abstract public function get_js_path(): string;

    /**
     * Let the children override this function. Basically, that an extension should be configured
     * via configuration files.
     *
     * @param array $options
     * @return void
     */
    public function set_options(array $options): void {
    }

    /**
     * Returning the array of dependencies that the extensions in the front-end need.
     * @return array
     */
    public function get_js_parameters(): array {
        return [];
    }

    /**
     * @return string
     */
    public static function get_extension_name(): string {
        $cls = get_called_class();
        $parts = explode("\\", $cls);

        $first = reset($parts);
        $last = end($parts);

        if ('editor_weka' === $first) {
            // If it is from editor_weka, then just return the extension name,
            // without any prefixing.
            return $last;
        }

        // Otherwise, prefixing the component in, as we do want prevent the chance for extensions
        // to get duplicated.
        return "{$first}_{$last}";
    }

    /**
     * Get a list of include-based variants that this extension should be added to.
     *
     * This should only be used for extensions defined in plugins, it will be ignored
     * for core Weka extensions.
     *
     * Extensions that do not implement specific_custom_extension will be added to
     * exclude-based variants regardless, so it's not neccesary to list exclude-based
     * variants here.
     *
     * @return string[]
     */
    public static function get_included_in_variants(): array {
        return [];
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array {
        return [
            'name' => static::get_extension_name(),
            'tuicomponent' => $this->get_js_path(),
            'options' => $this->get_js_parameters()
        ];
    }
}