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

defined('MOODLE_INTERNAL') || die();

use totara_msteams\check\checkable;
use totara_msteams\check\status;
use totara_msteams\output\manifest_download;
use totara_msteams\page_helper;

/**
 * totara_msteams_renderer class
 */
class totara_msteams_renderer extends plugin_renderer_base {
    /**
     * Render the download manifest page.
     *
     * @param moodle_url $downloadlink The URL of the download button
     * @param boolean $haserror The return value of verifier::execute()
     * @param array $result The return value of verifier::get_results()
     * @return string
     */
    public function render_manifest_download(moodle_url $downloadlink, bool $haserror, array $result): string {
        global $CFG;
        $table = new html_table();
        $table->summary = get_string('report:summary', 'totara_msteams');
        $table->attributes['class'] = 'generaltable';
        $table->head = [
            get_string('report:config', 'totara_msteams'),
            get_string('report:report', 'totara_msteams'),
            get_string('report:status', 'totara_msteams')
        ];
        $table->data = [];

        foreach ($result as $entry) {
            $result = $entry->result;
            $class = $entry->class;
            /** @var int $result */
            /** @var checkable $class */
            $name = clean_string($class->get_name());
            $config = $class->get_config_name();
            if (!empty($config)) {
                $configurl = new moodle_url("/{$CFG->admin}/search.php", ['query' => $config]);
                $name = html_writer::link($configurl, $name);
            }
            if ($result === status::PASS) {
                $report = get_string('check:pass', 'totara_msteams');
                $status = html_writer::span(get_string('report:status_pass', 'totara_msteams'), 'label label-success');
            } else {
                $report = clean_string($class->get_report());
                $helpurl = $class->get_helplink();
                if ($helpurl !== null) {
                    $report = html_writer::link($helpurl, $report, ['target' => '_blank']);
                }
                if ($result === status::SKIPPED) {
                    $status = html_writer::span(get_string('report:status_skipped', 'totara_msteams'), 'label label-info');
                    $report = new html_table_cell($report);
                    $report->attributes['class'] = 'dimmed_text';
                } else {
                    $status = html_writer::span(get_string('report:status_failed', 'totara_msteams'), 'label label-danger');
                }
            }
            $table->data[] = new html_table_row([
                $name,
                $report,
                $status
            ]);
        }

        $data = [
            'link' => $downloadlink->out(false),
            'error' => $haserror,
            'notification' => [
                'message' => $haserror ? get_string('info:badsettings', 'totara_msteams') : get_string('info:goodsettings', 'totara_msteams'),
            ],
            'table' => [
                'template' => 'core/table',
                'context' => $table->export_for_template($this)
            ]
        ];
        return $this->render(new manifest_download($data));
    }

    /**
     * Render the debug information.
     *
     * @param string[] $installedlangs
     * @param string[] $files as [$filename => $content]
     * @return string
     */
    public function render_manifest_download_debug(array $installedlangs, array $files): string {
        global $CFG;
        $output = '';

        $output .= $this->heading('Installed languages', 2);
        $output .= html_writer::tag('p', 'Default language: '.s($CFG->lang));
        $table = new html_table();
        $table->head = ['code', 'name'];
        foreach ($installedlangs as $lang => $name) {
            $table->data[] = [s($lang), s($name)];
        }
        $output .= html_writer::table($table);

        $pats = [
            // [regexp, colour]
            ['/(\"([^\"\\\\]|\\\\.)*\")\s*:/', '#960'],
            ['/(\'([^\'\\\\]|\\\\.)*\')\s*:/', '#960'],
            ['/(\"([^\"\\\\]|\\\\.)*\")/', '#080'],
            ['/(\'([^\'\\\\]|\\\\.)*\')/', '#080'],
            ['/\b(\d+|\d+\.\d*)\b/', '#900'],
            ['/\b(true|false|null)\b/', '#03c'],
        ];

        $output .= $this->heading('Packed files', 2);
        foreach ($files as $filespec => $content) {
            $output .= $this->heading(s($filespec), 3);
            $extension = substr($filespec, strrpos($filespec, '.'));
            if ($extension === '.json') {
                $out = '';
                $len = strlen($content);
                for ($i = 0; $i < $len;) {
                    foreach ($pats as $pat) {
                        if (preg_match($pat[0], $content, $matches, PREG_OFFSET_CAPTURE, $i) && $matches[0][1] == $i) {
                            $match = $matches[1][0];
                            $code = s($match);
                            if ($pat[1] !== '') {
                                $out .= "<span style=\"color:{$pat[1]}\">{$code}</span>";
                            } else {
                                $out .= $code;
                            }
                            $i += strlen($match);
                            continue 2;
                        }
                    }
                    $ch = substr($content, $i, 1);
                    $out .= $ch;
                    $i++;
                }
            } else if ($extension === '.png') {
                $out = '<img src="data:image/png;base64,'.base64_encode($content).'" alt="'.s($filespec).'">';
            } else {
                $out = s($content);
            }
            $output .= html_writer::tag('pre', html_writer::tag('code', $out));
        }

        return $output;
    }

    /**
     * Display the current learning contents.
     *
     * @return string
     */
    public function render_my_learning(): string {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
        require_once($CFG->dirroot . '/blocks/current_learning/block_current_learning.php');

        $instance = page_helper::find_block_instance('current_learning');

        $block = block_instance('current_learning', $instance, $PAGE);
        /** @var block_current_learning $block */

        if (!$instance) {
            $block->page = $PAGE;
            $block->instance = new stdClass();
            $block->instance->id = 0; // Add instance id so the get_content call doesn't error.
        }
        $content = $block->get_content();

        if ($instance) {
            // The tile view requires the id attribute.
            $attrs = ['id' => 'inst' . $instance->id];
        } else {
            $attrs = null;
        }
        // Put the content into two divs to make stylesheets happy.
        return html_writer::div(html_writer::div($content->text, 'content'), 'block block_current_learning', $attrs);
    }
}
