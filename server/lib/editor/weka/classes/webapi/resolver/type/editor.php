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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
namespace editor_weka\webapi\resolver\type;

use coding_exception;
use context_system;
use context_user;
use core\editor\variant_name;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use editor_weka\local\file_helper;
use editor_weka\variant;
use totara_core\identifier\component_area;
use weka_texteditor;

/**
 * Resolver for type editor.
 */
class editor extends type_resolver {
    /**
     * @param string            $field
     * @param weka_texteditor  $source
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed|void
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/lib/editor/weka/lib.php");

        if (!($source instanceof weka_texteditor)) {
            $cls = weka_texteditor::class;
            throw new coding_exception("Expecting source to be an instance of '{$cls}'");
        }

        switch ($field) {
            case 'extensions':
                if (!empty($args['component'])) {
                    debugging(
                        "The parameter 'component' had been deprecated, please use 'usage_identifier' instead",
                        DEBUG_DEVELOPER
                    );
                }

                if (!empty($args['area'])) {
                    debugging(
                        "The parameter 'area' had been deprecated, please use 'usage_identifier' instead",
                        DEBUG_DEVELOPER
                    );
                }

                $variant_name = $args['variant_name'] ?? variant_name::STANDARD;
                $context_id = $source->get_context_id();

                if (empty($context_id)) {
                    $context_id = context_system::instance()->id;

                    if ($ec->has_relevant_context()) {
                        $context_id = $ec->get_relevant_context()->id;
                    }
                }

                $variant = variant::create($variant_name, $context_id);
                if (isset($args['usage_identifier'])) {
                    /** @var array $usage_identifier */
                    $usage_identifier = $args['usage_identifier'];
                    $variant->set_component_area(
                        new component_area($usage_identifier['component'], $usage_identifier['area'])
                    );

                    if (!empty($usage_identifier['instance_id'])) {
                        $variant->set_instance_id($usage_identifier['instance_id']);
                    }
                }

                if (isset($args['extra_extensions'])) {
                    $extra_extensions = json_decode(
                        $args['extra_extensions'],
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );

                    $variant->set_extra_extensions($extra_extensions);
                }

                return $variant->get_extensions();

            case 'showtoolbar':
                if (!empty($args['component'])) {
                    debugging(
                        "The parameter 'component' had been deprecated and no longer used. Please update all calls",
                        DEBUG_DEVELOPER
                    );
                }

                if (!empty($args['area'])) {
                    debugging(
                        "The parameter 'area' had been deprecated and no longer used. Please update all calls",
                        DEBUG_DEVELOPER
                    );
                }

                return $source->show_toolbar();

            case 'files':
                $item_id = $args['item_id'] ?? null;
                // Keep files working but use draft_files instead

            case 'draft_files':
                $item_id = $item_id ?? $args['draft_item_id'] ?? null;
                if (empty($item_id)) {
                    return [];
                }

                require_once("{$CFG->dirroot}/lib/filelib.php");
                $fs = get_file_storage();

                $user_context = context_user::instance($USER->id);
                return array_values(
                    $fs->get_area_files(
                        $user_context->id,
                        'user',
                        'draft',
                        $item_id,
                        'itemid, filepath, filename',
                        false
                    )
                );

            case 'repository_data':
                $context_id = $source->get_context_id();
                if (null === $context_id) {
                    $context_id = context_system::instance()->id;
                }

                return file_helper::get_upload_repository($context_id);

            case 'context_id':
                $context_id = $source->get_context_id();
                if (null === $context_id) {
                    return context_system::instance()->id;
                }

                return $context_id;

            default:
                debugging("No field '{$field}' found for type editor", DEBUG_DEVELOPER);
                return null;
        }
    }
}