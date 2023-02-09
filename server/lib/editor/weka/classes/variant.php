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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
namespace editor_weka;

use coding_exception;
use core\editor\abstraction\custom_variant_aware;
use core\editor\abstraction\extra_extension_aware_variant;
use core\editor\abstraction\usage_identifier_aware_variant;
use core\editor\abstraction\variant as variant_interface;
use core\editor\variant_name;
use editor_weka\extension\extension;
use editor_weka\factory\extension_loader;
use editor_weka\factory\variant_definition;
use editor_weka\local\extension_helper;
use totara_core\identifier\component_area;

/**
 * Variant implementation for editor_weka.
 */
final class variant implements variant_interface, usage_identifier_aware_variant,
    custom_variant_aware, extra_extension_aware_variant {
    /**
     * An array of all extension class name.
     * @var string[]
     */
    private $extension_classes;

    /**
     * The hash map of extension options where the key is the extension class name,
     * and the value is another hash-map of key => values that the extension instance
     * is needed.
     *
     * @var array
     */
    private $extension_options;

    /**
     * @var int
     */
    private $context_id;

    /**
     * @var string
     */
    private $variant_name;

    /**
     * Default to editor_weka/learn for the component area.
     * @var component_area
     */
    private $component_area;

    /**
     * @var int|null
     */
    private $instance_id;

    /**
     * variant constructor.
     * @param string $variant_name
     * @param int    $context_id
     */
    public function __construct(string $variant_name, int $context_id) {
        $this->variant_name = $variant_name;
        $this->context_id = $context_id;
        $this->extension_classes = [];
        $this->instance_id = null;

        $this->component_area = new component_area('editor_weka', 'learn');
        $this->extension_options = [];
    }

    /**
     * This function will try to invoke {@see extension::create()}
     * @return extension[]
     */
    public function get_extensions(): array {
        $options = [
            'component' => $this->component_area->get_component(),
            'area' => $this->component_area->get_area(),
            'context_id' => $this->context_id,
        ];

        if (!empty($this->instance_id)) {
            $options['instance_id'] = $this->instance_id;
        }

        $extension_options = $this->extension_options;
        return array_map(
            function (string $extension_class) use ($options, $extension_options) {
                $merge_options = $options;

                if (isset($extension_options[$extension_class])) {
                    $merge_options = array_merge(
                        $options,
                        $extension_options[$extension_class]
                    );
                }

                /** @see extension::create() */
                return call_user_func_array([$extension_class, 'create'], [$merge_options]);
            },
            $this->extension_classes
        );
    }

    /**
     * @return array[]
     */
    public function get_additional_options(): array {
        $extensions = $this->get_extensions();
        return [
            'extensions' => array_map(
                function (extension $extension): array {
                    return $extension->jsonSerialize();
                },
                $extensions
            ),
        ];
    }

    /**
     * @return int
     */
    public function get_context_id(): int {
        return $this->context_id;
    }

    /**
     * @return string
     */
    public function get_variant_name(): string {
        return $this->variant_name;
    }

    /**
     * @param string $variant_name
     * @param int    $context_id
     * @return variant_interface|variant
     */
    public static function create(string $variant_name, int $context_id): variant_interface {
        if (!variant_name::is_valid($variant_name) && !variant_definition::in_supported($variant_name)) {
            throw new coding_exception("Invalid variant name '{$variant_name}'");
        }

        $variant = new variant($variant_name, $context_id);
        $extension_metadata = extension_loader::get_extensions_for_variant($variant_name);

        $variant->extension_classes = $extension_metadata['extensions'] ?? [];

        if (array_key_exists('extensions_options', $extension_metadata)) {
            $variant->extension_options = $extension_metadata['extensions_options'];
        }

        return $variant;
    }

    /**
     * @param component_area $component_area
     * @return void
     */
    public function set_component_area(component_area $component_area): void {
        $this->component_area = $component_area;
    }

    /**
     * @return component_area
     */
    public function get_component_area(): component_area {
        return $this->component_area;
    }

    /**
     * @param int $instance_id
     * @return void
     */
    public function set_instance_id(int $instance_id): void {
        $this->instance_id = $instance_id;
    }

    /**
     * @return int|null
     */
    public function get_instance_id(): ?int {
        return $this->instance_id;
    }

    /**
     * @param array $extra_extensions
     * @return void
     */
    public function set_extra_extensions(array $extra_extensions): void {
        foreach ($extra_extensions as $extension_datum) {
            if (empty($extension_datum['name'])) {
                debugging(
                    "The extension data map does not provide the extension's name to add to the list of extensions",
                    DEBUG_DEVELOPER
                );

                continue;
            }

            $name = $extension_datum['name'];
            $extension_class_name = extension_helper::get_extension_class_name_from_extension_name($name);

            if ($extension_class_name === null) {
                // Extension does not exist.
                // We cannot require all extra-extensions to exist due to differences between editors, so just ignore it.
                continue;
            }

            $this->extension_classes[] = $extension_class_name;

            if (!empty($extension_datum['options']) && is_array($extension_datum['options'])) {
                $this->extension_options[$extension_class_name] = $extension_datum['options'];
            }
        }
    }
}