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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  Petr Skoda <petr.skoda@totaralms.com>
 * @author  Brian Barnes <brian.barnes@totaralms.com>
 * @package theme_roots
 */

/* Developer documentation is in /pix/flex_icons.php file. */

$icons = [
    /* Do not use 'flex-icon-missing' directly, it indicates requested icon was not found */
    'flex-icon-missing' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-question ft-stack-main',
                'fa-exclamation ft-stack-suffix'
            ],
        ],
    ],
    'activate' => [
        'data' => [
            'classes' => 'fa-arrow-circle-o-right',
        ],
    ],
    'alarm' => [
        'data' => [
            'classes' => 'ft-alarm',
        ],
    ],
    'alarm-danger' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'ft-alarm ft-stack-main',
                'fa-bolt ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'alarm-warning' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'ft-alarm ft-stack-main',
                'fa-warning ft-stack-suffix ft-state-warning',
            ],
        ],
    ],
    'archive' => [
        'data' => [
            'classes' => 'fa-archive',
        ],
    ],
    'arrow-down' => [
        'data' => [
            'classes' => 'fa-arrow-down',
        ],
    ],
    'arrow-left' => [
        'data' => [
            'classes' => 'fa-arrow-left',
        ],
    ],
    'arrow-right' => [
        'data' => [
            'classes' => 'fa-arrow-right',
        ],
    ],
    'arrow-up' => [
        'data' => [
            'classes' => 'fa-arrow-up',
        ],
    ],
    'arrows' => [
        'data' => [
            'classes' => 'fa-arrows',
        ],
    ],
    'arrows-alt' => [
        'data' => [
            'classes' => 'fa-arrows-alt',
        ],
    ],
    'arrows-h' => [
        'data' => [
            'classes' => 'fa-arrows-h',
        ],
    ],
    'arrows-v' => [
        'data' => [
            'classes' => 'fa-arrows-v',
        ],
    ],
    'attachment' => [
        'data' => [
            'classes' => 'fa-paperclip',
        ],
    ],
    'back-arrow' => [
        'data' => [
            'classes' => 'fa-chevron-left ft-flip-rtl'
        ]
    ],
    'backpack' => [
        'data' => [
            'classes' => 'ft-backpack',
        ],
    ],
    'badge' => [
        'data' => [
            'classes' => 'fa-trophy',
        ],
    ],
    'ban' => [
        'data' => [
            'classes' => 'fa-ban',
        ],
    ],
    'bars' => [
        'data' => [
            'classes' => 'fa-bars',
        ],
    ],
    'bar-chart' => [
        'data' => [
            'classes' => 'fa-bar-chart',
        ],
    ],
    'blended' => [
        'data' => [
            'classes' => 'ft-blended',
        ],
    ],
    'block-dock' => [
        'data' => [
            'classes' => 'fa-caret-square-o-left ft-flip-rtl',
        ],
    ],
    'block-hide' => [
        'data' => [
            'classes' => 'fa-minus-square',
        ],
    ],
    'block-show' => [
        'data' => [
            'classes' => 'fa-plus-square',
        ],
    ],
    'block-undock' => [
        'data' => [
            'classes' => 'fa-caret-square-o-right ft-flip-rtl',
        ],
    ],
    'bookmark' => [
        'data' => [
            'classes' => 'fa-bookmark-o',
        ],
    ],
    'books' => [
        'data' => [
            'classes' => 'ft-books',
        ],
    ],
    'cache' => [
        'data' => [
            'classes' => 'fa-bolt',
        ],
    ],
    'calculator' => [
        'data' => [
            'classes' => 'fa-calculator',
        ],
    ],
    'calculator-off' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-calculator ft-stack-main',
                'ft-slash ft-stack-over ft-state-danger',
            ],
        ],
    ],
    'calendar' => [
        'data' => [
            'classes' => 'fa-calendar',
        ],
    ],
    'caret-down' => [
        'data' => [
            'classes' => 'fa-caret-down',
        ],
    ],
    'caret-left' => [
        'data' => [
            'classes' => 'fa-caret-left',
        ],
    ],
    'caret-left-disabled' => [
        'data' => [
            'classes' => 'fa-caret-left ft-state-disabled',
        ],
    ],
    'caret-left-info' => [
        'data' => [
            'classes' => 'fa-caret-left ft-state-info',
        ],
    ],
    'caret-right' => [
        'data' => [
            'classes' => 'fa-caret-right',
        ],
    ],
    'caret-right-disabled' => [
        'data' => [
            'classes' => 'fa-caret-right ft-state-disabled',
        ],
    ],
    'caret-right-info' => [
        'data' => [
            'classes' => 'fa-caret-right ft-state-info',
        ],
    ],
    'caret-up' => [
        'data' => [
            'classes' => 'fa-caret-up',
        ],
    ],
    'certification' => [
        'data' => [
            'classes' => 'ft-certificate',
        ],
    ],
    'check' => [
        'data' => [
            'classes' => 'fa-check',
        ],
    ],
    'check-circle' => [
        'data' => [
            'classes' => 'fa-check-circle',
        ],
    ],
    'check-circle-o' => [
        'data' => [
            'classes' => 'fa-check-circle-o',
        ],
    ],
    'check-circle-o-success' => [
        'data' => [
            'classes' => 'fa-check-circle-o ft-state-success',
        ],
    ],
    'check-circle-success' => [
        'data' => [
            'classes' => 'fa-check-circle ft-state-success',
        ],
    ],
    'check-disabled' => [
        'data' => [
            'classes' => 'fa-check ft-state-disabled',
        ],
    ],
    'check-square-o' => [
        'data' => [
            'classes' => 'fa-check-square-o',
        ],
    ],
    'check-success' => [
        'data' => [
            'classes' => 'fa-check ft-state-success',
        ],
    ],
    'check-warning' => [
        'data' => [
            'classes' => 'fa-check ft-state-warning',
        ],
    ],
    'checklist' => [
        'data' => [
            'classes' => 'ft-checklist',
        ],
    ],
    'chevron-down' => [
        'data' => [
            'classes' => 'fa-chevron-down',
        ],
    ],
    'chevron-up' => [
        'data' => [
            'classes' => 'fa-chevron-up',
        ],
    ],
    'circle-danger' => [
        'data' => [
            'classes' => 'fa-circle ft-state-danger',
        ],
    ],
    'circle-disabled' => [
        'data' => [
            'classes' => 'fa-circle ft-state-disabled',
        ],
    ],
    'circle-o' => [
        'data' => [
            'classes' => 'fa-circle-o',
        ],
    ],
    'circle-success' => [
        'data' => [
            'classes' => 'fa-circle ft-state-success',
        ],
    ],
    'clock' => [
        'data' => [
            'classes' => 'fa-clock-o',
        ],
    ],
    'clock-locked' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-clock-o ft-stack-main',
                'fa-lock ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'close' => [
        'data' => [
            'classes' => 'fa-times'
        ],
    ],
    'code' => [
        'data' => [
            'classes' => 'fa-code',
        ],
    ],
    'cohort' => [
        'data' => [
            'classes' => 'fa-users',
        ],
    ],
    'collapsed' => [
        'data' => [
            'classes' => 'fa-caret-right ft-flip-rtl'
        ],
    ],
    'collapsed-empty' => [
        'data' => [
            'classes' => 'fa-caret-right ft-flip-rtl ft-state-disabled'
        ],
    ],
    'columns' => [
        'data' => [
            'classes' => 'ft-columns',
        ],
    ],
    'column-hide' => [
        'data' => [
            'classes' => 'fa-minus-square'
        ],
    ],
    'column-show' => [
        'data' => [
            'classes' => 'fa-plus-square'
        ],
    ],
    'comment' => [
        'data' => [
            'classes' => 'fa-comment-o',
        ],
    ],
    'comment-add' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-comment-o ft-stack-main',
                'fa-plus ft-stack-suffix',
            ],
        ],
    ],
    'commenting-info' => [
        'data' => [
            'classes' => 'fa-commenting ft-state-info',
        ],
    ],
    'comments' => [
        'data' => [
            'classes' => 'fa-comments-o',
        ],
    ],
    'comments-search' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-comments-o ft-stack-main',
                'fa-search ft-stack-suffix',
            ],
        ],
    ],
    'competency' => [
        'data' => [
            'classes' => 'fa-graduation-cap',
        ],
    ],
    'competency-achieved' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-graduation-cap ft-stack-main',
                'fa-check ft-stack-suffix ft-state-success',
            ],
        ],
    ],
    'completion-auto-enabled' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-check-circle-o ft-stack-main',
                'fa-play ft-stack-suffix',
            ],
        ],
    ],
    'completion-auto-fail' => [
        'data' => [
            'classes' => 'fa-times-circle-o ft-state-danger',
        ],
    ],
    'completion-auto-n' => [
        'data' => [
            'classes' => 'fa-circle-o',
        ],
    ],
    'completion-auto-pass' => [
        'data' => [
            'classes' => 'fa-check-circle-o ft-state-success',
        ],
    ],
    'completion-auto-y' => [
        'data' => [
            'classes' => 'fa-check-circle-o',
        ],
    ],
    'completion-manual-enabled' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-check-square-o ft-stack-main',
                'fa-play ft-stack-suffix',
            ],
        ],
    ],
    'completion-manual-n' => [
        'data' => [
            'classes' => 'fa-square-o',
        ],
    ],
    'completion-manual-y' => [
        'data' => [
            'classes' => 'fa-check-square-o',
        ],
    ],
    'completion-rpl-n' => [
        'data' => [
            'classes' => 'fa-square-o',
        ],
    ],
    'completion-rpl-y' => [
        'data' => [
            'classes' => 'fa-check-square-o',
        ],
    ],
    'compress' => [
        'data' => [
            'classes' => 'fa-compress',
        ],
    ],
    'contact-add' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'ft-address-book ft-stack-main',
                'fa-plus ft-stack-suffix ft-state-info',
            ],
        ],
    ],
    'contact-remove' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'ft-address-book ft-stack-main',
                'fa-minus ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'core|notification-error' => [
        'data' => [
            'classes' => 'fa-times-circle',
        ],
    ],
    'core|notification-info' => [
        'data' => [
            'classes' => 'fa-info-circle',
        ],
    ],
    'core|notification-success' => [
        'data' => [
            'classes' => 'fa-check-circle',
        ],
    ],
    'core|notification-warning' => [
        'data' => [
            'classes' => 'fa-warning',
        ],
    ],
    'course' => [
        'data' => [
            'classes' => 'fa-cube',
        ],
    ],
    'course-completed' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-cube ft-stack-main',
                'fa-check ft-stack-suffix ft-state-success',
            ],
        ],
    ],
    'course-started' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-cube ft-stack-main',
                'fa-play ft-stack-suffix',
            ],
        ],
    ],
    'database' => [
        'data' => [
            'classes' => 'fa-database',
        ],
    ],
    'date-disabled' => [
        'data' => [
            'classes' => 'fa-ban',
        ],
    ],
    'date-enabled' => [
        'data' => [
            'classes' => 'fa-clock-o',
        ],
    ],
    'date-frequency-once' => [
        'data' => [
            'classes' => 'fa-calendar-check-o',
        ],
    ],
    'date-frequency-repeating' => [
        'data' => [
            'classes' => 'fa-repeat',
        ],
    ],
    'date-limited' => [
        'data' => [
            'classes' => 'fa-sign-in',
        ],
    ],
    'date-open' => [
        'data' => [
            'classes' => 'fa-sign-out',
        ],
    ],
    'date-relative' => [
        'data' => [
            'classes' => 'fa-calendar-o',
        ],
    ],
    'deeper' => [
        'data' => [
            'classes' => 'fa-caret-right ft-flip-rtl'
        ],
    ],
    /* General delete icon to be used for all delete actions */
    'delete' => [
        'data' => [
            'classes' => 'fa-times ft-state-danger',
        ],
    ],
    // Non-standard / no state delete. For use with dark background colours.
    'delete-ns' => [
        'data' => [
            'classes' => 'fa-times',
        ],
    ],
    'delete-disabled' => [
        'data' => [
            'classes' => 'fa-times ft-state-disabled',
        ],
    ],
    'document-edit' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-file-o ft-stack-main',
                'fa-pencil ft-stack-suffix',
            ],
        ],
    ],
    'document-new' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-file-o ft-stack-main',
                'fa-plus ft-stack-suffix',
            ],
        ],
    ],
    'document-properties' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-file-o ft-stack-main',
                'fa-wrench ft-stack-suffix',
            ],
        ],
    ],
    'dollar' => [
        'data' => [
            'classes' => 'fa-dollar',
        ],
    ],
    'download' => [
        'data' => [
            'classes' => 'fa-download',
        ],
    ],
    'duplicate' => [
        'data' => [
            'classes' => 'fa-copy',
        ],
    ],
    'edit' => [
        'data' => [
            'classes' => 'fa-pencil',
        ],
    ],
    'email' => [
        'data' => [
            'classes' => 'fa-envelope-o',
        ],
    ],
    'email-filled' => [
        'data' => [
            'classes' => 'fa-envelope',
        ],
    ],
    'email-no' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-envelope-o ft-stack-main',
                'ft-slash ft-stack-over ft-state-danger',
            ],
        ],
    ],
    'emoticon-frown' => [
        'data' => [
            'classes' => 'fa-frown-o',
        ],
    ],
    'emoticon-smile' => [
        'data' => [
            'classes' => 'fa-smile-o',
        ],
    ],
    'enrolment-suspended' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-user ft-stack-main',
                'fa-pause ft-stack-suffix',
            ],
        ],
    ],
    'error-circle' => [
        'data' => [
            'classes' => 'fa-times-circle',
        ],
    ],
    'event-course' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-cube ft-stack-main',
                'fa-clock-o ft-stack-suffix',
            ],
        ],
    ],
    'event-group' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-users ft-stack-main',
                'fa-clock-o ft-stack-suffix ft-state-info',
            ],
        ],
    ],
    'event-user' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-user ft-stack-main',
                'fa-clock-o ft-stack-suffix ft-state-info',
            ],
        ],
    ],
    'exclamation-circle' => [
        'data' => [
            'classes' => 'fa-exclamation-circle',
        ],
    ],
    'expand' => [
        'data' => [
            'classes' => 'fa-expand',
        ],
    ],
    'expandable' => [
        'data' => [
            'classes' => 'fa-caret-down'
        ],
    ],
    'expanded' => [
        'data' => [
            'classes' => 'fa-caret-down'
        ],
    ],
    'explore' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-folder-o ft-stack-main',
                'fa-search ft-stack-suffix',
            ],
        ],
    ],
    'export' => [
        'data' => [
            'classes' => 'fa-share-square-o',
        ],
    ],
    'external-link' => [
        'data' => [
            'classes' => 'fa-external-link',
        ],
    ],
    'external-link-square' => [
        'data' => [
            'classes' => 'fa-external-link-square',
        ],
    ],
    'file-archive' => [
        'data' => [
            'classes' => 'fa-file-archive-o',
        ],
    ],
    'file-audio' => [
        'data' => [
            'classes' => 'fa-volume-up',
        ],
    ],
    'file-chart' => [
        'data' => [
            'classes' => 'fa-bar-chart',
        ],
    ],
    'file-code' => [
        'data' => [
            'classes' => 'fa-file-code-o',
        ],
    ],
    'file-database' => [
        'data' => [
            'classes' => 'fa-database',
        ],
    ],
    'file-ebook' => [
        'data' => [
            'classes' => 'ft-book',
        ],
    ],
    'file-general' => [
        'data' => [
            'classes' => 'fa-file-o',
        ],
    ],
    'file-image' => [
        'data' => [
            'classes' => 'fa-file-image-o',
        ],
    ],
    'file-pdf' => [
        'data' => [
            'classes' => 'fa-file-pdf-o',
        ],
    ],
    'file-powerpoint' => [
        'data' => [
            'classes' => 'fa-file-powerpoint-o',
        ],
    ],
    'file-sound' => [
        'data' => [
            'classes' => 'fa-file-sound-o',
        ],
    ],
    'file-spreadsheet' => [
        'data' => [
            'classes' => 'fa-file-excel-o',
        ],
    ],
    'file-text' => [
        'data' => [
            'classes' => 'fa-file-text-o',
        ],
    ],
    'file-video' => [
        'data' => [
            'classes' => 'fa-file-video-o',
        ],
    ],
    'file-word' => [
        'data' => [
            'classes' => 'fa-file-word-o',
        ],
    ],
    'filter' => [
        'data' => [
            'classes' => 'fa-filter',
        ],
    ],
    'flag-off' => [
        'data' => [
            'classes' => 'fa-flag-o',
        ],
    ],
    'flag-on' => [
        'data' => [
            'classes' => 'fa-flag',
        ],
    ],
    'folder-create' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-folder-o ft-stack-main',
                'fa-plus ft-stack-suffix',
            ],
        ],
    ],
    'folder' => [
        'data' => [
            'classes' => 'fa-folder-o',
        ],
    ],
    'folder-open' => [
        'data' => [
            'classes' => 'fa-folder-open-o',
        ],
    ],
    'forward-arrow' => [
        'data' => [
            'classes' => 'fa-chevron-right ft-flip-rtl',
        ],
    ],
    'grades' => [
        'data' => [
            'classes' => 'ft-grades',
        ],
    ],
    'grid' => [
        'data' => [
            'classes' => 'fa-th',
        ],
    ],
    'groups-no' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-users ft-stack-main',
                'ft-slash ft-stack-main ft-state-danger',
            ],
        ],
    ],
    'groups-separate' => [
        'data' => [
            'classes' => 'ft-group-separate',
        ],
    ],
    'groups-visible' => [
        'data' => [
            'classes' => 'fa-users',
        ],
    ],
    /* For links to Totara help */
    'help' => [
        'data' => [
            'classes' => 'fa-info-circle ft-state-info',
        ],
    ],
    /* For action links that result in hiding of something */
    'hide' => [
        'data' => [
            'classes' => 'fa-eye',
        ],
    ],
    'image' => [
        'data' => [
            'classes' => 'fa-image',
        ],
    ],
    'indent' => [
        'data' => [
            'classes' => 'fa-indent',
        ],
    ],
    'info' => [
        'data' => [
            'classes' => 'fa-info-circle ft-state-info',
        ],
    ],
    'info-circle' => [
        'data' => [
            'classes' => 'fa-info-circle',
        ],
    ],
    'key' => [
        'data' => [
            'classes' => 'fa-key',
        ],
    ],
    'key-no' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-key ft-stack-main',
                'ft-slash ft-stack-over ft-state-danger',
            ],
        ],
    ],
    'laptop' => [
        'data' => [
            'classes' => 'fa-laptop',
        ],
    ],
    'learningplan' => [
        'data' => [
            'classes' => 'fa-briefcase',
        ],
    ],
    'level-up' => [
        'data' => [
            'classes' => 'fa-level-up',
        ],
    ],
    'link' => [
        'data' => [
            'classes' => 'fa-link',
        ],
    ],
    'loading' => [
        'data' => [
            'classes' => 'fa-spinner fa-pulse',
        ],
    ],
    'lock' => [
        'data' => [
            'classes' => 'fa-lock',
        ],
    ],
    'log' => [
        'data' => [
            'classes' => 'ft-log',
        ],
    ],
    'marker-on' => [
        'data' => [
            'classes' => 'fa-lightbulb-o',
        ],
    ],
    'marker-off' => [
        'data' => [
            'classes' => 'fa-lightbulb-o ft-state-disabled',
        ],
    ],
    'mean' => [
        'data' => [
            'classes' => 'ft-mean',
        ],
    ],
    'message' => [
        'data' => [
            'classes' => 'fa-comment',
        ],
    ],
    'messages' => [
        'data' => [
            'classes' => 'fa-comments',
        ],
    ],
    'minus' => [
        'data' => [
            'classes' => 'fa-minus',
        ],
    ],
    'minus-square' => [
        'data' => [
            'classes' => 'fa-minus-square',
        ],
    ],
    'minus-square-o' => [
        'data' => [
            'classes' => 'fa-minus-square-o',
        ],
    ],
    'mnet-host' => [
        'data' => [
            'classes' => 'ft-mnethost',
        ],
    ],
    'more' => [
        'data' => [
            'classes' => 'fa-ellipsis-h'
        ],
    ],
    'mouse-pointer' => [
        'data' => [
            'classes' => 'fa-mouse-pointer',
        ],
    ],
    'move-down' => [
        'data' => [
            'classes' => 'fa-arrow-down'
        ],
    ],
    'move-up' => [
        'data' => [
            'classes' => 'fa-arrow-up'
        ],
    ],
    'nav-down' => [
        'data' => [
            'classes' => 'fa-chevron-down',
        ],
    ],
    'nav-expand' => [
        'data' => [
            'classes' => 'fa-chevron-right ft-flip-rtl',
        ],
    ],
    'nav-expanded' => [
        'data' => [
            'classes' => 'fa-chevron-down',
        ],
    ],
    'navitem' => [
        'data' => [
            'classes' => 'ft-square-small',
        ],
    ],
    'new' => [ // Something recently added.
        'data' => [
            'classes' => 'ft-new',
        ],
    ],
    'news' => [
        'data' => [
            'classes' => 'fa-newspaper-o',
        ],
    ],
    'notification' => [
        'data' => [
            'classes' => 'fa-bell',
        ],
    ],
    'objective' => [
        'data' => [
            'classes' => 'fa-bullseye',
        ],
    ],
    'objective-achieved' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-bullseye ft-stack-main',
                'fa-check ft-stack-suffix ft-state-success',
            ],
        ],
    ],
    'outcomes' => [
        'data' => [
            'classes' => 'fa-pie-chart',
        ],
    ],
    'outdent' => [
        'data' => [
            'classes' => 'fa-outdent',
        ],
    ],
    'parent-node' => [
        'data' => [
            'classes' => 'fa-sitemap',
        ],
    ],
    'package' => [
        'data' => [
            'classes' => 'ft-package',
        ],
    ],
    'pencil' => [
        'data' => [
            'classes' => 'fa-pencil',
        ],
    ],
    'pencil-square-info' => [
        'data' => [
            'classes' => 'fa-pencil-square ft-state-info',
        ],
    ],
    'pencil-square-o' => [
        'data' => [
            'classes' => 'fa-pencil-square-o',
        ],
    ],
    'permission-lock' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-user ft-stack-main',
                'fa-lock ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'permissions' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-user ft-stack-main',
                'fa-key ft-stack-suffix ft-state-info',
            ],
        ],
    ],
    'permissions-check' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-user ft-stack-main',
                'fa-warning ft-stack-suffix ft-state-warning',
            ],
        ],
    ],
    'plus' => [
        'data' => [
            'classes' => 'fa-plus',
        ],
    ],
    'plus-circle-info' => [
        'data' => [
            'classes' => 'fa-plus-circle ft-state-info',
        ],
    ],
    'plus-square' => [
        'data' => [
            'classes' => 'fa-plus-square',
        ],
    ],
    'plus-square-o' => [
        'data' => [
            'classes' => 'fa-plus-square-o',
        ],
    ],
    'portfolio' => [
        'data' => [
            'classes' => 'ft-profile',
        ],
    ],
    'portfolio-add' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'ft-profile ft-stack-main',
                'fa-plus ft-stack-suffix',
            ],
        ],
    ],
    'preferences' => [
        'data' => [
            'classes' => 'fa-sliders',
        ],
    ],
    'preview' => [
        'data' => [
            'classes' => 'fa-eye',
        ],
    ],
    'print' => [
        'data' => [
            'classes' => 'fa-print',
        ],
    ],
    /* Totara program */
    'program' => [
        'data' => [
            'classes' => 'fa-cubes',
        ],
    ],
    'publish' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-globe ft-stack-main',
                'fa-play ft-stack-suffix ft-state-info',
            ],
        ],
    ],
    'question' => [
        'data' => [
            'classes' => 'fa-question',
        ],
    ],
    'question-circle' => [
        'data' => [
            'classes' => 'fa-question-circle',
        ],
    ],
    'question-circle-warning' => [
        'data' => [
            'classes' => 'fa-question-circle ft-state-warning',
        ],
    ],
    'ranges' => [
        'data' => [
            'classes' => 'ft-stats-bars',
        ],
    ],
    'rating-star' => [
        'data' => [
            'classes' => 'fa-star-half-o',
        ],
    ],
    'recordoflearning' => [
        'data' => [
            'classes' => 'ft-archive',
        ],
    ],
    'recycle' => [
        'data' => [
            'classes' => 'fa-recycle',
        ],
    ],
    'refresh' => [
        'data' => [
            'classes' => 'fa-refresh',
        ],
    ],
    'remove' => [
        'data' => [
            'classes' => 'fa-trash-o',

        ],
    ],
    'repeat' => [
        'data' => [
            'classes' => 'fa-repeat',
        ],
    ],
    /* Forms element required to be filled */
    'required' => [
        'data' => [
            'classes' => 'fa-asterisk ft-state-danger',
        ],
    ],
    'risk-allowxss' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-code ft-stack-main',
                'fa-warning ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'risk-config' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-cogs ft-stack-main',
                'fa-warning ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'risk-dataloss' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-database ft-stack-main',
                'fa-warning ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'risk-managetrust' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-shield ft-stack-main',
                'fa-warning ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'risk-personal' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-user ft-stack-main',
                'fa-warning ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'risk-spam' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-envelope ft-stack-main',
                'fa-warning ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'risk-xss' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-code ft-stack-main',
                'fa-warning ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'rows' => [
        'data' => [
            'classes' => 'fa-bars',
        ],
    ],
    'rss' => [
        'data' => [
            'classes' => 'fa-rss',
        ],
    ],
    'save' => [
        'data' => [
            'classes' => 'fa-save',
        ],
    ],
    'scales' => [
        'data' => [
            'classes' => 'ft-stats-bars',
        ],
    ],
    'search' => [
        'data' => [
            'classes' => 'fa-search',
        ],
    ],
    /* Settings or editing of stuff that changes how Totara works */
    'settings' => [
        'data' => [
            'classes' => 'fa-cog',
        ],
    ],
    'settings-lock' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-cog ft-stack-main',
                'fa-lock ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'settings-menu' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-cog ft-stack-main',
                'fa-caret-down ft-stack-suffix',
            ],
        ],
    ],
    'share-link' => [
        'data' => [
            'classes' => 'fa-share-alt',
        ],
    ],
    /* Use for action icons that unhide something */
    'show' => [
        'data' => [
            'classes' => 'fa-eye-slash',
        ],
    ],
    'sigma' => [
        'data' => [
            'classes' => 'ft-sigma',
        ],
    ],
    'sigma-plus' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'ft-sigma ft-stack-main',
                'fa-plus ft-stack-suffix',
            ],
        ],
    ],
    'sign-out' => [
        'data' => [
            'classes' => 'fa-sign-out',
        ],
    ],
    'site-lock' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-sitemap ft-stack-main',
                'fa-lock ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'slash' => [
        'data' => [
            'classes' => 'ft-slash',
        ],
    ],
    'slider' => [
        'data' => [
            'classes' => 'fa-sliders',
        ],
    ],
    'sort' => [
        'data' => [
            'classes' => 'fa-sort',
        ],
    ],
    'sort-asc' => [
        'data' => [
            'classes' => 'fa-sort-asc',
        ],
    ],
    'sort-desc' => [
        'data' => [
            'classes' => 'fa-sort-desc',
        ],
    ],
    'spacer' => [
        'template' => 'core/flex_icon_spacer',
    ],
    'square-o' => [
        'data' => [
            'classes' => 'fa-square-o',
        ],
    ],
    'star' => [
        'data' => [
            'classes' => 'fa-star',
        ],
    ],
    'star-off' => [
        'data' => [
            'classes' => 'fa-star-o',
        ],
    ],
    'statistics' => [
        'data' => [
            'classes' => 'fa-line-chart',
        ],
    ],
    'subcategory-no' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'ft-view-tree ft-stack-main',
                'ft-slash ft-stack-over ft-state-danger',
            ],
        ],
    ],
    'subitems' => [
        'data' => [
            'classes' => 'fa-chevron-right ft-flip-rtl',
        ],
    ],
    'table' => [
        'data' => [
            'classes' => 'fa-table',
        ],
    ],
    'tags-searchable' => [
        'data' => [
            'classes' => 'fa-check-square-o'
        ],
    ],
    'tags-unsearchable' => [
        'data' => [
            'classes' => 'fa-square-o',
        ],
    ],
    'tasks' => [
        'data' => [
            'classes' => 'fa-tasks',
        ],
    ],
    'thumbs-down-danger' => [
        'data' => [
            'classes' => 'fa-thumbs-down ft-state-danger',
        ],
    ],
    'thumbs-up-success' => [
        'data' => [
            'classes' => 'fa-thumbs-up ft-state-success',
        ],
    ],
    'times-circle-danger' => [
        'data' => [
            'classes' => 'fa-times-circle ft-state-danger',
        ],
    ],
    'times-circle-o' => [
        'data' => [
            'classes' => 'fa-times-circle-o',
        ],
    ],
    'times-circle-o-danger' => [
        'data' => [
            'classes' => 'fa-times-circle-o ft-state-danger',
        ],
    ],
    'times-danger' => [
        'data' => [
            'classes' => 'fa-times ft-state-danger',
        ],
    ],
    'toggle-off' => [
        'data' => [
            'classes' => 'fa-toggle-off',
        ],
    ],
    'toggle-on' => [
        'data' => [
            'classes' => 'fa-toggle-on',
        ],
    ],
    'totara' => [
        'data' => [
            'classes' => 'ft-totara',
        ],
    ],
    'trash' => [
        'data' => [
            'classes' => 'fa-trash',
        ],
    ],
    'tree-list-collapsed' => [
        'data' => [
            'classes' => 'fa-angle-right',
        ],
    ],
    'tree-list-expanded' => [
        'data' => [
            'classes' => 'fa-angle-down',
        ],
    ],
    'undo' => [
        'data' => [
            'classes' => 'fa-undo',
        ],
    ],
    'unlink' => [
        'data' => [
            'classes' => 'fa-unlink',
        ],
    ],
    'unlock' => [
        'data' => [
            'classes' => 'fa-unlock',
        ],
    ],
    'unlocked' => [
        'data' => [
            'classes' => 'fa-unlock-alt',
        ],
    ],
    'upload' => [
        'data' => [
            'classes' => 'fa-upload',
        ],
    ],
    'user' => [
        'data' => [
            'classes' => 'fa-user',
        ],
    ],
    'user-add' => [
        'data' => [
            'classes' => 'fa-user-plus',
        ],
    ],
    'user-delete' => [
        'data' => [
            'classes' => 'fa-user-times',
        ],
    ],
    'user-disabled' => [
        'data' => [
            'classes' => 'fa-user ft-state-disabled',
        ],
    ],
    'user-refresh' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-user ft-stack-main',
                'fa-refresh ft-stack-suffix ft-state-info',
            ],
        ],
    ],
    'user-secret' => [
        'data' => [
            'classes' => 'fa-user-secret',
        ],
    ],
    'users' => [
        'data' => [
            'classes' => 'fa-users',
        ],
    ],
    'view-grid' => [
        'data' => [
            'classes' => 'fa-th-large',
        ],
    ],
    'view-large' => [
        'data' => [
            'classes' => 'fa-th-large',
        ],
    ],
    'view-list' => [
        'data' => [
            'classes' => 'fa-th-list',
        ],
    ],
    'view-tree' => [
        'data' => [
            'classes' => 'ft-view-tree',
        ],
    ],
    'warning' => [
        'data' => [
            'classes' => 'fa-warning ft-state-warning',
        ],
    ],
    'warning-sign' => [
        'data' => [
            'classes' => 'fa-warning',
        ],
    ],
    'tags' => [
        'data' => [
            'classes' => 'fa-tags'
        ]
    ],
    'tag' => [
        'data' => [
            'classes' => 'fa-tag'
        ]
    ],
    'reply' => [
        'data' => [
            'classes' => 'fa-reply'
        ]
    ],
    'notification-non-filled' => [
        'data' => [
            'classes' => 'fa-bell-o'
        ]
    ],
    'notification-slash-non-filled' => [
        'data' => [
            'classes' => 'fa-bell-slash-o'
        ]
    ],
    'circle' => [
        'data' => [
            'classes' => 'fa-circle'
        ]
    ],

    // tool_oauth2 icons
    'tool_oauth2|yes' => [
        'data' => [
            'classes' => 'fa-check ft-state-success',
        ],
    ],
    'tool_oauth2|no' => [
        'data' => [
            'classes' => 'fa-times ft-state-danger',
        ],
    ],
    'tool_oauth2|auth' => [
        'data' => [
            'classes' => 'fa-sign-out',
        ],
    ],
    'tool_oauth2|endpoints' => [
        'data' => [
            'classes' => 'fa-th-list',
        ],
    ],

    // tool_recyclebin icons
    'tool_recyclebin|trash' => [
        'data' => [
            'classes' => 'fa-trash',
        ],
    ],

    // tool_sitepolicy icons
    'tool_sitepolicy|archive' => [
        'data' => [
            'classes' => 'fa-archive',
        ],
    ],

    // tool_usertours icons
    'tool_usertours|t/export' => [
        'data' => [
            'classes' => 'fa-download',
        ],
    ],
    'tool_usertours|i/reload' => [
        'data' => [
            'classes' => 'fa-refresh',
        ],
    ],

    // auth_approved icons
    'auth_approved|approve' => [
        'data' => [
            'classes' => 'fa-thumbs-up',
        ],
    ],
    'auth_approved|reject' => [
        'data' => [
            'classes' => 'fa-times',
        ],
    ],

    // auth_connect icons
    'auth_connect|icon' => [
        'data' => [
            'classes' => 'fa-plug',
        ],
    ],

    // enrol_paypal icons
    'enrol_paypal|icon' => [
        'data' => [
            'classes' => 'fa-paypal',
        ],
    ],

    // atto_collapse icons
    'atto_collapse|icon' => [
        'data' => [
            'classes' => 'fa-level-down',
        ],
    ],

    // assignfeedback_editpdf icons
    'assignfeedback_editpdf|stamp' => [
        'data' => [
            'classes' => 'ft-stamp',
        ],
    ],
    'assignfeedback_editpdf|highlight' => [
        'data' => [
            'classes' => 'ft-highlight',
        ],
    ],

    // mod_assign icons
    'mod_assign|icon' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-file-text-o ft-stack-main',
                'fa-thumb-tack ft-stack-suffix ft-state-info',
            ],
        ],
    ],

    // mod_book icons
    'mod_book|icon' => [
        'data' => [
            'classes' => 'fa-book',
        ],
    ],
    'mod_book|chapter' => [
        'data' => [
            'classes' => 'ft-book-open',
        ],
    ],
    'mod_book|nav_exit' => [
        'data' => [
            'classes' => 'fa-caret-up',
        ],
    ],
    'mod_book|nav_next' => [
        'data' => [
            'classes' => 'fa-caret-right ft-flip-rtl',
        ],
    ],
    'mod_book|nav_prev' => [
        'data' => [
            'classes' => 'fa-caret-left ft-flip-rtl',
        ],
    ],
    'mod_book|nav_prev_dis' => [
        'data' => [
            'classes' => 'fa-caret-left ft-state-disabled ft-flip-rtl',
        ],
    ],

    // booktool_exportimscp icons
    'booktool_exportimscp|generate' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'ft-package ft-stack-main',
                'fa-arrow-right ft-stack-suffix',
            ],
        ],
    ],

    // booktool_print icons
    'booktool_print|book' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-book ft-stack-main',
                'fa-print ft-stack-suffix',
            ],
        ],
    ],
    'booktool_print|chapter' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'ft-book-open ft-stack-main',
                'fa-print ft-stack-suffix',
            ],
        ],
    ],

    // mod_certificate icons
    'mod_certificate|icon' => [
        'data' => [
            'classes' => 'ft-certificate',
        ],
    ],

    // mod_chat icons
    'mod_chat|icon' => [
        'data' => [
            'classes' => 'fa-comments',
        ],
    ],

    // mod_choice icons
    'mod_choice|icon' => [
        'data' => [
            'classes' => 'fa-question-circle',
        ],
    ],

    // mod_data icons
    'mod_data|field/latlong' => [
        'data' => [
            'classes' => 'fa-globe',
        ],
    ],
    'mod_data|field/radiobutton' => [
        'data' => [
            'classes' => 'fa-dot-circle-o',
        ],
    ],
    'mod_data|field/menu' => [
        'data' => [
            'classes' => 'fa-bars',
        ],
    ],
    'mod_data|field/multimenu' => [
        'data' => [
            'classes' => 'fa-bars',
        ],
    ],
    'mod_data|field/number' => [
        'data' => [
            'classes' => 'fa-hashtag',
        ],
    ],
    'mod_data|field/textarea' => [
        'data' => [
            'classes' => 'fa-font',
        ],
    ],
    'mod_data|field/text' => [
        'data' => [
            'classes' => 'fa-i-cursor',
        ],
    ],

    // mod_facetoface icons
    'mod_facetoface|icon' => [
        'data' => [
            'classes' => 'ft-seminar',
        ],
    ],
    'mod_facetoface|filters' => [
        'data' => [
            'classes' => 'fa-sliders',
        ],
    ],
    'mod_facetoface|moreactions' => [
        'data' => [
            'classes' => 'fa-ellipsis-h',
        ],
    ],

    // mod_feedback icons
    'mod_feedback|icon' => [
        'data' => [
            'classes' => 'fa-bullhorn',
        ],
    ],
    'mod_feedback|notrequired' => [
        'data' => [
            'classes' => 'fa-question-circle-o',
        ],
    ],

    // mod_folder icons
    'mod_folder|icon' => [
        'data' => [
            'classes' => 'fa-folder-o',
        ],
    ],

    // mod_forum icons
    'mod_forum|icon' => [
        'data' => [
            'classes' => 'fa-comments-o',
        ],
    ],
    'mod_forum|t/subscribed' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-envelope-o ft-stack-main',
                'fa-check ft-stack-suffix ft-state-success',
            ],
        ],
    ],
    'mod_forum|t/unsubscribed' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-envelope-o ft-stack-main',
                'fa-times ft-stack-suffix ft-state-danger',
            ],
        ],
    ],
    'mod_forum|t/selected' => [
        'data' => [
            'classes' => 'fa-check',
        ],
    ],
    'mod_forum|i/pinned' => [
        'data' => [
            'classes' => 'fa-map-pin',
        ],
    ],

    // mod_glossary icons
    'mod_glossary|icon' => [
        'data' => [
            'classes' => 'ft-address-book',
        ],
    ],

    // mod_label icons
    'mod_label|icon' => [
        'data' => [
            'classes' => 'fa-tag',
        ],
    ],

    // mod_lesson icons
    'mod_lesson|icon' => [
        'data' => [
            'classes' => 'fa-list-alt',
        ],
    ],
    'mod_lesson|e/copy' => [
        'data' => [
            'classes' => 'fa-clone',
        ],
    ],

    // mod_lti icons
    'mod_lti|icon' => [
        'data' => [
            'classes' => 'fa-puzzle-piece',
        ],
    ],

    // mod_scorm icons
    'mod_scorm|icon' => [
        'data' => [
            'classes' => 'ft-archive',
        ],
    ],
    'mod_scorm|suspend' => [
        'data' => [
            'classes' => 'fa-moon-o',
        ],
    ],
    'mod_scorm|assetc' => [
        'data' => [
            'classes' => 'fa-file-archive-o',
        ],
    ],
    'mod_scorm|asset' => [
        'data' => [
            'classes' => 'fa-file-archive-o',
        ],
    ],
    'mod_scorm|browsed' => [
        'data' => [
            'classes' => 'fa-book',
        ],
    ],
    'mod_scorm|completed' => [
        'data' => [
            'classes' => 'fa-check-square-o',
        ],
    ],
    'mod_scorm|failed' => [
        'data' => [
            'classes' => 'fa-times',
        ],
    ],
    'mod_scorm|incomplete' => [
        'data' => [
            'classes' => 'fa-pencil-square-o',
        ],
    ],
    'mod_scorm|popdown' => [
        'data' => [
            'classes' => 'fa-window-close-o',
        ],
    ],
    'mod_scorm|popup' => [
        'data' => [
            'classes' => 'fa-window-restore',
        ],
    ],

    // mod_survey icons
    'mod_survey|icon' => [
        'data' => [
            'classes' => 'ft-stats-bars',
        ],
    ],

    // mod_url icons
    'mod_url|icon' => [
        'data' => [
            'classes' => 'fa-globe',
        ],
    ],

    // mod_wiki icons
    'mod_wiki|icon' => [
        'data' => [
            'classes' => 'fa-wikipedia-w',
        ],
    ],

    // mod_workshop icons
    'mod_workshop|icon' => [
        'data' => [
            'classes' => 'ft-seminar',
        ],
    ],

    // qtype icons
    'qtype_ddmarker|crosshairs' => [
        'data' => [
            'classes' => 'fa-crosshairs',
        ],
    ],
    'qtype_ddmarker|grid' => [
        'data' => [
            'classes' => 'fa-th',
        ],
    ],

    // repository_areafiles icons
    'repository_areafiles|icon' => [
        'template' => 'core/flex_icon_stack',
        'data' => [
            'classes' => [
                'fa-file-text-o ft-stack-main',
                'fa-paperclip ft-stack-suffix',
            ],
        ],
    ],

    // repository_dropbox icons
    'repository_dropbox|icon' => [
        'data' => [
            'classes' => 'fa-dropbox',
        ],
    ],

    // repository_flickr icons
    'repository_flickr|icon' => [
        'data' => [
            'classes' => 'fa-flickr',
        ],
    ],

    // repository_flickr_public icons
    'repository_flickr_public|icon' => [
        'data' => [
            'classes' => 'fa-flickr',
        ],
    ],

    // repository_googledocs icons
    'repository_googledocs|icon' => [
        'data' => [
            'classes' => 'ft-google-drive',
        ],
    ],

    // repository_skydrive
    'repository_skydrive|icon' => [
        'data' => [
            'classes' => 'fa-skyatlas',
        ],
    ],

    // repository_wikimedia icons
    'repository_wikimedia|icon' => [
        'data' => [
            'classes' => 'fa-wikipedia-w',
        ],
    ],

    // repository_youtube icons
    'repository_youtube|icon' => [
        'data' => [
            'classes' => 'fa-youtube-play',
        ],
    ],

    // contentmarketplace_goone icons
    'contentmarketplace_goone|online' => [
        'data' => [
            'classes' => 'fa-laptop',
        ],
    ],

    // totara_contentmarketplace icons
    'totara_contentmarketplace|icon' => [
        'data' => [
            'classes' => 'ft-totara',
        ],
    ],

    // totara_core icons
    'totara_core|accordion-expanded' => [
        'data' => [
            'classes' => 'fa-chevron-down',
        ],
    ],
    'totara_core|accordion-collapsed' => [
        'data' => [
            'classes' => 'fa-chevron-right ft-flip-rtl',
        ],
    ],
    'totara_core|home' => [
        'data' => [
            'classes' => 'fa-home',
        ],
    ],
    'totara_core|archive_file' => [
        'data' => [
            'classes' => 'fa-file-archive-o'
        ]
    ],
    'totara_core|pdf_file' => [
        'data' => [
            'classes' => 'fa-file-pdf-o'
        ]
    ],
    'totara_core|image_file' => [
        'data' => [
            'classes' => 'fa-file-image-o'
        ]
    ],
    'totara_core|word_file' => [
        'data' => [
            'classes' => 'fa-file-word-o'
        ]
    ],
    'totara_core|excel_file' => [
        'data' => [
            'classes' => 'fa-file-excel-o'
        ]
    ],
    'totara_core|file' => [
        'data' => [
            'classes' => 'fa-file-o'
        ]
    ],
    'totara_core|video_file' => [
        'data' => [
            'classes' => 'fa-file-video-o'
        ]
    ],
    'totara_core|powerpoint_file' => [
        'data' => [
            'classes' => 'fa-file-powerpoint-o'
        ]
    ],
    'totara_core|audio_file' => [
        'data' => [
            'classes' => 'fa-file-audio-o',
        ]
    ],

    'totara_core|bookmark-active' => [
        'data' => [
            'classes' => 'fa-bookmark'
        ]
    ],

    'totara_core|like' => [
        'data' => [
            'classes' => 'fa-thumbs-o-up'
        ]
    ],

    'totara_core|like-active' => [
        'data' => [
            'classes' => 'fa-thumbs-up'
        ]
    ],

    // totara_program icons
    'totara_program|progress_or' => [
        'data' => [
            'classes' => 'fa-ellipsis-h',
        ],
    ],
    'totara_program|too_many_assignments' => [
        'data' => [
            'classes' => 'fa-warning ft-state-info'
        ],
    ],

    // totara_reportbuilder icons
    'totara_reportbuilder|report_icon' => [
        'data' => [
            'classes' => 'fa-pie-chart',
        ],
    ],

    // totara_userdata icons
    'totara_userdata|icon' => [
        'data' => [
            'classes' => [
                'fa-database'
            ],
        ],
    ],
];

