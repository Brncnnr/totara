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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_mvc
 */

namespace totara_mvc;

defined('MOODLE_INTERNAL') || die();

/**
 * Extend this controller if your page is based on an admin external page defined in the settings.php.
 *
 * You have to at least set $admin_external_page_name to the one defined for the admin_external_page.
 *
 * @package totara_mvc
 */
abstract class admin_controller extends controller {

    /**
     * Set this to the name of the external_page, usually defined in settings.php
     *
     * @var string
     */
    protected $admin_external_page_name;

    /**
     * Set this if the actual page being viewed is not the one defined in the settings for this admin external page
     *
     * @var string
     */
    protected $admin_actual_url = '';

    /**
     * Parameters that need to be added to the admin external page url
     *
     * @var null|array
     */
    protected $admin_extra_url_params = null;

    /**
     * Additional options for the admin_external_page_setup call, check admin_externalpage_setup() for valid options
     *
     * @var array
     */
    protected $admin_options = [];

    /**
     * Before the actual process run admin_externalpage_setup
     *
     * @param string $action
     * @return void
     */
    public function process(string $action = '') {
        global $CFG;

        if (empty($this->admin_external_page_name)) {
            throw new \coding_exception('Missing external page name in controller.');
        }
        require_once($CFG->libdir.'/adminlib.php');

        // if there's a specific layout defined in the controller use this instead of the default
        $options = array_merge(
            $this->admin_options,
            !empty($this->layout) ? ['pagelayout' => $this->layout] : []
        );
        // Prepare this admin page by checking login, access and set all necessary PAGE params
        admin_externalpage_setup(
            $this->admin_external_page_name,
            '', // not used
            $this->admin_extra_url_params,
            $this->admin_actual_url,
            $options
        );

        if ($url = $this->get_page()->url) {
            $this->set_url($url);
        }

        parent::process($action);
    }

}
