<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author  Joby Harding <joby.harding@totaralms.com>
 * @author  Petr Skoda <petr.skoda@totaralms.com>
 * @package core
 */

/**
 * Information located pix/flex_icons.php files is merged to obtain
 * a full map of all flex icon definitions. Themes have the highest
 * priority and can override any map or translation item.
 */

/*
 * Translations array is expected to be used in plugins pix/flex_icons.php only.
 *
 * The data format is: array('mod_xxxx|someicon' => 'mapidentifier', 'mod_xxxx|otehricon' => 'mapidentifierx')
 */
$aliases = array(
    // NOTE: do not add anything here in core, use the $icons instead!
);

/*
 * Font icon map - this definition tells us how is each icon constructed.
 *
 * The identifiers in this core map are expected to be general
 * shape descriptions not directly related to Totara.
 *
 * In plugins the map is used when plugin needs a completely new icon
 * that is not defined here in core.
 */
$icons = array(
    /* Do not use 'flex-icon-missing' directly, it indicates requested icon was not found */
    'flex-icon-missing' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-questionmark_exclamation'
                ),
        ),
    'activate' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-right-circle',
                ),
        ),
    'alarm' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-alarm',
                ),
        ),
    'alarm-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-alarm_lightning'
                ),
        ),
    'alarm-warning' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-Alarm-warning'
                ),
        ),
    'archive' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-archive',
                ),
    ),
    'arrow-down' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-down',
                ),
        ),
    'arrow-left' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-left ft-flip-rtl',
                ),
        ),
    'arrow-right' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-right ft-flip-rtl',
                ),
        ),
    'arrow-up' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-up1',
                ),
        ),
    'arrows' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrows-move',
                ),
        ),
    'arrows-alt' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrows-fullscreen',
                ),
        ),
    'arrows-h' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-doublehead_arrow',
                ),
        ),
    'arrows-v' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrows-v',
                ),
        ),
    'attachment' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-paperclip',
                ),
        ),
    'back-arrow' =>
        array (
            'data' =>
                array (
                    'classes' => 'tfont-var-chevron-left ft-flip-rtl'
                )
        ),
    'backpack' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-backpack1',
                ),
        ),
    'badge' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-trophy2',
                ),
        ),
    'ban' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-slash-circle',
                ),
        ),
    'bars' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-list',
                ),
        ),
    'bar-chart' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-bar-chart',
                ),
        ),
    'blended' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-computer_people',
                ),
        ),
    'block-dock' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-left-square ft-flip-rtl',
                ),
        ),
    'block-hide' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-dash-square',
                ),
        ),
    'block-show' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-plus-square',
                ),
        ),
    'block-undock' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-right-square ft-flip-rtl',
                ),
        ),
    'bookmark' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-bookmark',
                ),
        ),
    'books' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-books',
                ),
        ),
    'cache' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-lightning',
                ),
        ),
    'calculator' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-calculator-fill',
                ),
        ),
    'calculator-off' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-calculator_slash_filled'
                ),
        ),
    'calendar' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-calendar3',
                ),
        ),
    'caret-down' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-down-fill',
                ),
        ),
    'caret-left' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-left-fill ft-flip-rtl',
                ),
        ),
    'caret-left-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-left-fill ft-state-disabled',
                ),
        ),
    'caret-left-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-left-fill ft-state-info ft-flip-rtl',
                ),
        ),
    'caret-right' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-right-fill ft-flip-rtl',
                ),
        ),
    'caret-right-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-right-fill ft-flip-rtl ft-state-disabled',
                ),
        ),
    'caret-right-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-right ft-state-info',
                ),
        ),
    'caret-up' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-up-fill',
                ),
        ),
    'certification' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-certificate1',
                ),
        ),
    'check' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check',
                ),
        ),
    'check-circle' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check-circle-fill',
                ),
        ),
    'check-circle-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check-circle',
                ),
        ),
    'check-circle-o-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-circle-o ft-state-success',
                ),
        ),
    'check-circle-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check-circle ft-state-success',
                ),
        ),
    'check-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check ft-state-disabled',
                ),
        ),
    'check-square-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check-square',
                ),
        ),
    'check-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check ft-state-success',
                ),
        ),
    'check-warning' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check ft-state-warning',
                ),
        ),
    'checklist' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-clipboard-check',
                ),
        ),
    'chevron-down' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chevron-down',
                ),
        ),
    'chevron-up' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chevron-up',
                ),
        ),
    'circle-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-circle-fill ft-state-danger',
                ),
        ),
    'circle-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-dash-circle-fill ft-state-disabled',
                ),
        ),
    'circle-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-circle',
                ),
        ),
    'circle-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-circle-fill ft-state-success',
                ),
        ),
    'clock' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-clock',
                ),
        ),
    'clock-locked' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-Clock-lock'
                ),
        ),
    'close' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-x'
                ),
        ),
    'code' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-code-slash',
                ),
        ),
    'cohort' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-people',
                ),
        ),
    'collapsed' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-right-fill ft-flip-rtl'
                ),
        ),
    'collapsed-empty' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-right-fill ft-flip-rtl ft-state-disabled'
                ),
        ),
    'columns' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-layout-three-columns',
                ),
        ),
    'column-hide' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-dash-square-fill'
                ),
        ),
    'column-show' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-plus-square'
                ),
        ),
    'comment' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chat',
                ),
        ),
    'comment-add' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chat'
                ),
        ),
    'commenting-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chat ft-state-info',
                ),
        ),
    'comments' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chat',
                ),
        ),
    'comments-search' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chat_text_search'
                ),
        ),
    'competency' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-trophy2',
                ),
        ),
    'competency-achieved' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-trophy_check'
                ),
        ),
    'completion-auto-enabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-completion-auto-enabled'
                ),
        ),
    'completion-auto-fail' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-x-circle ft-state-danger',
                ),
        ),
    'completion-auto-n' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-circle',
                ),
        ),
    'completion-auto-pass' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-circle-o ft-state-success',
                ),
        ),
    'completion-auto-y' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check-circle',
                ),
        ),
    'completion-manual-enabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-completion-manual-enabled'
                ),
        ),
    'completion-manual-n' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-square',
                ),
        ),
    'completion-manual-y' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check-square',
                ),
        ),
    'completion-rpl-n' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-square',
                ),
        ),
    'completion-rpl-y' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check-square',
                ),
        ),
    'compress' =>
        array (
            'data' =>
                array(
                    'classes' => 'tfont-var-arrows-angle-contract',
                ),
        ),
    'contact-add' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-journal_person_plus'
                ),
        ),
    'contact-remove' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-phonebook_minus'
                ),
        ),
    'core|notification-error' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-x-circle-fill',
                ),
        ),
    'core|notification-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-info_filled',
                ),
        ),
    'core|notification-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check-circle-fill',
                ),
        ),
    'core|notification-warning' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-exclamation-triangle-fill',
                ),
        ),
    'course' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-box',
                ),
        ),
    'course-completed' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-box_tick'
                ),
        ),
    'course-started' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-box_play'
                ),
        ),
    'database' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-database',
                ),
        ),
    'date-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-slash-circle',
                ),
        ),
    'date-enabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-clock',
                ),
        ),
    'date-frequency-once' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-calendar-check',
                ),
        ),
    'date-frequency-repeating' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-clockwise',
                ),
        ),
    'date-limited' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-box-arrow-in-right',
                ),
        ),
    'date-open' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-box-arrow-right',
                ),
        ),
    'date-relative' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-calendar',
                ),
        ),
    'deeper' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-right-fill ft-flip-rtl'
                ),
        ),
    /* General delete icon to be used for all delete actions */
    'delete' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-x ft-state-danger',
                ),
        ),
    // Non-standard / no state delete. For use with dark background colours.
    'delete-ns' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-x',
                ),
        ),
    'delete-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-x ft-state-disabled',
                ),
        ),
    'document-edit' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-pencil'
                ),
        ),
    'document-new' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file-earmark-plus'
                ),
        ),
    'document-properties' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file_earmark_gear'
                ),
        ),
    'dollar' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-dollar',
                ),
        ),
    'download' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-download',
                ),
        ),
    'duplicate' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-files_copy',
                ),
        ),
    'edit' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-pencil',
                ),
        ),
    'email' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-envelope',
                ),
        ),
    'email-filled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-envelope-fill',
                ),
        ),
    'email-no' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-email_slash'
                ),
        ),
    'emoticon-frown' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-emoji-frown',
                ),
        ),
    'emoticon-smile' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-emoji-smile',
                ),
        ),
    'enrolment-suspended' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-person_noentry'
                ),
        ),
    'error-circle' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-dash-circle-fill',
                ),
        ),
    'event-course' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-box_clock'
                ),
        ),
    'event-group' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-people_calendar1'
                ),
        ),
    'event-user' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-person_calendar'
                ),
        ),
    'exclamation-circle' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-exclamation-circle-fill',
                ),
        ),
    'expand' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrows-angle-expand',
                ),
        ),
    'expandable' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-down-fill'
                ),
        ),
    'expanded' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-down-fill'
                ),
        ),
    'explore' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-explore'
                ),
        ),
    'export' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-box-arrow-up',
                ),
        ),
    'external-link' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-box-arrow-up-right',
                ),
        ),
    'external-link-square' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-external-link-square',
                ),
        ),
    'file-archive' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file-earmark',
                ),
        ),
    'file-audio' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file_audio',
                ),
        ),
    'file-chart' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file_chart',
                ),
        ),
    'file-code' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file-earmark-code',
                ),
        ),
    'file-database' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-database',
                ),
        ),
    'file-ebook' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-journal-code',
                ),
        ),
    'file-general' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file-earmark',
                ),
        ),
    'file-image' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file_image',
                ),
        ),
    'file-pdf' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file_pdf',
                ),
        ),
    'file-powerpoint' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file_p',
                ),
        ),
    'file-sound' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file_audio',
                ),
        ),
    'file-spreadsheet' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file_x',
                ),
        ),
    'file-text' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file-earmark-text',
                ),
        ),
    'file-video' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file_camera',
                ),
        ),
    'file-word' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file_w',
                ),
        ),
    'filter' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-funnel',
                ),
        ),
    'flag-off' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-flag',
                ),
        ),
    'flag-on' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-flag-fill',
                ),
        ),
    'folder-create' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-folder-plus'
                ),
        ),
    'folder' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-folder',
                ),
        ),
    'folder-open' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-folder2-open',
                ),
        ),
    'forward-arrow' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-chevron-right ft-flip-rtl',
                ),
        ),
    'grades' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file-earmark-spreadsheet',
                ),
        ),
    'grid' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-grid-3x3-gap-fill',
                ),
        ),
    'groups-no' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-groups_no'
                ),
        ),
    'groups-separate' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-people_slash',
                ),
        ),
    'groups-visible' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-people',
                ),
        ),
    /* For links to Totara help */
    'help' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-info_filled',
                ),
        ),
    /* For action links that result in hiding of something */
    'hide' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-eye',
                ),
        ),
    'image' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-card-image',
                ),
        ),
    'indent' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-text-indent-left ft-flip-rtl',
                ),
        ),
    'info' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-info_filled ft-state-info',
                ),
        ),
    'info-circle' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-info_filled',
                ),
        ),
    'key' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-key',
                ),
        ),
    'key-no' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-key_slash'
                ),
        ),
    'laptop' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-laptop',
                ),
        ),
    'learningplan' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-briefcase',
                ),
        ),
    'level-up' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-90deg-up',
                ),
        ),
    'link' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-link-45deg',
                ),
        ),
    'loading' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-spinner tfont-pulse',
                ),
        ),
    'lock' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-lock',
                ),
        ),
    'log' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-card-text',
                ),
        ),
    'marker-on' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-light_bulb',
                ),
        ),
    'marker-off' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-light_bulb',
                ),
        ),
    'mean' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-mean1',
                ),
        ),
    'message' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chat-fill',
                ),
        ),
    'messages' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chats',
                ),
        ),
    'minus' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-dash',
                ),
        ),
    'minus-square' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-dash-square ',
                ),
        ),
    'minus-square-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-dash-square',
                ),
        ),
    'more' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-three-dots'
                ),
        ),
    'mouse-pointer' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-mouse_pointer_filled',
                ),
        ),
    'move-down' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-down'
                ),
        ),
    'move-up' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-up1'
                ),
        ),
    'nav-down' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chevron-down',
                ),
        ),
    'nav-expand' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chevron-down',
                ),
        ),
    'nav-expanded' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chevron-up',
                ),
        ),
    'navitem' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-stop-fill',
                ),
        ),
    'new' => // Something recently added.
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-star_10_points_filled',
                ),
        ),
    'news' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-newspaper',
                ),
        ),
    'notification' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-bell-fill',
                ),
        ),
    'objective' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-bullseye',
                ),
        ),
    'objective-achieved' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-bullseye_tick'
                ),
        ),
    'outcomes' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-pie-chart',
                ),
        ),
    'outdent' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-outdent',
                ),
        ),
    'parent-node' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-diagram-3-fill',
                ),
        ),
    'package' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-box-seam',
                ),
        ),
    'pencil' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-pencil',
                ),
        ),
    'pencil-square-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-pencil ft-state-info',
                ),
        ),
    'pencil-square-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-pencil-square',
                ),
        ),
    'permission-lock' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-person_lock'
                ),
        ),
    'permissions' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-person_key'
                ),
        ),
    'permissions-check' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-person_check'
                ),
        ),
    'plus' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-plus',
                ),
        ),
    'plus-circle-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-plus-circle-fill ft-state-info',
                ),
        ),
    'plus-square' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-plus-square',
                ),
        ),
    'plus-square-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-plus-square',
                ),
        ),
    'portfolio' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-file-person',
                ),
        ),
    'portfolio-add' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-briefcase_person_plus'
                ),
        ),
    'preferences' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-sliders',
                ),
        ),
    'preview' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-eye',
                ),
        ),
    'print' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-printer ',
                ),
        ),
    /* Totara program */
    'program' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-boxes',
                ),
        ),
    'publish' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-globe_caret'
                ),
        ),
    'question' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-question',
                ),
        ),
    'question-circle' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-question-circle',
                ),
        ),
    'question-circle-warning' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-question-circle-fill ft-state-warning',
                ),
        ),
    'ranges' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-stats-bars',
                ),
        ),
    'rating-star' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-star-half',
                ),
        ),
    'recordoflearning' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-archive',
                ),
        ),
    'recycle' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-repeat',
                ),
        ),
    'refresh' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-repeat',
                ),
        ),
    'remove' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-trash',
                ),
            ),
    'repeat' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-repeat',
                ),
        ),
    /* Forms element required to be filled */
    'required' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-asterisk ft-state-danger',
                ),
        ),
    'risk-allowxss' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-exclamation_code_slash'
                ),
        ),
    'risk-config' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-exclamation_gear'
                ),
        ),
    'risk-dataloss' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-risk-dataloss'
                ),
        ),
    'risk-managetrust' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-shield-exclamation'
                ),
        ),
    'risk-personal' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-exclamation_person'
                ),
        ),
    'risk-spam' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-exclamation_envelope'
                ),
        ),
    'risk-xss' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-risk-xss'
                ),
        ),
    'rows' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-list',
                ),
        ),
    'rss' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-rss',
                ),
        ),
    'save' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-floppy_disk',
                ),
        ),
    'scales' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-scales',
                ),
        ),
    'search' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-search',
                ),
        ),
    /* Settings or editing of stuff that changes how Totara works */
    'settings' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-gear',
                ),
        ),
    'settings-lock' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-gear_lock'
                ),
        ),
    'settings-menu' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-gear'
                ),
        ),
    'share-link' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-share-fill',
                ),
        ),
    /* Use for action icons that unhide something */
    'show' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-eye-slash',
                ),
        ),
    'sigma' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-sigma1',
                ),
        ),
    'sigma-plus' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-sigma_plus'
                ),
        ),
    'sign-out' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-box-arrow-right',
                ),
        ),
    'site-lock' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-site_lock'
                ),
        ),
    'slash' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-slash1',
                ),
        ),
    'slider' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-sliders',
                ),
        ),
    'sort' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chevron-expand',
                ),
        ),
    'sort-asc' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-up-fill',
                ),
        ),
    'sort-desc' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-caret-down-fill',
                ),
        ),
    'spacer' =>
        array(
            'template' => 'core/flex_icon_spacer',
        ),
    'square-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-square',
                ),
        ),
    'star' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-star-fill',
                ),
        ),
    'star-off' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-star',
                ),
        ),
    'statistics' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-statistics',
                ),
        ),
    'subcategory-no' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-List_X'
                ),
        ),
    'subitems' =>
       array(
           'data' =>
               array(
                   'classes' => 'tfont-var-chevron-right ft-flip-rtl',
               ),
       ),
    'table' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-table',
                ),
        ),
    'tags-searchable' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-check-square'
                ),
        ),
    'tags-unsearchable' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-plus-square',
                ),
        ),
    'tasks' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-progress_bars',
                ),
        ),
    'thumbs-down-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-hand-thumbs-down ft-state-danger',
                ),
        ),
    'thumbs-up-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-hand_thumbs_up_filled ft-state-success',
                ),
        ),
    'times-circle-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-x-circle-fill ft-state-danger',
                ),
        ),
    'times-circle-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-x-circle',
                ),
        ),
    'times-circle-o-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-x-circle ft-state-danger',
                ),
        ),
    'times-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-x ft-state-danger',
                ),
        ),
    'toggle-off' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-toggle-off',
                ),
        ),
    'toggle-on' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-toggle-on',
                ),
        ),
    'totara' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-totara_filled',
                ),
        ),
    'trash' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-trash',
                ),
        ),
    'tree-list-collapsed' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chevron-down',
                ),
        ),
    'tree-list-expanded' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-chevron-up',
                ),
        ),
    'undo' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-arrow-counterclockwise',
                ),
        ),
    'unlink' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-unlink',
                ),
        ),
    'unlock' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-unlock',
                ),
        ),
    'unlocked' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-unlock',
                ),
        ),
    'upload' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-upload',
                ),
        ),
    'user' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-person',
                ),
        ),
    'user-add' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-person-plus',
                ),
        ),
    'user-delete' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-person_x',
                ),
        ),
    'user-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-person ft-state-disabled',
                ),
        ),
    'user-refresh' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-person_arrow_clockwise'
                ),
        ),
    'user-secret' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-user-secret',
                ),
        ),
    'users' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-people',
                ),
        ),
    'view-grid' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-grid-fill',
                ),
        ),
    'view-large' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-grid-fill',
                ),
        ),
    'view-list' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-grid_list_filled',
                ),
        ),
    'view-tree' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-diagram_tree',
                ),
        ),
    'warning' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-exclamation-triangle-fill ft-state-warning',
                ),
        ),
    'warning-sign' =>
        array(
            'data' =>
                array(
                    'classes' => 'tfont-var-exclamation-triangle-fill',
                ),
        ),
    'tags' =>
        array (
            'data' =>
                array (
                    'classes' => 'tfont-var-tags'
                )
        ),
    'tag' =>
        array (
            'data' =>
                array (
                    'classes' => 'tfont-var-tag'
                )
        ),
    'reply' =>
        array (
            'data' =>
                array (
                    'classes' => 'tfont-var-reply-filled'
                )
        ),
    'notification-non-filled' =>
        array (
            'data' =>
                array (
                    'classes' => 'tfont-var-bell'
                )
        ),
    'notification-slash-non-filled' =>
        array (
            'data' =>
                array (
                    'classes' => 'tfont-var-bell_slash'
                )
        ),
    'circle' =>
        array (
            'data' =>
                array(
                    'classes' => 'fa-circle'
                )
        )
);

