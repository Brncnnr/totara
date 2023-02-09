<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\userdata;

use context;
use context_system;
use core\orm\query\builder;
use stored_file;
use totara_evidence\customfield_area\evidence;
use totara_evidence\customfield_area\field_helper;
use totara_evidence\entity\evidence_field_data;
use totara_evidence\entity\evidence_item;
use totara_evidence\entity\evidence_type;
use totara_evidence\event\evidence_item_deleted;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

/**
 * User data related to evidence items. Includes all items and associated date in the custom fields.
 */
class evidence_items {

    public const CREATED_BY_SELF = 'self';
    public const CREATED_BY_OTHER = 'other';

    /**
     * @var target_user
     */
    private $user;

    /**
     * @var context
     */
    private $context;

    /**
     * @var string
     */
    private $created_by;

    /**
     * evidence_items constructor.
     *
     * @param target_user $user
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @param string $created_by see CREATED_BY_ constants for possible values
     */
    public function __construct(target_user $user, context $context, string $created_by) {
        $this->user = $user;
        $this->context = $context;
        $this->created_by = $created_by;
    }

    /**
     * Factory method
     *
     * @param target_user $user
     * @param context $context
     * @return evidence_items
     */
    public static function create_self(target_user $user, context $context): self {
        return new static($user, $context, self::CREATED_BY_SELF);
    }

    /**
     * Factory method
     *
     * @param target_user $user
     * @param context $context
     * @return evidence_items
     */
    public static function create_other(target_user $user, context $context): self {
        return new static($user, $context, self::CREATED_BY_OTHER);
    }

    /**
     * @return string
     */
    protected function get_created_operator(): string {
        return $this->created_by === self::CREATED_BY_SELF ? '=' : '<>';
    }

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @return bool
     */
    public function purge(): bool {
        // Get all items associated to the user
        $items = evidence_item::repository()
            ->where('user_id', $this->user->id)
            ->where('created_by', $this->get_created_operator(), $this->user->id)
            ->get_lazy();

        /** @var evidence_item $item */
        foreach ($items as $item) {
            $event = builder::get_db()->transaction(function () use ($item) {
                /** @var evidence_field_data $data */
                foreach ($item->data as $data) {
                    field_helper::get_field_instance($data)->delete();
                }

                $event = evidence_item_deleted::create_from_item($item);
                $item->delete();

                return $event;
            });

            $event->trigger();
        }

        return true;
    }

    /**
     * Export user data from this item.
     *
     * @return export
     */
    public function export(): export {
        $export = new export();

        // Get all items associated to the user
        $items = evidence_item::repository()
            ->where('user_id', $this->user->id)
            ->where('created_by', $this->get_created_operator(), $this->user->id)
            ->get_lazy();

        $data = ['items' => []];
        $types = [];
        /** @var evidence_item $item */
        foreach ($items as $item) {
            // Cache each type
            if (!isset($types[$item->typeid])) {
                $types[$item->typeid] = new evidence_type($item->typeid);
            }
            $type = $types[$item->typeid];

            $item_export = [
                'id' => $item->id,
                'typeid' => $type->id,
                'type' => format_string($type->name),
                'name' => format_string($item->name),
                'status' => (int)$item->status,
                'created_by' => (int)$item->created_by,
                'created_at' => (int)$item->created_at,
                'modified_by' => (int)$item->modified_by,
                'modified_at' => (int)$item->modified_at,
            ];

            $item_export['fields'] = [];
            /** @var evidence_field_data $field_data */
            foreach ($item->data as $field_data) {
                $field = $field_data->field;

                $field_export = [
                    'type' => $field->datatype,
                    'label' => $field->fullname,
                    'value' => field_helper::get_field_class($field->datatype)::display_item_data($field_data->data, [
                        'isexport' => true,
                        'prefix'   => evidence::get_prefix(),
                        'itemid'   => $field_data->id,
                        'extended' => true
                    ])
                ];

                $files = $this->get_files($field_data);
                $field_files = [];
                foreach ($files as $file) {
                    $file = $export->add_file($file);
                    $field_files[] = $file;
                }

                if (!empty($field_files)) {
                    $field_export['files'] = $field_files;
                }
                $item_export['fields'][] = $field_export;
            }
            $data['items'][] = $item_export;
        }

        $export->data = $data;

        return $export;
    }

    /**
     * Count user data for this item.
     *
     * @return int
     */
    public function count(): int {
        return evidence_item::repository()
            ->where('user_id', $this->user->id)
            ->where('created_by', $this->get_created_operator(), $this->user->id)
            ->count();
    }

    /**
     * @param evidence_field_data $data
     * @return stored_file[]
     */
    protected function get_files(evidence_field_data $data): array {
        $context = context_system::instance();

        $fileareas = evidence::get_fileareas();

        $files = [];
        foreach ($fileareas as $filearea) {
            $area_files = get_file_storage()->get_area_files(
                $context->id,
                'totara_customfield',
                $filearea,
                $data->id,
                "itemid, filepath, filename",
                false
            );

            $files[] = $area_files;
        }
        return array_merge(...$files);
    }
}