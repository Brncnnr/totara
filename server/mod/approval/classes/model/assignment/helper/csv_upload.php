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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\assignment\helper;

use moodle_url;
use totara_table;
use core\entity\user;
use core\orm\query\builder;
use core\collection;
use core\orm\collection as orm_collection;
use core\notification;
use hierarchy_organisation\entity\organisation;

use mod_approval\csv_import_helper;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;

use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\assignment\approver_type\relationship;
use mod_approval\model\assignment\approver_type\user as user_approver_type;

use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;

/**
 * Class assignment overrides csv_upload
 */
class csv_upload extends csv_import_helper {

    public $cmd = false;

    public const SRCTYPE = 'assignment_overrides';

    public const REMOVE_CODE = '%remove';

    /**
     * Generate properties and construct the instance
     * @param int $workflow_id
     * @param int|null $process_id
     * @param moodle_url|null $base_url
     * @return static
     */
    public static function instance(int $workflow_id, ?int $process_id = null, ?moodle_url $base_url = null): self {
        $process_id = $process_id ?: static::get_new_iid(static::SRCTYPE);
        if ($base_url instanceof moodle_url) {
            $base_url->param('process_id', $process_id);
        }
        return new static($process_id, $base_url, static::SRCTYPE, $workflow_id);
    }

    /**
     * Upload the content from csv file
     *
     * @param \stdClass $formdata
     */
    public function upload_csv_content(\stdClass $formdata): void {
        $delimiter = csv_import_helper::detect_delimiter($formdata);
        $datacount = $this->load_csv_content($formdata->content, $formdata->encoding, $delimiter);
        if (!$datacount) {
            $this->console("\n".$this->get_error()."\n", notification::ERROR);
            return;
        }
        $headers = $this->get_columns();
        if (!$headers) {
            $this->console("\ncan not parse csv file\n", notification::ERROR);
            return;
        }

        $fieldnames = [0 => 'shortname', 1 => 'manager'];
        $approval_levels = static::get_approval_levels($this->get_action_id());
        foreach ($approval_levels as $approval_level) {
            $fieldnames[] = self::get_stage_x_level_y($approval_level->workflow_stage->ordinal_number, $approval_level->ordinal_number);
        }
        $missing_headers = array_diff($fieldnames, $headers);
        if ($missing_headers) {
            $missing_headers = implode(', ', $missing_headers);
            $this->console("\nmissing $missing_headers column/s, all columns must be included in each row, the values for columns may be blank\n", notification::ERROR);
            return;
        }
        $extra_headers = array_diff($headers, $fieldnames);
        if ($extra_headers) {
            $extra_headers = htmlentities(implode(', ', $extra_headers));
            $this->console("\nunexpected column/s: $extra_headers\n", notification::ERROR);
            return;
        }

        $organisations = builder::table(organisation::TABLE)
            ->select(['shortname', 'id', 'fullname', 'idnumber'])
            ->fetch();

        $iter = 0;
        $total = 0;
        $errors = 0;
        $raw_data = [];
        $raw_data['headers'] = $fieldnames;

        $this->init();
        while ($attempt = $this->next()) {
            $iter++;
            $total++;
            $data = array_combine($fieldnames, $attempt);
            if (!$data) {
                $this->console("\n$iter: can not read data for these assignments \n");
                $errors++;
                continue;
            }
            if (empty($data['shortname'])) {
                $this->console("\n$iter: organisation shortname is required, skipping it ... \n");
                $errors++;
                continue;
            }
            $this->console("\nsearching record for '{$data['shortname']}' organisation shortname:\n");
            if (!isset($organisations[$data['shortname']])) {
                $this->console("$iter: found no record for '{$data['shortname']}' organisation shortname, skipping it ... \n", notification::ERROR);
                $errors++;
                continue;
            }
            $this->console("$iter: successful\n");

            $raw_data['raw_data'][$organisations[$data['shortname']]->id] = $data;
        }
        $raw_data['count'] = [
            'total' => $total,
            'errors' => $errors
        ];
        $this->close();
        $this->set_all_user_data($raw_data);
    }