/*
 * Translation of old pix icon names to flex icon identifiers.
 *
 * The old core pix icon name format is "core|originalpixpath"
 * similar to the old pix placeholders in CSS.
 *
 * This information allows the pix_icon renderer
 * to automatically return the new flex icon markup.
 *
 * All referenced identifiers must be present in the $icons.
 *
 * Note that plugins are using the same identifier format
 * for $aliases, $deprecated and $icons "plugintype_pluginname|icon".
 */
$deprecated = array(
    'core|a/add_file' => 'document-new',
    'core|a/create_folder' => 'folder-create',
    'core|a/download_all' => 'download',
    'core|a/help' => 'help',
    'core|a/logout' => 'sign-out',
    'core|a/refresh' => 'refresh',
    'core|a/search' => 'search',
    'core|a/setting' => 'settings',
    'core|a/view_icon_active' => 'view-large',
    'core|a/view_list_active' => 'view-list',
    'core|a/view_tree_active' => 'view-tree',
    'core|b/bookmark-new' => 'bookmark',
    'core|b/document-edit' => 'document-edit',
    'core|b/document-new' => 'document-new',
    'core|b/document-properties' => 'document-properties',
    'core|b/edit-copy' => 'duplicate',
    'core|b/edit-delete' => 'delete',
    'core|c/event' => 'calendar',
    'core|docs' => 'info-circle',
    'core|f/archive' => 'file-archive',
    'core|f/audio' => 'file-audio',
    'core|f/avi' => 'file-video',
    'core|f/base' => 'file-database',
    'core|f/bmp' => 'file-image',
    'core|f/chart' => 'file-chart',
    'core|f/database' => 'file-database',
    'core|f/dmg' => 'file-archive',
    'core|f/document' => 'file-text',
    'core|f/edit' => 'pencil-square-o',
    'core|f/eps' => 'file-image',
    'core|f/epub' => 'file-ebook',
    'core|f/explore' => 'explore',
    'core|f/flash' => 'file-video',
    'core|f/folder' => 'folder',
    'core|f/folder-open' => 'folder-open',
    'core|f/gif' => 'file-image',
    'core|f/help-32' => 'help',
    'core|f/html' => 'file-code',
    'core|f/image' => 'file-image',
    'core|f/jpeg' => 'file-image',
    'core|f/markup' => 'file-code',
    'core|f/mov' => 'file-video',
    'core|f/move' => 'arrows',
    'core|f/mp3' => 'file-sound',
    'core|f/mpeg' => 'file-video',
    'core|f/parent-32' => 'level-up',
    'core|f/pdf' => 'file-pdf',
    'core|f/png' => 'file-image',
    'core|f/powerpoint' => 'file-powerpoint',
    'core|f/psd' => 'file-image',
    'core|f/quicktime' => 'file-video',
    'core|f/sourcecode' => 'file-code',
    'core|f/spreadsheet' => 'file-spreadsheet',
    'core|f/text' => 'file-text',
    'core|f/tiff' => 'file-image',
    'core|f/unknown' => 'file-general',
    'core|f/video' => 'file-video',
    'core|f/wav' => 'file-sound',
    'core|f/wmv' => 'file-video',
    'core|f/word' => 'file-word',
    'core|help' => 'help',
    'core|i/admin' => 'settings',
    'core|i/agg_mean' => 'mean',
    'core|i/agg_sum' => 'sigma',
    'core|i/ajaxloader' => 'loading',
    'core|i/assignroles' => 'user-add',
    'core|i/backup' => 'upload',
    'core|i/badge' => 'badge',
    'core|i/calc' => 'calculator',
    'core|i/calendar' => 'calendar',
    'core|i/caution' => 'exclamation-circle',
    'core|i/checkpermissions' => 'permissions-check',
    'core|i/closed' => 'folder',
    'core|i/cohort' => 'cohort',
    'core|i/completion-auto-enabled' => 'completion-auto-enabled',
    'core|i/completion-auto-fail' => 'completion-auto-fail',
    'core|i/completion-auto-n' => 'completion-auto-n',
    'core|i/completion-auto-pass' => 'completion-auto-pass',
    'core|i/completion-auto-y' => 'completion-auto-y',
    'core|i/completion-manual-enabled' => 'completion-manual-enabled',
    'core|i/completion-manual-n' => 'completion-manual-n',
    'core|i/completion-manual-y' => 'completion-manual-y',
    'core|i/configlock' => 'settings-lock',
    'core|i/course' => 'course',
    'core|i/courseevent' => 'event-course',
    'core|i/db' => 'database',
    'core|i/delete' => 'delete',
    'core|i/down' => 'arrow-down',
    'core|i/dragdrop' => 'arrows',
    'core|i/dropdown' => 'caret-down',
    'core|i/edit' => 'edit',
    'core|i/email' => 'email',
    'core|i/enrolmentsuspended' => 'enrolment-suspended',
    'core|i/enrolusers' => 'user-add',
    'core|i/export' => 'upload',
    'core|i/feedback' => 'comment',
    'core|i/feedback_add' => 'comment-add',
    'core|i/files' => 'file-general',
    'core|i/filter' => 'filter',
    'core|i/flagged' => 'flag-on',
    'core|i/folder' => 'folder',
    'core|i/grade_correct' => 'check-success',
    'core|i/grade_incorrect' => 'times-danger',
    'core|i/grade_partiallycorrect' => 'check-warning',
    'core|i/grades' => 'grades',
    'core|i/group' => 'users',
    'core|i/groupevent' => 'event-group',
    'core|i/groupn' => 'groups-no',
    'core|i/groups' => 'groups-separate',
    'core|i/groupv' => 'groups-visible',
    'core|i/guest' => 'user-secret',
    'core|i/hide' => 'hide',
    'core|i/hierarchylock' => 'site-lock',
    'core|i/import' => 'download',
    'core|i/info' => 'info-circle',
    'core|i/invalid' => 'times-danger',
    'core|i/item' => 'navitem',
    'core|i/key' => 'key',
    'core|i/loading' => 'loading',
    'core|i/loading_small' => 'loading',
    'core|i/lock' => 'lock',
    'core|i/log' => 'log',
    'core|i/manual_item' => 'edit',
    'core|i/marked' => 'marker-on',
    'core|i/marker' => 'marker-off',
    'core|i/mean' => 'mean',
    'core|i/menu' => 'bars',
    'core|i/moodle_host' => 'totara', // Intentional change of branding for repositories on other Totara servers.
    'core|i/move_2d' => 'arrows',
    'core|i/navigationitem' => 'navitem',
    'core|i/new' => 'new',
    'core|i/news' => 'news',
    'core|i/nosubcat' => 'subcategory-no',
    'core|i/open' => 'folder-open',
    'core|i/outcomes' => 'outcomes',
    'core|i/payment' => 'dollar',
    'core|i/permissionlock' => 'permission-lock',
    'core|i/permissions' => 'permissions',
    'core|i/portfolio' => 'portfolio',
    'core|i/preview' => 'preview',
    'core|i/publish' => 'publish',
    'core|i/questions' => 'question',
    'core|i/reload' => 'refresh',
    'core|i/report' => 'file-text',
    'core|i/repository' => 'database',
    'core|i/restore' => 'download',
    'core|i/return' => 'undo',
    'core|i/risk_allowxss' => 'risk-allowxss',
    'core|i/risk_config' => 'risk-config',
    'core|i/risk_dataloss' => 'risk-dataloss',
    'core|i/risk_managetrust' => 'risk-managetrust',
    'core|i/risk_personal' => 'risk-personal',
    'core|i/risk_spam' => 'risk-spam',
    'core|i/risk_xss' => 'risk-xss',
    'core|i/rss' => 'rss',
    'core|i/scales' => 'scales',
    'core|i/scheduled' => 'clock',
    'core|i/search' => 'search',
    'core|i/self' => 'user',
    'core|i/settings' => 'settings',
    'core|i/show' => 'show',
    'core|i/siteevent' => 'calendar',
    'core|i/star-rating' => 'rating-star',
    'core|i/stats' => 'statistics',
    'core|i/switch' => 'toggle-on',
    'core|i/switchrole' => 'user-refresh',
    'core|i/twoway' => 'arrows-h',
    'core|i/unflagged' => 'flag-off',
    'core|i/unlock' => 'unlock',
    'core|i/up' => 'arrow-up',
    'core|i/user' => 'user',
    'core|i/useradd' => 'user-add',
    'core|i/userdel' => 'user-delete',
    'core|i/userevent' => 'event-user',
    'core|i/users' => 'users',
    'core|i/valid' => 'check-success',
    'core|i/warning' => 'warning',
    'core|i/withsubcat' => 'view-tree',
    'core|m/USD' => 'dollar',
    'core|req' => 'required',
    'core|spacer' => 'spacer',
    'core|t/add' => 'plus',
    'core|t/addcontact' => 'contact-add',
    'core|t/adddir' => 'folder-create',
    'core|t/addfile' => 'document-new',
    'core|t/approve' => 'check',
    'core|t/assignroles' => 'user-add',
    'core|t/award' => 'badge',
    'core|t/backpack' => 'backpack',
    'core|t/backup' => 'upload',
    'core|t/block' => 'ban',
    'core|t/block_to_dock' => 'block-dock',
    'core|t/block_to_dock_rtl' => 'block-dock',
    'core|t/cache' => 'cache',
    'core|t/calc' => 'calculator',
    'core|t/calc_off' => 'calculator-off',
    'core|t/calendar' => 'calendar',
    'core|t/check' => 'check',
    'core|t/cohort' => 'cohort',
    'core|t/collapsed' => 'caret-right',
    'core|t/collapsed_empty' => 'caret-right-disabled',
    'core|t/collapsed_empty_rtl' => 'caret-left-disabled',
    'core|t/collapsed_rtl' => 'caret-left',
    'core|t/contextmenu' => 'bars',
    'core|t/copy' => 'duplicate',
    'core|t/delete' => 'delete',
    'core|t/delete_gray' => 'delete-disabled',
    'core|t/disable_down' => 'arrow-down',
    'core|t/disable_up' => 'arrow-up',
    'core|t/dock_to_block' => 'block-undock',
    'core|t/dock_to_block_rtl' => 'block-undock',
    'core|t/dockclose' => 'times-circle-o',
    'core|t/down' => 'arrow-down',
    'core|t/download' => 'download',
    'core|t/dropdown' => 'caret-down',
    'core|t/edit' => 'settings',
    'core|t/edit_gray' => 'edit',
    'core|t/edit_menu' => 'settings-menu',
    'core|t/editstring' => 'edit',
    'core|t/email' => 'email',
    'core|t/emailno' => 'email-no',
    'core|t/enroladd' => 'plus',
    'core|t/enrolusers' => 'user-add',
    'core|t/expanded' => 'caret-down',
    'core|t/feedback' => 'comment',
    'core|t/feedback_add' => 'comment-add',
    'core|t/go' => 'circle-success',
    'core|t/grades' => 'grades',
    'core|t/groupn' => 'groups-no',
    'core|t/groups' => 'groups-separate',
    'core|t/groupv' => 'groups-visible',
    'core|t/hide' => 'hide',
    'core|t/left' => 'arrow-left',
    'core|t/less' => 'minus',
    'core|t/lock' => 'lock',
    'core|t/locked' => 'lock',
    'core|t/locktime' => 'clock-locked',
    'core|t/log' => 'log',
    'core|t/markasread' => 'check',
    'core|t/mean' => 'mean',
    'core|t/message' => 'message',
    'core|t/messages' => 'messages',
    'core|t/more' => 'plus',
    'core|t/move' => 'arrows-v',
    'core|t/portfolioadd' => 'portfolio-add',
    'core|t/preferences' => 'preferences',
    'core|t/preview' => 'preview',
    'core|t/print' => 'print',
    'core|t/ranges' => 'ranges',
    'core|t/recycle' => 'recycle',
    'core|t/reload' => 'refresh',
    'core|t/removecontact' => 'contact-remove',
    'core|t/reset' => 'undo',
    'core|t/restore' => 'download',
    'core|t/right' => 'arrow-right',
    'core|t/scales' => 'scales',
    'core|t/show' => 'show',
    'core|t/sigma' => 'sigma',
    'core|t/sigmaplus' => 'sigma-plus',
    'core|t/sort' => 'sort',
    'core|t/sort_asc' => 'sort-asc',
    'core|t/sort_desc' => 'sort-desc',
    'core|t/stop' => 'circle-danger',
    'core|t/stop_gray' => 'circle-disabled',
    'core|t/switch' => 'plus-square',
    'core|t/switch_minus' => 'minus-square',
    'core|t/switch_plus' => 'plus-square',
    'core|t/switch_plus_rtl' => 'plus-square',
    'core|t/switch_whole' => 'external-link-square',
    'core|t/unblock' => 'check',
    'core|t/unlock' => 'unlock',
    'core|t/unlocked' => 'unlocked',
    'core|t/up' => 'arrow-up',
    'core|t/user' => 'user',
    'core|t/viewdetails' => 'preview',
    'core|y/lm' => 'caret-down',
    'core|y/loading' => 'loading',
    'core|y/lp' => 'caret-right',
    'core|y/lp_rtl' => 'caret-left',
    'core|y/tm' => 'caret-down',
    'core|y/tp' => 'caret-right',
    'core|y/tp_rtl' => 'caret-left',
);

