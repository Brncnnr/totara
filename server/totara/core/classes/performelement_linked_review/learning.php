<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 */

namespace totara_core\performelement_linked_review;

use coding_exception;
use context_user;
use core\collection;
use core\entity\course;
use core\format;
use core\orm\query\builder;
use core_course\theme\file\course_image;
use core_course\user_learning\item as course_item;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\models\activity\subject_instance;
use performelement_linked_review\content_type;
use performelement_linked_review\rb\helper\content_type_response_report;
use totara_certification\entity\certification;
use totara_core\formatter\learning_item_formatter;
use totara_core\hook\component_access_check;
use totara_core\user_learning\item;
use totara_core\user_learning\item_has_progress;
use totara_core\user_learning\item_helper;
use totara_core\user_learning\learning_items_helper;
use totara_program\entity\program;
use totara_program\user_learning\item as program_item;
use totara_certification\user_learning\item as certification_item;

class learning extends content_type {

    /**
     * @inheritDoc
     */
    public static function get_identifier(): string {
        return 'learning';
    }

    /**
     * @inheritDoc
     */
    public static function get_display_name(): string {
        return get_string('learning', 'totara_core');
    }

    /**
     * This content type is based on data from different tables
     * so we cannot rely on one alone
     * @see learning::validate_content()
     *
     * @return string
     */
    public static function get_table_name(): string {
        return '';
    }

