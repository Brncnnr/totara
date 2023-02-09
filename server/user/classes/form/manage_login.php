<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core_user
 */

namespace core_user\form;

use totara_form\form\element\checkbox;
use totara_form\form\element\radios;
use totara_form\form\element\passwordunmask;
use totara_form\form\group\section;
use totara_form\form\element\static_html;
use totara_form\form\element\hidden;
use totara_form\form\clientaction\hidden_if;
use totara_form\form\element\action_button;
use totara_form\form\group\buttons;

defined('MOODLE_INTERNAL') || die();

/**
 * Manage user login form.
 */
final class manage_login extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $CFG, $USER;

        $parameters = $this->get_parameters();
        $user = $parameters['user'];
        $currentuser = ($USER->id == $user->id);
        /** @var \auth_plugin_base $auth */
        $auth = $parameters['auth'];

        if ($user->deleted || !$user->confirmed) {
            throw new \coding_exception('Form is not compatible with deleted and unconfirmed users');
        }

        $actions = [];
        $problems = [];

        $access = \core_user\access_controller::for($user);
        $canchangepassword = (($auth->can_change_password() && !$auth->change_password_url()) && !$currentuser);
        $lockedout = login_is_lockedout($user);

        $this->model->add(new hidden('id', PARAM_INT));
        $this->model->add(new hidden('returnto', PARAM_ALPHANUMEXT));
        $this->model->add(new hidden('returnurl', PARAM_LOCALURL));

        $section = new section('generalhdr', get_string('useraccount'));
        $section->set_expanded(true);
        $this->model->add($section);

        $this->model->add(new static_html('username', get_string('username'), clean_string($user->username)));

        $this->model->add(new static_html('email', get_string('email'), clean_string($user->email)));

        $this->model->add(new static_html('auth', get_string('authentication', 'core'), $auth->get_title()));
        if (!is_enabled_auth($user->auth)) {
            $problems['authdisabled'] = get_string('pluginnotenabled', 'core_auth', $auth->get_title());
        }

        if ($user->suspended == 0) {
            $suspendedstr =  get_string('no');
        } else {
            $suspendedstr =  get_string('yes');
            $problems['suspended'] = get_string('suspended', 'core_auth');
        }
        $this->model->add(new static_html('suspended', get_string('suspended'), $suspendedstr));

        if ($user->tenantid) {
            $tenant = \core\record\tenant::fetch($user->tenantid);
            $this->model->add(new static_html('tenantmember', get_string('tenantmember', 'totara_tenant'), format_string($tenant->name)));
            if ($tenant->suspended) {
                $problems['tenantsuspended'] = get_string('tenantsuspended', 'totara_tenant');
            }
        }

        // Note: there is no access control for last/current login, so show it always here for diagnostic purposes.
        $lastlogin = $user->currentlogin ? userdate($user->currentlogin) : get_string('never');
        $this->model->add(new static_html('lastlogin', get_string('lastlogin'), $lastlogin));

        if ($access->can_view_field('lastaccess')) {
            $lastaccess = $user->lastaccess ? userdate($user->lastaccess) : get_string('never');
            $this->model->add(new static_html('lastaccess', get_string('lastaccess'), $lastaccess));
        }

        if ($access->can_view_field('lastip')) {
            $lastip = ($user->lastip && $user->lastip != '0.0.0.0') ? $user->lastip : get_string('notavailable', 'totara_core');
            $this->model->add(new static_html('lastip', get_string('lastip'), $lastip));
        }

        if ($lockedout) {
            $problems['lockedout'] = get_string('lockedoutuser', 'totara_reportbuilder');
            $actions['unlock'] = get_string('unlockaccount', 'core_admin');
        }

        if ($user->suspended) {
            $actions['unsuspend'] = get_string('unsuspenduser', 'core_admin');
        } else {
            if (!is_siteadmin($user) && !$currentuser) {
                $actions['suspend'] = get_string('suspenduser', 'core_admin');
            }
        }

        if (empty($problems) or array_keys($problems) === ['lockedout']) {
            if ($canchangepassword && !$currentuser && $auth->is_internal()) {
                $actions['createpassword'] = get_string('createpassword', 'auth');
            }
        }

        if ($canchangepassword) {
            $actions['changepassword'] = get_string('changepassword', 'core');
        }

        if ($problems) {
            $loginproblems = array();
            foreach ($problems as $problem) {
                $loginproblems[] = '<li>' .  $problem . '</li>';
            }
            $loginproblems = '<ul>' . implode($loginproblems) . '</ul>';
            $this->model->add(new static_html('loginproblems', get_string('loginproblems', 'core_auth'), $loginproblems));

            if ($lockedout) {
                $this->model->add(new static_html('lockedout', '', get_string('lockedoutuserinfo', 'core_auth')));
            }
        }

        if ($actions) {
            $section = new section('actionhdr', get_string('action'));
            $section->set_expanded(true);
            $this->model->add($section);

            $action = new radios('action', get_string('choose'), $actions);
            $action->set_attribute('required', true);
            $this->model->add($action);

            if (isset($actions['changepassword'])) {
                if ($CFG->passwordpolicy) {
                    $policy = new static_html('passwordpolicy', get_string('passwordpolicy', 'core_admin'), print_password_policy());
                    $this->model->add($policy);
                    $hiddenpolicy = new hidden_if($policy);
                    $hiddenpolicy->not_equals($action, 'changepassword');
                    $this->model->add_clientaction($hiddenpolicy);
                }

                $newpassword = new passwordunmask('newpassword', get_string('newpassword'));
                $this->model->add($newpassword);
                $hiddennewpassword = new hidden_if($newpassword);
                $hiddennewpassword->not_equals($action, 'changepassword');
                $this->model->add_clientaction($hiddennewpassword);

                if (!$currentuser) {
                    $forcepasswordchange = new checkbox('forcepasswordchange', get_string('forcepasswordchange'));
                    $this->model->add($forcepasswordchange);
                    $hiddenforcepasswordchange = new hidden_if($forcepasswordchange);
                    $hiddenforcepasswordchange->not_equals($action, 'changepassword');
                    $this->model->add_clientaction($hiddenforcepasswordchange);
                }
            }

            $this->model->add_action_buttons(true, get_string('update'));

        } else {
            $buttongroup = $this->model->add(new buttons('actionbuttonsgroup'), -1);
            $buttongroup->add(new action_button('cancelbutton', get_string('cancel'), action_button::TYPE_CANCEL));
        }
    }

    protected function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);

        if (isset($data['action'])) {
            if ($data['action'] === 'changepassword') {
                if ($data['newpassword'] === '') {
                    $errors['newpassword'] = get_string('required');
                } else {
                    $errmsg = '';
                    if (!check_password_policy($data['newpassword'], $errmsg)) {
                        $errors['newpassword'] = $errmsg;
                    }
                }
            }
        }

        return $errors;
    }
}