/*
 * Pix only images are not supposed to be converted to flex icons.
 *
 * - e/xxx pix icons should be used by Atto editor that does not support flex icons
 *
 */
$pixonlyimages = array(
    'e/abbr',
    'e/absolute',
    'e/accessibility_checker',
    'e/acronym',
    'e/advance_hr',
    'e/align_center',
    'e/align_left',
    'e/align_right',
    'e/anchor',
    'e/backward',
    'e/bold',
    'e/bullet_list',
    'e/cell_props',
    'e/cite',
    'e/cleanup_messy_code',
    'e/clear_formatting',
    'e/copy',
    'e/cut',
    'e/decrease_indent',
    'e/delete',
    'e/delete_col',
    'e/delete_row',
    'e/delete_table',
    'e/document_properties',
    'e/emoticons',
    'e/find_replace',
    'e/forward',
    'e/fullpage',
    'e/fullscreen',
    'e/help',
    'e/increase_indent',
    'e/insert',
    'e/insert_col_after',
    'e/insert_col_before',
    'e/insert_date',
    'e/insert_edit_image',
    'e/insert_edit_link',
    'e/insert_edit_video',
    'e/insert_file',
    'e/insert_horizontal_ruler',
    'e/insert_nonbreaking_space',
    'e/insert_page_break',
    'e/insert_row_after',
    'e/insert_row_before',
    'e/insert_time',
    'e/italic',
    'e/justify',
    'e/layers',
    'e/layers_over',
    'e/layers_under',
    'e/left_to_right',
    'e/manage_files',
    'e/math',
    'e/merge_cells',
    'e/new_document',
    'e/numbered_list',
    'e/page_break',
    'e/paste',
    'e/paste_text',
    'e/paste_word',
    'e/prevent_autolink',
    'e/preview',
    'e/print',
    'e/question',
    'e/redo',
    'e/remove_link',
    'e/remove_page_break',
    'e/resize',
    'e/restore_draft',
    'e/restore_last_draft',
    'e/right_to_left',
    'e/row_props',
    'e/save',
    'e/screenreader_helper',
    'e/search',
    'e/select_all',
    'e/show_invisible_characters',
    'e/source_code',
    'e/special_character',
    'e/spellcheck',
    'e/split_cells',
    'e/strikethrough',
    'e/styleprops',
    'e/subscript',
    'e/superscript',
    'e/table',
    'e/table_props',
    'e/template',
    'e/text_color',
    'e/text_color_picker',
    'e/text_highlight',
    'e/text_highlight_picker',
    'e/tick',
    'e/toggle_blockquote',
    'e/underline',
    'e/undo',
    'e/visual_aid',
    'e/visual_blocks',
    /* Default user images */
    'g/f1',
    'g/f2',
    'i/mahara_host',
    'u/f1',
    'u/f2',
    'u/f3',
    'u/user35',
    'u/user100',
    // Course catalogue images.
    'course_defaultimage'
);
