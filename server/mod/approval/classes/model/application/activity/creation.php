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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\application\activity;

use core\orm\query\exceptions\record_not_found_exception;
use html_writer;
use mod_approval\controllers\application\view;
use mod_approval\model\application\application;
use mod_approval\model\application\application_activity;

/**
 * Type 1: creation.
 */
final class creation extends activity {
    /**
     * @param application_activity $activity
     */
    protected function __construct(application_activity $activity) {
        $info = $activity->activity_info_parsed;
        $source = $info['source'] ?? 0;
        if ($source) {
            try {
                $application = application::load_by_id($source);
                $a = [
                    'source' => html_writer::link(view::get_url_for($application->id), s($application->title)),
                ];
                $this->by_user('model_application_activity_type_creation_desc_clone', $activity->user, $a);
            } catch (record_not_found_exception $ex) {
                $this->by_user('model_application_activity_type_creation_desc_clone_deleted', $activity->user);
            }
        } else {
            $this->by_user('model_application_activity_type_creation_desc_new', $activity->user);
        }
    }

    public static function get_type(): int {
        return 1;
    }

    protected static function get_label_key(): string {
        return 'model_application_activity_type_creation';
    }

    public static function is_valid_activity_info(array $info): bool {
        return empty($info) || (array_keys($info) == ['source'] && is_number($info['source']));
    }
}
