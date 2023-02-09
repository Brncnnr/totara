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

use coding_exception;
use core\entity\user;
use mod_approval\controllers\workflow_controller;
use moodle_exception;
use mod_approval\model\application\application as application_model;
use stdClass;
use Throwable;

/**
 * Base controller for application view
 */
abstract class base extends workflow_controller {
    /**
     * @var string Page layout defaults to legacynolayout
     */
    protected $layout = 'legacynolayout';

    /**
     * @inheritDoc
     */
    protected function authorize(): void {
        parent::authorize();
        // require_login sets the layout to incourse because our context is within a course
        $this->get_page()->set_pagelayout($this->layout);
    }

    /**
     * @return int
     */
    protected function get_application_id_param(): int {
        return $this->get_required_param('application_id', PARAM_INT);
    }

    /**
     * Loads application model from parameters
     *
     * @return application_model
     * @throws moodle_exception
     */
    protected function get_application_from_param(): application_model {
        try {
            return application_model::load_by_id($this->get_application_id_param());
        } catch (Throwable $exception) {
            throw new moodle_exception('invalid_application', 'mod_approval');
        }
    }

    /**
     * Get the page title from an application
     *
     * @param string $action edit, view or preview
     * @param application_model $application
     * @return string
     */
    protected function get_title(string $action, application_model $application): string {
        $a = new stdClass();
        $a->applicant = $application->user->fullname;
        $a->title = $application->title;

        if ($action == 'edit') {
            if (user::logged_in()->id == $application->user_id) {
                return get_string('application_own_edit_page_title', 'mod_approval', $a);
            } else {
                return get_string('application_edit_page_title', 'mod_approval', $a);
            }
        }

        if ($action == 'view' || $action == 'preview') {
            if (user::logged_in()->id == $application->user_id) {
                return get_string('application_own_view_page_title', 'mod_approval', $a);
            } else {
                return get_string('application_view_page_title', 'mod_approval', $a);
            }
        }
        throw new coding_exception("Something went wrong! Choose correct action");
    }

    /**
     * Return true if the controller is able to display debug stuff.
     *
     * @return boolean
     */
    protected static function is_debug(): bool {
        global $CFG;
        return !empty($CFG->debugdeveloper) && is_siteadmin();
    }

    /**
     * @inheritDoc
     */
    public function execute_graphql_operation(?string $operation_name = null, array $params = []): array {
        $result = parent::execute_graphql_operation($operation_name, $params);
        // Dump the result when fails without an exception e.g. referencing a non-existing field.
        if (array_keys($result) == ['errors']) {
            // Also log a debugging message to flunk a Behat scenario.
            if (defined('BEHAT_SITE_RUNNING')) {
                debugging('server side GQL failed: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (self::is_debug()) {
                echo self::json_to_html($result);
            }
        } else if (self::is_debug() && $this->get_optional_param('debug', 0, PARAM_INT)) {
            // Dump the result when `?debug=1` is set.
            echo self::json_to_html($result);
        }
        return $result;
    }

    /**
     * Loads the application data by resolving graphQL queries.
     *
     * @param int $application_id
     * @return array
     */
    protected function load_application_query(int $application_id): array {
        return $this->execute_graphql_operation(
            'mod_approval_load_application_slim',
            [
                'input' => [
                    'application_id' => $application_id,
                ],
            ]
        );
    }

    /**
     * Syntax highlighter
     *
     * @param mixed $json
     * @return string
     */
    final protected static function json_to_html($json): string {
        if (!is_string($json)) {
            $json = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        $pats = [
            // [regexp, colour]
            ['/(\"([^\"\\\\]|\\\\.)*\")\s*:/', '#960'],
            ['/(\'([^\'\\\\]|\\\\.)*\')\s*:/', '#960'],
            ['/(\"([^\"\\\\]|\\\\.)*\")/', '#080'],
            ['/(\'([^\'\\\\]|\\\\.)*\')/', '#080'],
            ['/\b(\d+|\d+\.\d*)\b/', '#900'],
            ['/\b(true|false|null)\b/', '#03c'],
        ];
        $out = '';
        $len = strlen($json);
        for ($i = 0; $i < $len;) {
            $first_matches = null;
            $colour = '';
            foreach ($pats as $pat) {
                if (preg_match($pat[0], $json, $matches, PREG_OFFSET_CAPTURE, $i) && (!$first_matches || $matches[0][1] < $first_matches[0][1])) {
                    $first_matches = $matches;
                    $colour = $pat[1] ?? '';
                }
            }
            if ($first_matches) {
                $offset = $first_matches[0][1];
                if ($i < $offset) {
                    $out .= htmlentities(substr($json, $i, $offset - $i), ENT_QUOTES);
                    $i = $offset;
                }
                $match = $first_matches[1][0];
                $code = htmlentities($match, ENT_QUOTES);
                if ($colour !== '') {
                    $code = "<span style=\"color:{$colour}\">{$code}</span>";
                }
                $out .= $code;
                $i += strlen($match);
            } else {
                $out .= htmlentities(substr($json, $i), ENT_QUOTES);
                break;
            }
        }
        return "<pre><code>{$out}</code></pre>";
    }

    /**
     * Get the page URL for the application.
     *
     * @param integer $application_id
     * @return string
     */
    public static function get_url_for(int $application_id): string {
        return self::get_url(['application_id' => $application_id])->out(false);
    }
}
