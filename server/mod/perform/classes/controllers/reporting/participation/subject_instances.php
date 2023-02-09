<?php
/*
 * This file is part of Totara Perform
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\reporting\participation;

use context;
use context_system;
use core\output\notification;
use mod_perform\controllers\perform_controller;
use mod_perform\models\activity\activity;
use moodle_exception;
use moodle_url;
use totara_mvc\has_report;
use totara_mvc\view;

class subject_instances extends perform_controller {

    use has_report;

    /**
     * mod_perform\models\activity\activity instance
     * @var activity $activity
     */
    private $activity = null;

    public function setup_context(): context {
        if ($this->get_optional_param('activity_id', null, PARAM_INT)) {
            return $this->get_activity()->get_context();
        } else {
            return context_system::instance();
        }
    }

    public function action() {
        if ($this->get_optional_param('activity_id', null, PARAM_INT)) {
            $report = $this->load_embedded_report('perform_participation_subject_instance', ['activity_id' => $this->get_activity()->id]);

            $debug = $this->get_optional_param('debug', 0, PARAM_INT);

            $this->set_url(static::get_url(['activity_id' => $this->get_activity()->id]));

            return self::create_report_view($report, $debug)
                ->set_title(get_string('participation_reporting_with_activity', 'mod_perform', format_string($this->get_activity()->name)))
                ->set_back_to(
                    new moodle_url('/mod/perform/manage/activity/index.php'),
                    get_string('back_to_all_activities', 'mod_perform')
                );
        } else {
            $url = new moodle_url('/mod/perform/manage/activity/index.php');
            $this->set_url(static::get_url());
            return self::create_view('mod_perform/no_report', [
                'content' => view::core_renderer()->notification(
                    get_string('report_activity_warning_message', 'mod_perform', (object)['url' => $url->out(true)]),
                    notification::NOTIFY_WARNING
                )
            ]);
        }
    }

    public static function get_base_url(): string {
        return '/mod/perform/reporting/participation/index.php';
    }

    private function get_activity(): activity {
        if (!isset($this->activity)) {
            try {
                $activity_id = $this->get_required_param('activity_id', PARAM_INT);
                $this->activity = activity::load_by_id($activity_id);
            } catch (\Exception $e) {
                throw new moodle_exception('error_activity_id_wrong', 'mod_perform', '', null, $e);
            }
        }
        return $this->activity;
    }
}
