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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review;

use coding_exception;
use context;
use core\collection;
use core\orm\query\builder;
use mod_perform\entity\activity\participant_section;
use mod_perform\models\activity\subject_instance;
use performelement_linked_review\models\linked_review_content;
use performelement_linked_review\rb\helper\content_type_response_report;
use totara_core\hook\component_access_check;

/**
 * This is the base class for all linked review content types.
 *
 * Extend this class in another plugin to make a new type available.
 * Make sure all the functions required return valid values.
 *
 * The @see content_type::load_content_items() method has to return
 * the content which the VUE component @see content_type::get_participant_content_component()
 * uses to display the item.
 *
 * @package performelement_linked_review
 */
abstract class content_type {

    /**
     * @var context
     */
    protected $context;

    /**
     * @param context $context
     */
    public function __construct(context $context) {
        $this->context = $context;
    }

    /**
     * Associated component
     *
     * @return string
     */
    abstract public static function get_component(): string;

    /**
     * The unique name identifier of this content type.
     *
     * @return string
     */
    abstract public static function get_identifier(): string;

    /**
     * The display name of this content type.
     * Shown when selecting it from menus etc.
     *
     * @return string
     */
    abstract public static function get_display_name(): string;

    /**
     * Get the database table that the content ID is a foreign key for.
     *
     * @return string
     */
    abstract public static function get_table_name(): string;

    /**
     * Is this content type enabled?
     *
     * @return bool
     */
    abstract public static function is_enabled(): bool;

    /**
     * The component path of the vue component for rendering on the admin view.
     *
     * @return string
     */
    abstract public static function get_admin_view_component(): string;

    /**
     * The component path of the vue component for allowing admins to configure extra settings. (Optional)
     *
     * @return string|null
     */
    abstract public static function get_admin_settings_component(): ?string;

    /**
     * Array of available settings that can be configured by the admin (keys) and their default values (values)
     *
     * Example: ['allow_rating' => true, 'show_description' => false]
     *
     * @return array
     */
    abstract public static function get_available_settings(): array;

    /**
     * Returns the settings in a human readable form.
     * The key is the display name of the setting and the value is human readable form of the value.
     *
     * MUST make sure that any output is formatted correctly to prevent XSS risk.
     *
     * @example
     *
     * [
     *     'Is rating enabled?' => 'Yes',
     *     'Final rating participant' => 'Manager'
     * ]
     *
     * @param array $settings
     * @return array
     */
    abstract public static function get_display_settings(array $settings): array;

    /**
     * Apply any additional processing to the content type settings.
     *
     * @param array $content_type_settings
     * @return array
     */
    public static function get_content_type_settings(array $content_type_settings): array {
        return $content_type_settings;
    }

    /**
     * Remove/clean any unwanted settings attributes before saving.
     *
     * @param array $content_type_settings
     * @return array
     */
    public static function clean_content_type_settings(array $content_type_settings): array {
        return $content_type_settings;
    }

    /**
     * The component path of the vue component for picking the content items.
     *
     * @return string
     */
    abstract public static function get_content_picker_component(): string;

    /**
     * The component path of the vue component for rendering the content response display.
     *
     * @return string
     */
    abstract public static function get_participant_content_component(): string;

    /**
     * Get the content type name for this content type, usually identical to the identifier
     * but can be overridden.
     *
     * @param array $content
     * @return string
     */
    public function get_content_type_name(array $content) {
        return static::get_identifier();
    }

    /**
     * Returns additional metadata for the given type to be stored with the content
     *
     * @param int $user_id the user_id this content is for
     * @param array $content
     * @return array
     */
    public function get_metadata(int $user_id, array $content): array {
        return [];
    }

    /**
     * This function is responsible for loading the actual items when requested by the
     *
     * @see \performelement_linked_review\webapi\resolver\query\content_items query.
     * This data is injected in the content items and used for display in the
     * VUE component returned by @see content_type::get_participant_content_component().
     *
     * Make sure this method returns the array keyed by the content_ids passed in
     * otherwise the content won't be returned to the frontend.
     *
     * Each individual content item returned needs to have an id property or key.
     *
     * @param subject_instance $subject_instance The subject instance the content is for
     * @param linked_review_content[]|collection $content_items
     * @param participant_section|null $participant_section The participant section of the user viewing the content
     * @param bool $can_view_other_responses
     * @param int $created_at the timestamp the content got created, this might be needed for point in time / static data
     * @return array[] Array of content items, keyed by the ID of each item. Each content item must be an array itself.
     */
    abstract public function load_content_items(
        subject_instance $subject_instance,
        collection $content_items,
        ?participant_section $participant_section,
        bool $can_view_other_responses,
        int $created_at
    ): array;

    /**
     * Returns helper for the content type needed for the response report
     *
     * @return content_type_response_report
     */
    abstract public static function get_response_report_helper(): content_type_response_report;

    /**
     * The component path of the vue component for rendering the footer.
     *
     * @return string
     */
    public static function get_participant_content_footer_component(): string {
        return '';
    }

    /**
     * The component path of the vue component for rendering the admin footer.
     *
     * @return string
     */
    public static function get_admin_content_footer_component(): string {
        return '';
    }

    /**
     * Is the hook for this content type?
     *
     * @param component_access_check $hook
     * @return string
     */
    public static function is_for_access_hook(component_access_check $hook): bool {
        return false;
    }

    /**
     * Validate the content, can be overridden if different logic is needed
     *
     * @param array $content
     * @throws coding_exception
     */
    public static function validate_content(array $content): void {
        $content_ids = [];
        foreach ($content as $item) {
            if (is_int($item)) {
                $content_ids[] = $item;
            } else {
                $content_id = $item['id'] ?? null;
                if (empty($content_id)) {
                    throw new coding_exception('Missing content id');
                }
                $content_ids[] = $content_id;
            }
        }

        $content_table = static::get_table_name();
        $content_count = builder::table($content_table)->where_in('id', $content_ids)->count();
        if ($content_count !== count($content_ids)) {
            throw new coding_exception(
                'Not all the specified content IDs actually exist. ' .
                'Specified IDs: ' . json_encode($content_ids) .
                ', Number of IDs in the ' . $content_table . ' table: ' . $content_count
            );
        }
    }

}