    /**
     * @inheritDoc
     */
    public static function is_enabled(): bool {
        // The core course functionality cannot be disabled at the moment
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function get_content_type_settings(array $content_type_settings): array {
        global $PAGE, $USER;

        $course_image = new course_image($PAGE->theme);
        $course_image->set_tenant_id(!empty($USER->tenantid) ? $USER->tenantid : 0);
        $file = $course_image->get_current_or_default_url();

        $content_type_settings['default_image'] = (string) $file;

        return $content_type_settings;
    }

    /**
     * @inheritDoc
     */
    public static function clean_content_type_settings(array $content_type_settings): array {
        unset($content_type_settings['default_image']);

        return $content_type_settings;
    }

    /**
     * @inheritDoc
     */
    public function get_content_type_name(array $content) {
        // Now this is a bit of a hack as we have three different
        // "subtypes" grouped under this one type. To allow for reporting
        // to join the right tables and the content items to be loaded we
        // need to identify the subtype. We opted for using the itemtype for this.
        if (empty($content['itemtype'])) {
            throw new coding_exception('We need the itemtype to determine the final content type');
        }

        $content_type = null;
        switch ($content['itemtype']) {
            case 'course':
            case 'program':
            case 'certification':
                $content_type = 'learning_'.$content['itemtype'];
                break;
            default:
                throw new coding_exception('Unknown learning type given, unable to determine final content_type');
        }

        return $content_type;
    }

    /**
     * @inheritDoc
     */
    public function get_metadata(int $user_id, array $content): array {
        /** @var item $item_class */
        switch ($content['itemtype']) {
            case 'course':
                $item_class = course_item::class;
                break;
            case 'program':
                $item_class = program_item::class;
                break;
            case 'certification':
                $item_class = certification_item::class;
                break;
            default:
                throw new coding_exception('Unknown learning type given, unable to determine learning item');
        }

        $item = $item_class::one($user_id, $content['id']);
        $progress = null;

        // Make sure we have the percentage in the progress.
        if (method_exists($item, 'get_progress_percentage')) {
            $progress = $item->get_progress_percentage();
        }

        return [
            'type' => $content['itemtype'],
            'id' => $content['id'],
            'progress' => $progress
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_admin_settings_component(): ?string {
        return 'totara_core/components/performelement_linked_review/learning/AdminEdit';
    }

    /**
     * @inheritDoc
     */
    public static function get_available_settings(): array {
        return [];
    }

    /**
     * @param array $settings
     * @return array
     */
    public static function get_display_settings(array $settings): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function get_content_picker_component(): string {
        return 'totara_core/components/performelement_linked_review/learning/ParticipantContentPicker';
    }

    /**
     * @inheritDoc
     */
    public static function get_participant_content_component(): string {
        return 'totara_core/components/performelement_linked_review/learning/ParticipantContent';
    }

    /**
     * @inheritDoc
    */
    public static function get_admin_view_component(): string {
        return 'totara_core/components/performelement_linked_review/learning/AdminView';
    }

    /**
     * @inheritDoc
     */
    public function load_content_items(
        subject_instance $subject_instance,
        collection $content_items,
        ?participant_section_entity $participant_section,
        bool $can_view_other_responses,
        int $created_at
    ): array {
        if ($content_items->count() === 0) {
            return [];
        }

        // Organise type ID's.
        $type_ids = [];
        foreach ($content_items as $content_item) {
            $type = explode('_', $content_item->content_type);
            $type_ids[$type[1]][] = $content_item->content_id;
        }

        // Get data providers.
        $data_providers = [];
        foreach ($type_ids as $type => $ids) {
            $data_provider = item_helper::get_data_provider($type);
            $data_provider->set_filters([
                'user_id' => $subject_instance->subject_user_id,
                'ids' => $ids
            ]);
            $data_providers[] = $data_provider;
        }

        // Get context.
        $context = context_user::instance($subject_instance->subject_user_id);

        $learning = learning_items_helper::get_learning_items($data_providers, $subject_instance->subject_user_id);
        $result = [];
        foreach ($learning['items'] as $item) {
            // We could have used $item->get_unique_id() here, but we need 'learning_' to be part
            // of the content type.
            $result["{$item->id}-learning_{$item->get_type()}"] = $this->create_result_item($item, $context);
        }

        return $result;
    }

    /**
     * Create the data for one learning content item.
     *
     * @param item $item
     * @param context_user $context
     *
     * @return array
     */
    private function create_result_item(item $item, context_user $context): array {
        $formatter = new learning_item_formatter($item, $context);

        return [
            'id' => $item->id,
            'fullname' => $formatter->format('fullname', format::FORMAT_PLAIN),
            'description' => $formatter->format('description', format::FORMAT_HTML),
            'url_view' => $item->url_view->out(),
            'image_src' => $item->get_image(),
            'itemtype' => $item->get_type(),
            'progress' => $this->get_progress_percentage($item),
        ];
    }

    /**
     * @param item $item
     *
     * @return int|null
     */
    private function get_progress_percentage(item $item): ?int {
        if ($item instanceof item_has_progress && $item->can_be_completed()) {
            return $item->get_progress_percentage();
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public static function get_response_report_helper(): content_type_response_report {
        return new response_report();
    }

    /**
     * @inheritDoc
     */
    public static function get_component(): string {
        return 'totara_core';
    }

    /**
     * @inheritDoc
     */
    public static function validate_content(array $content): void {
        $content_type_ids = [];
        foreach ($content as $item) {
            $content_type = $item['itemtype'] ?? null;
            $content_id = $item['id'] ?? null;

            if (empty($content_type)) {
                throw new coding_exception('Missing content type');
            }
            if (empty($content_id)) {
                throw new coding_exception('Missing content id');
            }

            $content_type_ids[$content_type][] = $content_id;
        }

        foreach ($content_type_ids as $content_type => $content_ids) {
            switch ($content_type) {
                case 'course':
                    $content_count = builder::table(course::TABLE)->where_in('id', $content_ids)->count();
                    break;
                case 'program':
                case 'certification':
                    $content_count = builder::table(program::TABLE)->where_in('id', $content_ids)->count();
                    break;
                default:
                    $content_count = 0;
            }

            if ($content_count !== count($content_ids)) {
                throw new coding_exception(
                    'Not all the specified content IDs actually exist. ' .
                    'Specified IDs: ' . json_encode($content_ids) .
                    ', Number of IDs in the ' . $content_type . ' table: ' . $content_count
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function is_for_access_hook(component_access_check $hook): bool {
        // The component 'totara_core' is a bit too broad so we check for 'user_learning'.
        return $hook->get_component_name() === 'user_learning';
    }
}
