<?php
/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\detail;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context;
use context_module;
use context_system;
use core\output\notification;
use moodle_exception;
use moodle_url;
use rb_config;
use rb_global_restriction_set;
use reportbuilder;
use totara_reportbuilder_renderer;
use mod_facetoface\output\seminarevent_actionbar;
use mod_facetoface\output\seminarresource_card;
use mod_facetoface\output\seminarresource_information;
use mod_facetoface\seminar_attachment_item;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface_renderer;

/**
 * Abstract class to render the detail page.
 */
abstract class content_generator {
    /**
     * @var string {report_builder}.shortname
     */
    private $reportshortname;

    /**
     * @var moodle_url page url
     */
    private $baseurl;

    /**
     * @var string the label of the edit button
     */
    private $managelabel;

    /**
     * @var moodle_url the url of the edit button
     */
    private $manageurl;

    /**
     * @var integer the id of the current resource item
     */
    private $id = 0;

    /**
     * @var moodle_url the url of the go back button
     */
    private $backurl;

    /**
     * @var boolean the debug flag that is passed to the report builder
     */
    private $debug = false;

    /**
     * @var boolean the popup layout for backward compatibility
     * @deprecated Totara 13
     */
    private $popup = false;

    /**
     * @var integer seminar_event.id aka {facetoface_sessions}.id
     */
    private $eventid = 0;

    /**
     * @var integer seminar_session.id aka {facetoface_sessions_dates}.id
     */
    private $sessionid = 0;

    /**
     * @var boolean read-only view
     */
    private $view = false;

    /**
     * Constructor.
     *
     * @param string $idparam a parameter name that represents 'id'
     * @param string $section the name of page
     * @param string $reportshortname the shortname of a report_builder record
     * @param string|moodle_url $baseurl the URL to this page
     */
    public function __construct(string $idparam, string $section, string $reportshortname, $baseurl) {
        $this->reportshortname = $reportshortname;
        $this->baseurl = new moodle_url($baseurl);

        $this->id = optional_param($idparam, 0, PARAM_INT);
        $this->backurl = optional_param('b', '', PARAM_URL);
        $this->debug = (bool)optional_param('debug', 0, PARAM_INT);
        $this->popup = (bool)optional_param('popup', 0, PARAM_INT);
        $this->eventid = optional_param('sid', 0, PARAM_INT);
        $this->sessionid = optional_param('sdid', 0, PARAM_INT);
        $this->view = (bool)optional_param('view', 0, PARAM_INT);

        $params = [
            $idparam => $this->id,
            'b' => $this->backurl,
            'debug' => $this->debug,
            'popup' => $this->popup,
            'sid' => $this->eventid,
            'view' => $this->view,
        ];
        if (!empty($this->sessionid)) {
            $params['sdid'] = $this->sessionid;
        }

        foreach ($params as $name => $value) {
            if (!empty($value)) {
                $this->baseurl->params([$name => $value]);
            } else {
                $this->baseurl->remove_params([$name]);
            }
        }
    }

    /**
     * Instantiate an item from $id.
     *
     * @param integer $id
     * @return seminar_attachment_item
     * @throws moodle_exception
     */
    abstract protected function load(int $id): seminar_attachment_item;

    /**
     * Get the title string of a page.
     *
     * @param seminar_attachment_item $item an item returned by load()
     * @return string
     */
    abstract protected function get_title(seminar_attachment_item $item): string;

    /**
     * See if the user is capable to edit an item.
     *
     * @param seminar_attachment_item $item an item returned by load()
     * @param context $context current context of either system or activity
     * @param stdClass $user
     * @return boolean
     */
    abstract protected function has_edit_capability(seminar_attachment_item $item, context $context, stdClass $user): bool;

    /**
     * See if the user is capable to see a report.
     *
     * @param seminar_attachment_item $item an item returned by load()
     * @param context $context current context of either system or activity
     * @param stdClass $user
     * @return boolean
     */
    abstract protected function has_report_capability(seminar_attachment_item $item, context $context, stdClass $user): bool;

    /**
     * Get the header string of a report.
     *
     * @param seminar_attachment_item $item an item returned by load()
     * @return string
     */
    abstract protected function get_report_header(seminar_attachment_item $item): string;

