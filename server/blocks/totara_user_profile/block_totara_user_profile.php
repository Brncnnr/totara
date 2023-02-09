<?php
 /*
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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package block_totara_user_profile
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main block file
 */
class block_totara_user_profile extends block_base {

    /**
     * Initialises this block instance
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_totara_user_profile');
    }

    /**
     * Creating an abstract tree for block settings
     *
     * @param \core_user\output\myprofile\tree $tree Tree object
     */
    public static function block_user_profile_settings(core_user\output\myprofile\tree $tree) {

        $nodes = $tree->nodes;

        if (!isset($nodes['fullprofile'])) {
            $node = new core_user\output\myprofile\node('miscellaneous', 'fullprofile', get_string('fullprofile'));
            $tree->add_node($node);
        }

        $node = new  core_user\output\myprofile\node('administration', 'loginas', get_string('loginas'));
        $tree->add_node($node);

        if (!isset($nodes['city'])) {
            $node = new core_user\output\myprofile\node('contact', 'city', get_string('city'));
            $tree->add_node($node);
        }

        if (!isset($nodes['country'])) {
            $node = new core_user\output\myprofile\node('contact', 'country', get_string('country'));
            $tree->add_node($node);
        }
        if (!isset($nodes['address'])) {
            $node = new core_user\output\myprofile\node('contact', 'address', get_string('address'));
            $tree->add_node($node);
        }

        if (!isset($nodes['phone1'])) {
            $node = new core_user\output\myprofile\node('contact', 'phone1', get_string('phone1'));
            $tree->add_node($node);
        }

        if (!isset($nodes['phone2'])) {
            $node = new core_user\output\myprofile\node('contact', 'phone2', get_string('phone2'));
            $tree->add_node($node);
        }

        if (!isset($nodes['institution'])) {
            $node = new core_user\output\myprofile\node('contact', 'institution', get_string('institution'));
            $tree->add_node($node);
        }

        if (!isset($nodes['department'])) {
            $node = new core_user\output\myprofile\node('contact', 'department', get_string('department'));
            $tree->add_node($node);
        }

        if (!isset($nodes['idnumber'])) {
            $node = new core_user\output\myprofile\node('contact', 'idnumber', get_string('idnumber'));
            $tree->add_node($node);
        }

        if (!isset($nodes['webpage'])) {
            $node = new core_user\output\myprofile\node('contact', 'webpage', get_string('webpage'));
            $tree->add_node($node);
        }

        if (!isset($nodes['interests'])) {
            $node = new core_user\output\myprofile\node('contact', 'interests', get_string('interests'));
            $tree->add_node($node);
        }

        if (!isset($nodes['courseprofiles'])) {
            $node = new core_user\output\myprofile\node('coursedetails', 'courseprofiles', get_string('courseprofiles'));
            $tree->add_node($node);
        }

        if (!isset($nodes['roles'])) {
            $node = new core_user\output\myprofile\node('coursedetails', 'roles', get_string('roles'));
            $tree->add_node($node);
        }

        if (!isset($nodes['groups'])) {
            $node = new core_user\output\myprofile\node('coursedetails', 'groups', get_string('group'));
            $tree->add_node($node);
        }

        if (!isset($nodes['suspended'])) {
            $node = new core_user\output\myprofile\node('coursedetails', 'suspended', get_string('suspended'));
            $tree->add_node($node);
        }

        if (!isset($nodes['skypeid'])) {
            $node = new core_user\output\myprofile\node('contact', 'skypeid', get_string('skypeid'));
            $tree->add_node($node);
        }
        return $tree;
    }