$aliases = [
    // assignfeedback_editpdf icons
    'assignfeedback_editpdf|comment' => 'comment',
    'assignfeedback_editpdf|comment_search' => 'comments-search',
    'assignfeedback_editpdf|cross' => 'times-danger',
    'assignfeedback_editpdf|line' => 'slash',
    'assignfeedback_editpdf|nav_next' => 'caret-right',
    'assignfeedback_editpdf|nav_prev' => 'caret-left',
    'assignfeedback_editpdf|oval' => 'circle-o',
    'assignfeedback_editpdf|pen' => 'pencil',
    'assignfeedback_editpdf|rectangle' => 'square-o',
    'assignfeedback_editpdf|sad' => 'emoticon-frown',
    'assignfeedback_editpdf|select' => 'mouse-pointer',
    'assignfeedback_editpdf|smile' => 'emoticon-smile',
    'assignfeedback_editpdf|tick' => 'check-success',
    'assignfeedback_editpdf|trash' => 'trash',

    // mod_assign icons
    'mod_assign|gradefeedback' => 'edit',

    // mod_choice icons
    'mod_choice|column' => 'columns',
    'mod_choice|row' => 'rows',

    // mod_data icons
    'mod_data|field/checkbox' => 'check-square-o',
    'mod_data|field/date' => 'calendar',
    'mod_data|field/file' => 'file-general',
    'mod_data|field/picture' => 'image',
    'mod_data|field/url' => 'link',
    'mod_data|icon' => 'database',

    // mod_feedback icons
    'mod_feeback|required' => 'required',
    'mod_feeback|notrequired' => 'required',

    // mod_glossary icons
    'mod_glossary|asc' => 'sort-asc',
    'mod_glossary|comment' => 'comment',
    'mod_glossary|desc' => 'sort-desc',
    'mod_glossary|export' => 'export',
    'mod_glossary|minus' => 'minus',
    'mod_glossary|print' => 'print',

    // mod_lti icons
    'mod_lti|warning' => 'warning',

    // mod_quiz icons
    'mod_quiz|icon' => 'checklist',

    // mod_scorm icons
    'mod_scorm|minus' => 'minus-square-o',
    'mod_scorm|notattempted' => 'square-o',
    'mod_scorm|passed' => 'check-square-o',
    'mod_scorm|plus' => 'plus-square-o',
    'mod_scorm|wait' => 'loading',

    // mod_wiki icons
    'mod_wiki|attachment' => 'attachment',

    // mod_workshop icons
    'mod_workshop|userplan/task-done' => 'check-success',
    'mod_workshop|userplan/task-fail' => 'times-danger',
    'mod_workshop|userplan/task-info' => 'info-circle',
    'mod_workshop|userplan/task-todo' => 'check-disabled',

    // totara_core icons
    'totara_core|bookings' => 'calendar',
    'totara_core|comment-point-blue' => 'caret-left-info',
    'totara_core|comment-point-blue-rtl' => 'caret-right-info',
    'totara_core|comment-point-grey' => 'caret-right-disabled',
    'totara_core|comment-point-grey-rtl' => 'caret-left-disabled',
    'totara_core|i/bullet_delete' => 'times-danger',
    'totara_core|i/completion-rpl-n' => 'completion-rpl-n',
    'totara_core|i/completion-rpl-y' => 'completion-rpl-y',
    'totara_core|i/delete_grey' => 'delete-disabled',
    'totara_core|jquery_treeview/folder' => 'folder-open',
    'totara_core|jquery_treeview/folder-closed' => 'folder',
    'totara_core|loading' => 'loading',
    'totara_core|loading_small' => 'loading',
    'totara_core|msgicons/blended-add' => 'plus-circle-info',
    'totara_core|msgicons/blended-approve' => 'thumbs-up-success',
    'totara_core|msgicons/blended-complete' => 'check-circle-success',
    'totara_core|msgicons/blended-deadline' => 'alarm',
    'totara_core|msgicons/blended-decline' => 'thumbs-down-danger',
    'totara_core|msgicons/blended-due' => 'alarm-warning',
    'totara_core|msgicons/blended-fail' => 'times-circle-danger',
    'totara_core|msgicons/blended-newcomment' => 'commenting-info',
    'totara_core|msgicons/blended-overdue' => 'alarm-danger',
    'totara_core|msgicons/blended-regular' => 'blended',
    'totara_core|msgicons/blended-remove' => 'times-danger',
    'totara_core|msgicons/blended-request' => 'question-circle-warning',
    'totara_core|msgicons/blended-update' => 'pencil-square-info',
    'totara_core|msgicons/competency-add' => 'plus-circle-info',
    'totara_core|msgicons/competency-approve' => 'thumbs-up-success',
    'totara_core|msgicons/competency-complete' => 'check-circle-success',
    'totara_core|msgicons/competency-deadline' => 'alarm',
    'totara_core|msgicons/competency-decline' => 'thumbs-down-danger',
    'totara_core|msgicons/competency-due' => 'alarm-warning',
    'totara_core|msgicons/competency-fail' => 'times-circle-danger',
    'totara_core|msgicons/competency-newcomment' => 'commenting-info',
    'totara_core|msgicons/competency-overdue' => 'alarm-danger',
    'totara_core|msgicons/competency-regular' => 'competency',
    'totara_core|msgicons/competency-remove' => 'times-danger',
    'totara_core|msgicons/competency-request' => 'question-circle-warning',
    'totara_core|msgicons/competency-update' => 'pencil-square-info',
    'totara_core|msgicons/course-add' => 'plus-circle-info',
    'totara_core|msgicons/course-approve' => 'thumbs-up-success',
    'totara_core|msgicons/course-complete' => 'check-circle-success',
    'totara_core|msgicons/course-deadline' => 'alarm',
    'totara_core|msgicons/course-decline' => 'thumbs-down-danger',
    'totara_core|msgicons/course-due' => 'alarm-warning',
    'totara_core|msgicons/course-fail' => 'times-circle-danger',
    'totara_core|msgicons/course-newcomment' => 'commenting-info',
    'totara_core|msgicons/course-overdue' => 'alarm-danger',
    'totara_core|msgicons/course-regular' => 'course',
    'totara_core|msgicons/course-remove' => 'times-danger',
    'totara_core|msgicons/course-request' => 'question-circle-warning',
    'totara_core|msgicons/course-update' => 'pencil-square-info',
    'totara_core|msgicons/default' => 'laptop',
    'totara_core|msgicons/elearning-add' => 'plus-circle-info',
    'totara_core|msgicons/elearning-approve' => 'thumbs-up-success',
    'totara_core|msgicons/elearning-complete' => 'check-circle-success',
    'totara_core|msgicons/elearning-deadline' => 'alarm',
    'totara_core|msgicons/elearning-decline' => 'thumbs-down-danger',
    'totara_core|msgicons/elearning-due' => 'alarm-warning',
    'totara_core|msgicons/elearning-fail' => 'times-circle-danger',
    'totara_core|msgicons/elearning-newcomment' => 'commenting-info',
    'totara_core|msgicons/elearning-overdue' => 'alarm-danger',
    'totara_core|msgicons/elearning-regular' => 'laptop',
    'totara_core|msgicons/elearning-remove' => 'times-danger',
    'totara_core|msgicons/elearning-request' => 'question-circle-warning',
    'totara_core|msgicons/elearning-update' => 'pencil-square-info',
    'totara_core|msgicons/evidence-add' => 'plus-circle-info',
    'totara_core|msgicons/evidence-approve' => 'thumbs-up-success',
    'totara_core|msgicons/evidence-complete' => 'check-circle-success',
    'totara_core|msgicons/evidence-deadline' => 'alarm',
    'totara_core|msgicons/evidence-decline' => 'thumbs-down-danger',
    'totara_core|msgicons/evidence-due' => 'alarm-warning',
    'totara_core|msgicons/evidence-fail' => 'times-circle-danger',
    'totara_core|msgicons/evidence-newcomment' => 'commenting-info',
    'totara_core|msgicons/evidence-overdue' => 'alarm-danger',
    'totara_core|msgicons/evidence-regular' => 'attachment',
    'totara_core|msgicons/evidence-remove' => 'times-danger',
    'totara_core|msgicons/evidence-request' => 'question-circle-warning',
    'totara_core|msgicons/evidence-update' => 'pencil-square-info',
    'totara_core|msgicons/facetoface-add' => 'plus-circle-info',
    'totara_core|msgicons/facetoface-approve' => 'thumbs-up-success',
    'totara_core|msgicons/facetoface-complete' => 'check-circle-success',
    'totara_core|msgicons/facetoface-deadline' => 'alarm',
    'totara_core|msgicons/facetoface-decline' => 'thumbs-down-danger',
    'totara_core|msgicons/facetoface-due' => 'alarm-warning',
    'totara_core|msgicons/facetoface-fail' => 'times-circle-danger',
    'totara_core|msgicons/facetoface-newcomment' => 'commenting-info',
    'totara_core|msgicons/facetoface-overdue' => 'alarm-danger',
    'totara_core|msgicons/facetoface-regular' => 'mod_facetoface|icon',
    'totara_core|msgicons/facetoface-remove' => 'times-danger',
    'totara_core|msgicons/facetoface-request' => 'question-circle-warning',
    'totara_core|msgicons/facetoface-update' => 'pencil-square-info',
    'totara_core|msgicons/feedback360-cancel' => 'times-danger',
    'totara_core|msgicons/feedback360-remind' => 'alarm-warning',
    'totara_core|msgicons/feedback360-request' => 'question-circle-warning',
    'totara_core|msgicons/feedback360-update' => 'pencil-square-info',
    'totara_core|msgicons/learningplan-add' => 'plus-circle-info',
    'totara_core|msgicons/learningplan-approve' => 'thumbs-up-success',
    'totara_core|msgicons/learningplan-complete' => 'check-circle-success',
    'totara_core|msgicons/learningplan-deadline' => 'alarm',
    'totara_core|msgicons/learningplan-decline' => 'thumbs-down-danger',
    'totara_core|msgicons/learningplan-due' => 'alarm-warning',
    'totara_core|msgicons/learningplan-fail' => 'times-circle-danger',
    'totara_core|msgicons/learningplan-newcomment' => 'commenting-info',
    'totara_core|msgicons/learningplan-overdue' => 'alarm-danger',
    'totara_core|msgicons/learningplan-regular' => 'learningplan',
    'totara_core|msgicons/learningplan-remove' => 'times-danger',
    'totara_core|msgicons/learningplan-request' => 'question-circle-warning',
    'totara_core|msgicons/learningplan-update' => 'pencil-square-info',
    'totara_core|msgicons/objective-add' => 'plus-circle-info',
    'totara_core|msgicons/objective-approve' => 'thumbs-up-success',
    'totara_core|msgicons/objective-complete' => 'check-circle-success',
    'totara_core|msgicons/objective-deadline' => 'alarm',
    'totara_core|msgicons/objective-decline' => 'thumbs-down-danger',
    'totara_core|msgicons/objective-due' => 'alarm-warning',
    'totara_core|msgicons/objective-fail' => 'times-circle-danger',
    'totara_core|msgicons/objective-newcomment' => 'commenting-info',
    'totara_core|msgicons/objective-overdue' => 'alarm-danger',
    'totara_core|msgicons/objective-regular' => 'objective',
    'totara_core|msgicons/objective-remove' => 'times-danger',
    'totara_core|msgicons/objective-request' => 'question-circle-warning',
    'totara_core|msgicons/objective-update' => 'pencil-square-info',
    'totara_core|msgicons/program-add' => 'plus-circle-info',
    'totara_core|msgicons/program-approve' => 'thumbs-up-success',
    'totara_core|msgicons/program-complete' => 'check-circle-success',
    'totara_core|msgicons/program-deadline' => 'alarm',
    'totara_core|msgicons/program-decline' => 'thumbs-down-danger',
    'totara_core|msgicons/program-due' => 'alarm-warning',
    'totara_core|msgicons/program-fail' => 'times-circle-danger',
    'totara_core|msgicons/program-newcomment' => 'commenting-info',
    'totara_core|msgicons/program-overdue' => 'alarm-danger',
    'totara_core|msgicons/program-regular' => 'program',
    'totara_core|msgicons/program-remove' => 'times-danger',
    'totara_core|msgicons/program-request' => 'question-circle-warning',
    'totara_core|msgicons/program-update' => 'pencil-square-info',
    'totara_core|msgicons/resource-add' => 'plus-circle-info',
    'totara_core|msgicons/resource-approve' => 'thumbs-up-success',
    'totara_core|msgicons/resource-complete' => 'check-circle-success',
    'totara_core|msgicons/resource-deadline' => 'alarm',
    'totara_core|msgicons/resource-decline' => 'thumbs-down-danger',
    'totara_core|msgicons/resource-due' => 'alarm-warning',
    'totara_core|msgicons/resource-fail' => 'times-circle-danger',
    'totara_core|msgicons/resource-newcomment' => 'commenting-info',
    'totara_core|msgicons/resource-overdue' => 'alarm-danger',
    'totara_core|msgicons/resource-regular' => 'books',
    'totara_core|msgicons/resource-remove' => 'times-danger',
    'totara_core|msgicons/resource-request' => 'question-circle-warning',
    'totara_core|msgicons/resource-update' => 'pencil-square-info',
    'totara_core|plan' => 'learningplan',
    'totara_core|record' => 'recordoflearning',
    'totara_core|t/calendar' => 'calendar',
    'totara_core|t/delete_grey' => 'delete-disabled',
    'totara_core|t/file' => 'file-text',
    'totara_core|t/minus' => 'minus-square-o',
    'totara_core|t/plus' => 'plus-square-o',
    'totara_core|teammembers' => 'users',

    // totara_program icons
    'totara_program|program_warning' => 'exclamation-circle',
    'totara_program|progress_then' => 'arrow-down',
    'totara_program|no_assignments' => 'exclamation-circle',

    // totara_reportbuilder icons 
    'totara_reportbuilder|wait' => 'loading',
    'totara_reportbuilder|waitbig' => 'loading',
];

/* Pix only images are not supposed to be converted to flex icons. */
$pixonlyimages = [
    'screenshot',
    // TODO: add sprite and fp/ stuff if it is not converted to flex icons
];