    /**
     * Render a detailed content.
     *
     * @param seminar_attachment_item $item an item returned by load()
     * @param stdClass $user
     * @param mod_facetoface_renderer $renderer
     * @return string
     */
    abstract protected function render_details(seminar_attachment_item $item, stdClass $user, mod_facetoface_renderer $renderer): string;

    /**
     * Render an empty content.
     *
     * @param moodle_url $manageurl
     * @return string
     */
    abstract protected function render_empty(moodle_url $manageurl): string;

    /**
     * Render a card content.
     *
     * @param seminar_session|null $session the current seminar session or null if nothing applicable
     * @param seminar_attachment_item $item an item returned by load()
     * @param stdClass $user
     * @param mod_facetoface_renderer $renderer
     * @return seminarresource_card|null
     */
    abstract protected function render_card(?seminar_session $session, seminar_attachment_item $item, stdClass $user, mod_facetoface_renderer $renderer): ?seminarresource_card;

    /**
     * Render a notification banner.
     *
     * @param seminar_session|null $session the current seminar session or null if nothing applicable
     * @param seminar_attachment_item $item an item returned by load()
     * @param stdClass $user
     * @param mod_facetoface_renderer $renderer
     * @return notification|null
     */
    abstract protected function render_banner(?seminar_session $session, seminar_attachment_item $item, stdClass $user, mod_facetoface_renderer $renderer): ?notification;

    /**
     * Get the label of the 'Manage (thing)' button.
     *
     * @param boolean $frommanage true if a user comes from the manage page, otherwise false
     * @return string label text
     */
    abstract protected function get_manage_button(bool $frommanage): string;

    /**
     * Get the URL to which the 'Manage (thing)' button links.
     *
     * @param boolean $frommanage
     * @return moodle_url URL
     */
    abstract protected function get_manage_url(bool $frommanage): moodle_url;

    /**
     * Get the label of the edit button.
     *
     * @param seminar_attachment_item $item an item returned by load()
     * @return string label text
     */
    abstract protected function get_edit_button(seminar_attachment_item $item): string;

    /**
     * Get the URL to which the edit button links.
     *
     * @param seminar_attachment_item $item an item returned by load()
     * @return moodle_url|null URL or null to disable a button
     */
    abstract protected function get_edit_url(seminar_attachment_item $item): ?moodle_url;

    /**
     * Get the page URL.
     *
     * @return moodle_url
     */
    public function get_page_url(): moodle_url {
        return clone $this->baseurl;
    }

    /**
     * Set up $PAGE and render the entire content of the web page.
     */
    public function display(): void {
        global $OUTPUT, $PAGE;

        $systemcontext = context_system::instance();
        if (!empty($this->eventid)) {
            $cm = (new seminar_event($this->eventid))->get_seminar()->get_coursemodule();
            $context = context_module::instance($cm->id);
        } else if (!empty($this->sessionid)) {
            $cm = (new seminar_session($this->sessionid))->get_seminar_event()->get_seminar()->get_coursemodule();
            $context = context_module::instance($cm->id);
        } else {
            $cm = null;
            $context = $systemcontext;
        }

        // 'popup' is no longer used; it is just for backward compatibility.
        if ($this->popup) {
            $PAGE->set_pagelayout('popup');
        } else {
            $PAGE->set_pagelayout('noblocks');
        }

        $PAGE->set_context($systemcontext);
        $PAGE->set_url($this->baseurl);
        if (!empty($cm)) {
            $PAGE->set_cm($cm);
        }

        $this->managelabel = $this->get_manage_button(!$this->view);
        $this->manageurl = $this->get_manage_url(!$this->view);
        $this->manageurl->param('published', 0);

        if (empty($this->id)) {
            echo $OUTPUT->header();
            $this->display_empty();
        } else {
            $item = $this->load($this->id);
            $title = $this->get_title($item);
            $PAGE->set_title($title);
            $PAGE->set_heading($title);
            echo $OUTPUT->header();
            $this->display_content($item, $context);
        }
        echo $OUTPUT->footer();
    }

    /**
     * Echo page content when id is not passed.
     */
    protected function display_empty(): void {
        global $OUTPUT;
        $text = $this->render_empty($this->manageurl);
        echo $OUTPUT->container($text);
    }