    /**
     * Creating new tree for users with visibility restriction
     *
     * @param \core_user\output\myprofile\tree $tree Full build profile tree object
     *
     * @return \core_user\output\myprofile\tree
     */
    private function tree_filter($tree) {
        $filteredtree = new core_user\output\myprofile\tree();
        if (!empty($this->config->category)) {
            $configcategory = $this->config->category;
        } else {
            $configcategory = '';
        }
        foreach ($tree->categories as $category => $cat) {
            if ($configcategory == $category) {
                // Prevent passing null as input parameter in PHP 8.1
                $catclasses = $cat->classes ?? '';
                //Create detail name for category classes for better navigation
                if (strpos($catclasses, $cat->name) === false) {
                    $catclasses =  trim('block_totara_user_profile_category_' . $cat->name . ' ' . $catclasses);
                }
                $newcategory = new core_user\output\myprofile\category($cat->name, $cat->title, null, $catclasses);
                $filteredtree->add_category($newcategory);
                foreach ($cat->nodes as $nodename => $node) {
                    $newnode = new core_user\output\myprofile\node($cat->name, $node->name, $node->title, '',
                    $node->url, $node->content, $node->icon, $node->classes);
                    if (!isset($this->config->$nodename) && count($cat->nodes) > 1) {
                        $newcategory->add_node($newnode);
                    }
                    if (empty($this->config->$nodename) && count($cat->nodes) > 1) {
                        continue;
                    }
                    $newcategory->add_node($newnode);
                }
            }
        }
        return $filteredtree;
    }

    /**
     * Generates and returns the content of the block
     *
     * @return stdClass
     */
    public function get_content() {
        global $DB, $USER, $CFG;


        if ($this->content !== null) {
            return $this->content;
        }

        if (!empty($CFG->forceloginforprofiles) && isguestuser() || !empty($CFG->forcelogin) && !isloggedin()) {
            $this->content = null;
            return;
        }

        // Get user or course from page context
        $page_context =  $this->page->context;
        $course = null;
        if ($page_context->contextlevel == CONTEXT_USER) {
            if ($page_context->instanceid == $USER->id) {
                $user = $USER;
            } else {
                $user = $DB->get_record('user', array('id' => $page_context->instanceid));
            }
        } else if ($page_context->contextlevel == CONTEXT_COURSE) {
            $course = $DB->get_record('course', array('id' => $page_context->instanceid), '*', MUST_EXIST);
            $extending = $this->page->navigation->get_extending_users();
            if (count($extending) == 1) {
                $user = end($extending);
            } else {
                $user = $USER;
            }
        }

        if (empty($user)) {
            // Gives ability for admin maintain User Profile blocks on Default Profile Page.
            if (is_siteadmin()) {
                $user = $USER;
            } else {
                $this->content = null;
                return;
            }
        }

        $currentuser = ($user->id == $USER->id);
        $this->content = new stdClass();
        /**
         * @var core_user\output\myprofile\renderer $renderer
         */
        $renderer = $this->page->get_renderer('core_user', 'myprofile');
        $tree = $this->build_profile_tree($user, $currentuser, $course);
        $newtree = $this->tree_filter($tree);

        $category = current($newtree->categories);
        if (empty($category) || empty($category->nodes)) {
            $this->content = null;
        } else {
            $this->content->text = $renderer->render($newtree);
        }
    }

    /**
     * Build the my profile tree or fetch one if already cached.
     * The cache only exists during page loading.
     *
     * @param \stdClass $user
     * @param boolean $currentuser
     * @param \stdClass|null $course
     * @return \core_user\output\myprofile\tree
     */
    private function build_profile_tree($user, $currentuser, $course) {
        static $cached_trees = [];
        $currentornot = $currentuser ? 'y' : 'n';
        $courseid = $course ? $course->id : 0;
        $cache_key = "{$user->id}-{$currentornot}-{$courseid}";
        if (!isset($cached_trees[$cache_key])) {
            $tree = core_user\output\myprofile\manager::build_tree($user, $currentuser, $course);
            $cached_trees[$cache_key] = $tree;
        }
        return fullclone($cached_trees[$cache_key]);
    }

    /**
     * Allow more than one instance of the block on a page
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Allow instances to have their own configuration
     *
     * @return boolean
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'user-profile' => true
        ];
    }

    /**
     * Show block with no header if no override title
     *
     * @return boolean
     */

    public function display_with_header(): bool {
        return $this->get_common_config_value('override_title');
    }
}
