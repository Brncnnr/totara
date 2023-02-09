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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\webapi\resolver\type;

use coding_exception;
use context_system;
use core\editor\abstraction\context_aware_editor;
use core\editor\abstraction\custom_variant_aware;
use core\editor\abstraction\extra_extension_aware_variant;
use core\editor\abstraction\usage_identifier_aware_variant;
use core\editor\abstraction\variant;
use core\editor\fallback_variant;
use core\editor\variant_name;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use texteditor;
use totara_core\identifier\component_area;

class editor extends type_resolver {
    /**
     * @param string            $field
     * @param texteditor        $editor
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed|null
     */
    public static function resolve(string $field, $editor, array $args, execution_context $ec) {
        if (!is_a($editor, texteditor::class)) {
            throw new coding_exception("Expecting the argument type of " . texteditor::class);
        }

        switch ($field) {
            case 'js_module':
                return $editor->get_js_module($args['framework'] ?? '');

            case 'name':
                $class_name = get_class($editor);
                [$editor_name] = explode('_', $class_name, 2);

                return $editor_name;

            case 'context_id':
                return static::get_context_id($editor, $ec);

            case 'variant':
                $class_name = get_class($editor);
                [$editor_name] = explode('_', $class_name, 2);

                $variant_class = "\\editor_{$editor_name}\\variant";
                if (!class_exists($variant_class)) {
                    // Fall back to the fallback_variant, this is for the editor plugin
                    // that had not able to add the variant yet.
                    $variant_class = fallback_variant::class;
                }

                $variant_name = $args['variant_name'] ?? variant_name::STANDARD;

                if (!in_array(custom_variant_aware::class, class_implements($variant_class))) {
                    // Note that we do not run the validation for custom variant aware instance,
                    // because we want to leave the validation at the implementation of the variant.
                    // Therefore, it can have a custom variant name that it can understand - for backward compatible only.
                    variant_name::validate($variant_name);
                }

                $context_id = self::get_context_id($editor, $ec);

                /** @see variant::create() */
                $variant = call_user_func_array([$variant_class, 'create'], [$variant_name, $context_id]);

                if (!empty($args['extra_extensions']) && $variant instanceof extra_extension_aware_variant) {
                    // Set the extra extensions for the variant if the variant instance is an extra extension aware variant.
                    $extra_extensions = json_decode(
                        $args['extra_extensions'],
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );

                    $variant->set_extra_extensions($extra_extensions);
                }

                if ($variant instanceof usage_identifier_aware_variant && isset($args['usage_identifier'])) {
                    /** @var array $usage_identifier */
                    $usage_identifier = $args['usage_identifier'];
                    $variant->set_component_area(new component_area($usage_identifier['component'], $usage_identifier['area']));

                    if (isset($usage_identifier['instance_id'])) {
                        $variant->set_instance_id($usage_identifier['instance_id']);
                    }
                }

                return $variant;

            default:
                debugging("The field '{$field}' is not supported yet", DEBUG_DEVELOPER);
                return null;
        }
    }

    /**
     * @param texteditor        $editor
     * @param execution_context $ec
     *
     * @return int
     */
    private static function get_context_id(texteditor $editor, execution_context $ec): int {
        if ($editor instanceof context_aware_editor) {
            $context_id = $editor->get_context_id();

            if (!empty($context_id)) {
                return $context_id;
            }
        }

        $context = context_system::instance();
        if ($ec->has_relevant_context()) {
            $context = $ec->get_relevant_context();
        }

        return $context->id;
    }
}