    /**
     * Echo page content when id is passed.
     * @param seminar_attachment_item $item
     * @param context $context system context for site wide items, module context for adhoc items
     */
    protected function display_content(seminar_attachment_item $item, context $context): void {
        global $PAGE, $USER;

        $report = $this->create_report($item, $context);
        if ($report) {
            $PAGE->set_button($report->edit_button());
        }

        /** @var \mod_facetoface_renderer $renderer */
        $renderer = $PAGE->get_renderer('mod_facetoface');
        $renderer->setcontext($context);

        $data = [];

        $hascapability = $this->has_edit_capability($item, $context, $USER);
        if ($hascapability) {
            $editlabel = $this->get_edit_button($item);
            $editurl = $this->get_edit_url($item);
            $disabled = $editurl === null;
            if ($disabled) {
                $editurl = '#';
            } else {
                $editurl->param('b', $PAGE->url->out_as_local_url(false));
            }
            $builder = seminarevent_actionbar::builder()
                ->set_align('far')
                ->set_class('eventdetail')
                ->add_commandlink('edit', $editurl, $editlabel, false, $disabled);
            $data['headeraction'] = $builder->build()->get_template_data();
        }

        $data['heading'] = $PAGE->title;

        $session = !empty($this->sessionid) ? new seminar_session($this->sessionid) : null;
        $card = $this->render_card($session, $item, $USER, $renderer);
        if ($card !== null) {
            $data['card'] = [
                'template' => $card->get_template_name(),
                'context' => $card->get_template_data(),
            ];
        }
        $banner = $this->render_banner($session, $item, $USER, $renderer);
        if ($banner !== null) {
            $data['banner'] = [
                'template' => $banner->get_template_name(),
                'context' => $banner->export_for_template($renderer),
            ];
        }

        $data['details'] = $this->render_details($item, $USER, $renderer);

        if ($report) {
            $report->display_restrictions();

            $data['report'] = [];
            $data['report']['header'] = $this->get_report_header($item);

            /** @var totara_reportbuilder_renderer $reportrenderer */
            $reportrenderer = $PAGE->get_renderer('totara_reportbuilder');

            // This must be done after the header and before any other use of the report.
            list($reporthtml, $debughtml) = $reportrenderer->report_html($report, $this->debug);
            $data['report']['debughtml'] = $debughtml;

            $data['report']['description'] = $reportrenderer->print_description($report->description, $report->_id);

            // Print saved search options and filters.
            $data['report']['searchoptions'] = self::capture_output(function (reportbuilder $report) {
                $report->display_saved_search_options();
            }, $report);
            $data['report']['search'] = self::capture_output(function (reportbuilder $report) {
                $report->display_search();
            }, $report);
            $data['report']['sidebar'] = self::capture_output(function (reportbuilder $report) {
                $report->display_sidebar_search();
            }, $report);

            $data['report']['reporthtml'] = $reporthtml;

            $report->include_js();
        }

        $builder = seminarevent_actionbar::builder()
            ->set_class('detailfooter')
            ->set_align('near');

        if (!$this->popup && !empty($this->backurl)) {
            $builder->add_commandlink('goback', new moodle_url($this->backurl), get_string('goback', 'mod_facetoface'));
        }

        if (!$this->popup && $hascapability) {
            $builder->add_commandlink('manage', $this->manageurl, $this->managelabel);
        }

        $data['footeraction'] = $builder->build()->get_template_data();
        echo $renderer->render(new seminarresource_information($data));
    }

    /**
     * @param callable $callback
     * @param mixed ...$args
     * @return string
     */
    private static function capture_output(callable $callback, ...$args): string {
        ob_start();
        try {
            $callback(...$args);
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Create a reportbuilder instance.
     * @param seminar_attachment_item $item
     * @param context $context
     * @return reportbuilder|null an instance object, or null if the current user does not have a capability
     * @throws moodle_exception do not have to handle this exception as it comes through print_error()
     */
    protected function create_report(seminar_attachment_item $item, context $context): ?reportbuilder {
        global $DB, $USER;

        if (!$this->has_report_capability($item, $context, $USER)) {
            return null;
        }
        // Verify global restrictions.
        $reportrecord = $DB->get_record('report_builder', array('shortname' => $this->reportshortname));
        $globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
        $config = (new rb_config())->set_global_restriction_set($globalrestrictionset)->set_sid($this->eventid);
        $report = reportbuilder::create_embedded($this->reportshortname, $config);
        if (!$report) {
            \print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
        }
        return $report;
    }
}
