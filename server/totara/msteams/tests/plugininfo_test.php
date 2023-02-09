<?php
/*
 * This file is part of Totara Engage
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_msteams
 */

use totara_core\advanced_feature;
use totara_msteams\botfw\entity\bot;
use totara_msteams\botfw\entity\channel;
use totara_msteams\botfw\entity\subscription;
use totara_msteams\botfw\entity\tenant;
use totara_msteams\botfw\entity\user;
use totara_msteams\plugininfo;

/**
 * @group totara_msteams
 */
class totara_msteams_plugininfo_testcase extends advanced_testcase {

    public function test_plugininfo_data() {
        $this->setAdminUser();

        $plugininfo = new plugininfo();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['msteamsenabled']);
        $this->assertEquals(0, $result['numbots']);
        $this->assertEquals(0, $result['numusers']);
        $this->assertEquals(0, $result['numchannels']);
        $this->assertEquals(0, $result['numsubscriptions']);
        $this->assertEquals(0, $result['numtenants']);

        // Generate test data
        $this->generate_data();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['msteamsenabled']);
        $this->assertEquals(1, $result['numbots']);
        $this->assertEquals(1, $result['numusers']);
        $this->assertEquals(1, $result['numchannels']);
        $this->assertEquals(1, $result['numsubscriptions']);
        $this->assertEquals(1, $result['numtenants']);

        advanced_feature::disable('totara_msteams');
        $result = $plugininfo->get_usage_for_registration_data();

        // Data should be returned even if MS teams is disabled.
        $this->assertEquals(0, $result['msteamsenabled']);
        $this->assertEquals(1, $result['numbots']);
        $this->assertEquals(1, $result['numusers']);
        $this->assertEquals(1, $result['numchannels']);
        $this->assertEquals(1, $result['numsubscriptions']);
        $this->assertEquals(1, $result['numtenants']);
    }

    /**
     * Generate data required to set registration stats
     */
    private function generate_data() {
        $msbot = new bot();
        $msbot->bot_id = '28:1aMAb0t';
        $msbot->bot_name = 'mybot';
        $msbot->service_url = 'https://example.com/api';
        $msbot->save();

        $mstenant = new tenant();
        $mstenant->tenant_id = '31415926-5358-9793-2384-626433832795';
        $mstenant->save();

        $mschannel = new channel();
        $mschannel->channel_id = '19:kIa0RAkoUt0u';
        $mschannel->save();

        $user = $this->getDataGenerator()->create_user();

        $msuser = new user();
        $msuser->verified = true;
        $msuser->userid = $user->id;
        $msuser->teams_id = '29:K1aKahAN3wzEa1ANd';
        $msuser->mschannelid = $mschannel->id;
        $msuser->save();

        $subscription = new subscription();
        $subscription->msbotid = $msbot->id;
        $subscription->mstenantid = $mstenant->id;
        $subscription->msuserid = $msuser->id;
        $subscription->save();
    }
}
