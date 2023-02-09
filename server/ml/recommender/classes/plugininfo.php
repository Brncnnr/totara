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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender;

use core\orm\query\builder;
use core\plugininfo\ml;
use totara_core\advanced_feature;

/**
 * Plugin info for recommender
 */
final class plugininfo extends ml {
    /**
     * For now it is not possible to delete this one.
     * @return bool
     */
    public function is_uninstall_allowed(): bool {
        return false;
    }

    public function get_usage_for_registration_data() {
        $data = array();
        $data['recommenderenabled'] = (int)advanced_feature::is_enabled('ml_recommender');
        $data['numinteractions'] = builder::table('ml_recommender_interactions')->count();
        $data['numitems'] = builder::table('ml_recommender_items')->count();
        $data['numtrending'] = builder::table('ml_recommender_trending')->count();
        $data['numusers'] = builder::table('ml_recommender_users')->count();

        return $data;
    }
}