    /**
     * Process the content and save to db of csv file
     *
     * @return int[]
     */
    public function process_data(): array {
        $workflow_id = $this->get_action_id();
        $workflow = workflow::load_by_id($workflow_id);
        $workflow_version = $workflow->get_latest_version();
        $stages = $workflow_version->get_stages();
        $workflow_manager_role = self::get_role('approvalworkflowmanager');
        $organisations = builder::table(organisation::TABLE)
            ->select(['shortname', 'id', 'fullname', 'idnumber', 'path'])
            ->fetch();

        $this->console("\nprocessing CSV content ...");

        $errors = 0;
        $successful = 0;
        $raw_data = $this->get_all_user_data();
        $assignments = [];
        $from_data = [];
        foreach ($raw_data['raw_data'] as $data) {
            $org = $organisations[$data['shortname']];
            $this->console("\nAssignment override for '{$data['shortname']}' organisation ...");
            $assignment_entity = assignment_entity::repository()
                ->where('course', '=', $workflow->course_id)
                ->where('assignment_type', '=', assignment_type\organisation::get_code())
                ->where('assignment_identifier', '=', $org->id)
                ->one();
            if (is_null($assignment_entity)) {
                $this->console("\n  - creating a new record for '{$org->fullname}' organisation ...");
                $assignment = assignment::create(
                    $workflow->course_id,
                    assignment_type\organisation::get_code(),
                    $org->id,
                    false,
                    $org->idnumber ?: $data['shortname']
                );
                $this->console(" created - ");
            } else {
                $assignment = assignment::load_by_entity($assignment_entity);
                $this->console(" loaded - ");
            }
            $assignments[$org->path] = $assignment;
            $from_data[$org->path] = $data;
            $this->console($org->path );
        }

        // Put the assignments in reverse path order to make activating them more efficient.
        // Lower approvers will already been in place when inheritance is calculated for higher approvers.
        krsort($assignments, SORT_NATURAL);

        $this->console("\n================");
        foreach ($assignments as $path => $assignment) {
            $this->console("\n\n{$path} working on {$assignment->name}:");
            $assignment_context = $assignment->get_context();

            $data = $from_data[$path];

            /**
             * Assign 'approvalworkflowmanager' role to a/the manager/s
             */
            $managers = $this->process_users($data['manager'] ?: '', $errors);
            if (!is_null($managers)) {
                $new_managers = [];
                foreach ($managers as $manager) {
                    $new_managers[$manager->id] = $manager;
                }
                $role_users = get_role_users($workflow_manager_role->id, $assignment_context, true, 'u.id', 'u.id ASC');
                $unassign_users = array_diff_key($role_users, $new_managers);
                $assign_users = array_diff_key($new_managers, $role_users);
                foreach ($unassign_users as $user) {
                    $this->console("\n  - unassign '{$user->id}' userid from the 'approvalworkflowmanager' role ...");
                    role_unassign($workflow_manager_role->id, $user->id, $assignment_context->id, 'mod_approval', $assignment->id);
                }
                foreach ($assign_users as $user) {
                    $this->console("\n  - assign '{$user->id}' userid to the 'approvalworkflowmanager' role with {$assignment_context->id} context id ...");
                    role_assign($workflow_manager_role->id, $user->id, $assignment_context, 'mod_approval', $assignment->id);
                }
            }

            /**
             * process Stage X Level X
             */
            $i = 0;
            /** @var workflow_stage $workflow_stage */
            foreach ($stages as $workflow_stage) {
                $i++;
                $this->console("\n  - processing Stage {$i} ...");
                $approval_levels = $workflow_stage->get_approval_levels();
                $y = 0;
                /** @var workflow_stage_approval_level $approval_level */
                foreach ($approval_levels as $approval_level) {
                    $y++;
                    $field = self::get_stage_x_level_y($i, $y);
                    $this->console("\n===\nProcessing Stage {$i} Level {$y} ...");
                    $users = $this->process_users($data[$field] ?: '', $errors);
                    if (is_null($users)) {
                        $this->console(" -> no users in data.");
                        continue;
                    }
                    $this->console(" -> " . count($users). " approver users in data.");
                    $userids = array_flip(
                        $users->pluck('id')
                    );

                    // Get all existing approvers, even inherited ones.
                    $approvers_here = builder::table(assignment_approver_entity::TABLE, 'approver')
                        ->where('approval_id', $assignment->id)
                        ->where('workflow_stage_approval_level_id', $approval_level->id) // should be $assignment_approval_level->id ???
                        ->where('active', '=', true)
                        ->get()
                        ->map_to(assignment_approver_entity::class)
                        ->map_to(assignment_approver::class);
                    $this->console("\n  - found " . count($approvers_here) . " approvers here already");

                    /** @var assignment_approver $approver */
                    foreach ($approvers_here as $approver) {
                        if ($approver->type == relationship::TYPE_IDENTIFIER) {
                            // Relationship approver, deactivate.
                            $approver->deactivate(true);
                            $this->console("\n  - deactivated relationship approver");
                            continue;
                        }
                        // If inherited user-type approver, ignore.
                        if (!empty($approver->ancestor_id)) {
                            continue;
                        }
                        if (array_key_exists($approver->identifier, $userids)) {
                            // Existing user approver, activate.
                            $approver->activate(true);
                            $this->console("\n  - (re)activated existing approver {$users[$approver->identifier]->username}");
                            unset($users[$approver->identifier]);
                            continue;
                        }
                        // Unknown approver, deactivate.
                        if ($approver->active) {
                            $this->console("\n  - deactivated existing approver {$approver->get_name()}");
                            $approver->deactivate(true);
                        }
                    }
                    // Now we're down to new approver users.
                    foreach ($users as $user) {
                        $this->console("\n  - processing '{$user->username}' user id {$user->id} as a new approver ...");
                        $approver = assignment_approver::create(
                            $assignment,
                            $approval_level,
                            user_approver_type::TYPE_IDENTIFIER,
                            $user->id
                        );
                        $approver->activate(true);
                        $this->console("\n - approver {$approver->id} activated, no descendants check.");
                    }
                }
            }
            $this->console("\nActivating assignment {$assignment->name}.");
            $assignment->activate();
            $successful++;
        }
        $this->clean();
        return [$successful, $errors];
    }

