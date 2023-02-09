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

namespace mod_approval\controllers\application;

use context;
use core\entity\user;
use core\format;
use core\notification;
use core\orm\query\builder;
use core\orm\query\order;
use core\webapi\formatter\field\string_field_formatter;
use core_date;
use DateTime;
use mod_approval\entity\application\application_action as application_action_entity;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\application;
use mod_approval\model\form\form_contents;
use totara_mvc\tui_view;

/**
 * The preview application.
 */
class preview extends base {
    /**
     * @inheritDoc
     */
    protected $layout = 'webview';

    /**
     * @inheritDoc
     */
    public function setup_context(): context {
        return $this->get_application_from_param()->get_context();
    }

    /**
     * @inheritDoc
     */
    public function process(string $action = '') {
        parent::process($action);
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $application_id = $this->get_application_id_param();
        $this->set_url(self::get_url_for($application_id));
        $application = $this->get_application_from_param();
        $interactor = $application->get_interactor(user::logged_in()->id);

        if (!$interactor->can_view()) {
            redirect(dashboard::get_url(), get_string('error:view_application', 'mod_approval'), null, notification::ERROR);
        }

        $form_contents = form_contents::generate_from_application($application, user::logged_in(), form_contents::PREVIEW);
        $approvers = $this->get_approvers($application);

        $props = [
            'form-schema' => $form_contents->get_form_schema(),
            'form-data' => $form_contents->get_form_data(),
            'approvers' => $approvers,
        ];

        // Let Behat be able to locate the print preview window.
        if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
            $this->get_page()->requires->js_init_code('window.name = "totara_approval_workflow_application_preview";');
        }
        $page_title = $this->get_title('preview', $application);

        return parent::create_tui_view('mod_approval/pages/ApplicationPreview', $props)
            ->set_title($page_title);
    }

    /**
     * Return the array of users that have approved the application so far.
     *
     * @param application $application
     * @return array of {level: String, fullname: String, timestamp: String}
     */
    private function get_approvers(application $application): array {
        return builder::table(application_action_entity::TABLE, 'aa')
            ->join([workflow_stage_approval_level_entity::TABLE, 'wal'], 'wal.id', 'aa.workflow_stage_approval_level_id')
            ->join([workflow_stage_entity::TABLE, 'ws'], 'ws.id', 'wal.workflow_stage_id')
            ->where('aa.application_id', $application->id)
            ->where('code', approve::get_code())
            ->where('superseded', 0)
            ->order_by_raw('ws.sortorder ASC')
            ->order_by_raw('wal.sortorder ASC')
            ->order_by('aa.created', order::DIRECTION_ASC)
            ->order_by('aa.id', order::DIRECTION_ASC)
            ->select(['aa.id', 'wal.name as level', 'aa.user_id', 'aa.created as timestamp'])
            ->map_to(function ($action) {
                $level = (new string_field_formatter(format::FORMAT_PLAIN, $this->context))
                    ->format($action->level);
                $fullname = (new user($action->user_id))->fullname;
                $timestamp = (new DateTime('@' . $action->timestamp))
                    ->setTimezone(core_date::get_server_timezone_object())
                    ->format(DateTime::ISO8601);
                return [
                    'level' => $level,
                    'fullname' => $fullname,
                    'timestamp' => $timestamp,
                ];
            })
            ->fetch(true);
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/approval/application/preview.php';
    }
}
