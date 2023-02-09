<?php
/**
 * This file is part of Totara Learn
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

use totara_msteams\oidcclient;
use totara_msteams\auth_helper;

require_once(__DIR__ . '/../../config.php');

\totara_core\advanced_feature::require('totara_msteams');

$wantsurl = required_param('returnurl', PARAM_LOCALURL);
if ($wantsurl === '') {
    $wantsurl = '/';
}

require_sesskey();

if (!is_enabled_auth('oauth2')) {
    throw new \moodle_exception('notenabled', 'auth_oauth2');
}

$issuer = auth_helper::get_oauth2_issuer(true);

// Pass through OAuth2 login.
$returnparams = ['wantsurl' => $wantsurl, 'sesskey' => sesskey(), 'id' => $issuer->get('id')];
$returnurl = new moodle_url('/auth/oauth2/login.php', $returnparams);

$client = new oidcclient($issuer, $returnurl, '');
if (!$client->is_logged_in_oidc()) {
    redirect($client->get_login_url());
}

$auth = get_auth_plugin('oauth2');
$auth->complete_login($client, new moodle_url($wantsurl));