    /**
     * Display the content of csv file
     *
     * @param moodle_url $base_url
     * @return string
     */
    public function render_data(moodle_url $base_url): string {
        $organisations = builder::table(organisation::TABLE)
            ->select(['shortname', 'id', 'fullname', 'idnumber'])
            ->fetch();

        $raw_data = $this->get_all_user_data();
        $headers = array_replace($raw_data['headers'], [0 => 'Organisation', 1 => 'Manager/s']);

        ob_start();
        $table = new totara_table('assignment_override_upload_confirm');
        $table->define_baseurl($base_url);
        $table->define_headers($headers);
        $table->define_columns($headers);
        $table->setup();

        foreach ($raw_data['raw_data'] as $row) {
            $data = [];

            if (isset($organisations[$row['shortname']])) {
                $data[] = $organisations[$row['shortname']]->fullname;
            } else {
                $data[] = '';
            }

            // render manager users
            $data[] = self::render_users($row['manager'] ?: '');

            // render stage_X_level_Y users
            $approval_levels = static::get_approval_levels($this->get_action_id());
            foreach ($approval_levels as $approval_level) {
                $field = self::get_stage_x_level_y($approval_level->workflow_stage->ordinal_number, $approval_level->ordinal_number);
                $data[] = self::render_users($row[$field] ?: '');
            }
            $table->add_data($data);
        }
        $table->finish_html();

        $tablecontent = ob_get_contents();
        ob_end_clean();

        return $tablecontent;
    }

    /**
     * Get a collection of workflow stage approval levels by workflow id
     *
     * @param int $workflow_id
     * @return collection
     */
    public static function get_approval_levels(int $workflow_id): collection {
        static $approval_levels = null;
        if ($approval_levels instanceof collection) {
            return $approval_levels;
        }
        $workflow = workflow::load_by_id($workflow_id);
        $workflow_version = $workflow->get_latest_version();
        $stages_id = $workflow_version->get_stages()->keys();
        $approval_levels = workflow_stage_approval_level_entity::repository()
            ->select('*')
            ->where_in('workflow_stage_id', array_values($stages_id))
            ->order_by('id')
            ->get()
            ->map_to(workflow_stage_approval_level::class);
        return $approval_levels;
    }

    /**
     * Return "stage_X_level_Y" string
     *
     * @param int $x stage
     * @param int $y level
     * @return string
     */
    public static function get_stage_x_level_y(int $x, int $y): string {
        return "stage_{$x}_level_{$y}";
    }

    /**
     * @param string $csv_users
     * @param int $errors
     * @return orm_collection|null
     */
    private function process_users(string $csv_users, int &$errors): ?orm_collection {
        if (empty($csv_users)) {
            return null;
        } else if ($csv_users == csv_upload::REMOVE_CODE) {
            return new orm_collection();
        }
        $usernames = explode(',', $csv_users); // there may be more than one
        $users = user::repository()
            ->where_in('username', $usernames)
            ->get();
        if ($users->count() == 0) {
            $this->console("\n  - no users with '{$csv_users}' usernames exists ...", notification::ERROR);
            $errors += count($usernames);
            return new orm_collection();
        }
        if ($users->count() != count($usernames)) {
            // Looks like some of username is invalid
            $userids = $users->pluck('username');
            $missing_users = array_diff($usernames, $userids);
            $this->console("\n  - no users with '".implode(',', $missing_users)."' usernames exists ...", notification::ERROR);
            $errors += count($missing_users);
        }
        if ($users->count() == 0) {
            return null;
        }
        return $users;
    }

    /**
     * Show users on UI overrides confirm page
     *
     * @param string $csv_users, raw comma separated usernames from csv file
     * @return string
     */
    private static function render_users(string $csv_users = ''): string {
        if (empty($csv_users)) {
            return '';
        }
        if ($csv_users == csv_upload::REMOVE_CODE) {
            return get_string('upload_csv_remove', 'mod_approval');
        }
        $usernames = explode(',', $csv_users);
        $users = user::repository()
            ->select_full_name_fields()
            ->where_in('username', $usernames)
            ->get();
        if ($users->count() == 0) {
            return '';
        }
        $data = [];
        foreach ($users as $user) {
            $data[] = fullname((object)$user->get_attributes_raw());
        }
        return implode(', ', $data);
    }

    /**
     * @param string $shortname
     * @return array|\stdClass|null
     */
    public static function get_role(string $shortname) {
        return builder::table('role')
            ->where('shortname', $shortname)
            ->one();
    }

    /**
     * Display message in the console OR on UI overrides confirm page
     *
     * @param string $msg
     * @param string $notification_level
     */
    private function console(string $msg, string $notification_level = null) {
        if ($this->cmd) {
            echo($msg);
            return;
        }
        if ($notification_level == notification::ERROR) {
            notification::error($msg);
        }
    }
}
