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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package mod_certificate
 */

namespace mod_certificate\output;

use grade_grade;
use moodle_url;
use stdClass;

global $CFG;
require_once("{$CFG->dirroot}/mod/certificate/locallib.php");

abstract class view_generator {

    /**
     * Name of mustache template.
     * @var string
     */
    protected $template_name;

    /**
     * Certificate data object.
     * @var stdClass
     */
    protected $certificate;

    /**
     * Course data object.
     * @var stdClass
     */
    protected $course;

    /**
     * Course module data object.
     * @var stdClass
     */
    protected $course_module;

    /** @var string */
    protected $time_completed;

    /** @var grade_grade|string */
    protected $grade;

    /** @var string */
    protected $outcome;

    /** @var string */
    protected $code;

    /** @var array */
    protected $text_content = [];

    /** @var int */
    protected $teacher_y_offset = 0;

    /**
     * Creates an instance of this class
     *
     * @param stdClass $certificate Certificate module object
     */
    public function __construct(
        stdClass $certificate,
        stdClass $certificate_record,
        stdClass $course,
        stdClass $course_module
    ) {
        $this->certificate = $certificate;
        $this->course = $course;
        $this->course_module = $course_module;

        // Use archived values if certificate is archived.
        if (isset($certificate_record->timearchived)) {
            $this->time_completed = certificate_get_date_completed_formatted(
                $certificate,
                $certificate_record->time_completed
            );
            $this->grade = $certificate_record->grade;
            $this->outcome = $certificate_record->outcome;
            $this->code = $certificate_record->code;
        } else {
            $this->time_completed = certificate_get_date($certificate, $certificate_record, $course);
            $this->grade = certificate_get_grade($certificate, $course);
            $this->outcome = certificate_get_outcome($certificate, $course);
            $this->code = certificate_get_code($certificate, $certificate_record);
        }
    }

    /**
     * Gets the URL of the background image if it exists
     *
     * @return moodle_url|null The URL of the background image
     */
    protected function get_background_url(): ?moodle_url {
        global $CFG;
        $url = '/mod/certificate/pdfresources/' . CERT_IMAGE_BORDER . '/' . $this->certificate->borderstyle;

        if (file_exists($CFG->dirroot . $url)) {
            return new moodle_url($url);
        }

        return $this->get_custom_locator('border', $this->certificate->borderstyle);
    }

    /**
     * Gets the URL of the seal image if it exists.
     *
     * @return moodle_url|null The URL of the seal image
     */
    protected function get_seal_url(): ?moodle_url {
        global $CFG;
        $url = '/mod/certificate/pdfresources/' . CERT_IMAGE_SEAL . '/' . $this->certificate->printseal;

        if (file_exists($CFG->dirroot . $url)) {
            return new moodle_url($url);
        }

        return $this->get_custom_locator('seal', $this->certificate->printseal);
    }

    /**
     * Gets the URL of the signature image if it exists.
     *
     * @return moodle_url|null The URL of the signature image
     */
    protected function get_signature_url(): ?moodle_url {
        global $CFG;
        $url = '/mod/certificate/pdfresources/' . CERT_IMAGE_SIGNATURE . '/' . $this->certificate->printsignature;

        if (file_exists($CFG->dirroot . $url)) {
            return new moodle_url($url);
        }

        return $this->get_custom_locator('signature', $this->certificate->printsignature);
    }

    /**
     * Gets the URL of the watermark image if it exists
     *
     * @return moodle_url|null The URL of the watermark image
     */
    protected function get_watermark_url(): ?moodle_url {
        global $CFG;
        $url = '/mod/certificate/pdfresources/' . CERT_IMAGE_WATERMARK . '/' . $this->certificate->printwmark;

        if (file_exists($CFG->dirroot . $url)) {
            return new moodle_url($url);
        }

        return $this->get_custom_locator('watermark', $this->certificate->printwmark);
    }

    /**
     * Gets a moodle_url to a custom image to be displayed on the certificate
     *
     * @param string $filearea The file area to check
     * @param string $filename The file name to get
     *
     * @return moodle_url To the file if it exists
     */
    protected function get_custom_locator(string $filearea, string $filename): ?moodle_url {
        $context = \context_system::instance();
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_certificate', $filearea, 3);
        foreach ($files as $file) {
            $fname = $file->get_filename();
            if ($fname == $filename) {
                return moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename(),
                    false
                );
            }
        }
        return null;
    }

    /**
     * Generates a complete page for the certificate
     *
     * @return string The HTML to be feed to the browser
     */
    public function generate_html(): string {
        global $PAGE;
        $renderer = $PAGE->get_renderer('mod_certificate', 'view');
        return $renderer->render_from_template(
            "{$this->certificate->certificatetype}/{$this->template_name}",
            $this->export_for_template()
        );
    }

    /**
     * @return array
     */
    protected function export_for_template(): array {
        return [
            'font_sans' => get_config('certificate', 'fontsans'),
            'font_serif' => get_config('certificate', 'fontserif'),
            'title' => format_string($this->certificate->name),
            'direction' => right_to_left() ? 'right' : 'left',
            'background' => $this->export_background(),
            'watermark' => $this->export_watermark(),
            'seal' => $this->export_seal(),
            'signature' => $this->export_signature(),
            'border' => $this->export_border(),
            'body_content' => $this->export_body_content(),
        ];
    }

    /**
     * @return array
     */
    protected function export_background(): array {
        $bg_url = $this->get_background_url();

        return [
            'show_background' => !empty($bg_url),
            'background_url' => !empty($bg_url) ? $bg_url->out() : '',
        ];
    }

    /**
     * @return array
     */
    protected function export_watermark(): array {
        $watermark_url = $this->get_watermark_url();

        return [
            'show_watermark' => !empty($watermark_url),
            'watermark_url' => !empty($watermark_url) ? $watermark_url->out() : '',
        ];
    }

    /**
     * @return array
     */
    protected function export_seal(): array {
        $seal_url = $this->get_seal_url();

        return [
            'show_seal' => !empty($seal_url),
            'seal_url' => !empty($seal_url) ? $seal_url->out() : '',
        ];
    }

    /**
     * @return array
     */
    protected function export_signature(): array {
        $signature_url = $this->get_signature_url();

        return [
            'show_signature' => !empty($signature_url),
            'signature_url' => !empty($signature_url) ? $signature_url : '',
        ];
    }

    /**
     * @return array
     */
    protected function export_border(): array {
        $color = '';
        switch ($this->certificate->bordercolor) {
            case 1:
                $color = '#000000';
                break;
            case 2:
                $color = 'rgb(153, 102, 51)';
                break;
            case 3:
                $color = 'rgb(0, 51, 204)';
                break;
            case 4:
                $color = 'rgb(0, 180, 0)';
                break;
        }

        return [
            'show_border' => $this->certificate->bordercolor > 0,
            'border_color' => $color,
        ];
    }

    /**
     * @return array
     */
    protected function export_body_content(): array {
        global $USER;

        return [
            'certificate_title' => get_string('title', 'certificate'),
            'certify' => get_string('certify', 'certificate'),
            'user' => fullname($USER),
            'statement' => get_string('statement', 'certificate'),
            'course_full_name' => format_string($this->course->fullname),
            'time_completed' => $this->time_completed,
            'outcome' => $this->outcome,
            'grade' => $this->grade,
            'show_credit_hours' => !empty($this->certificate->printhours),
            'credit_hours' => get_string('credithours', 'certificate')
                . ': ' . $this->certificate->printhours,
            'code' => $this->code,
            'custom_text' => format_text($this->certificate->customtext),
            'teachers' => $this->export_teachers(),
        ];
    }

    /**
     * @return array
     */
    protected function export_teachers(): array {
        $data = [];
        if ($this->certificate->printteacher) {
            $i = 0;
            $teachers = self::get_teachers($this->course_module);
            foreach ($teachers as $teacher) {
                ++$i;
                $data[] = [
                    'name' => $teacher,
                    'y' => function ($y) use ($i) {
                        return $y + ($i * $this->teacher_y_offset);
                    }
                ];
            }
        }
        return $data;
    }

    /**
     * Gets the names of the trainers of the course
     *
     * @param stdClass $cm The context object of this certificate module
     *
     * @return array An array of strings containing the fullnames of the teachers
     */
    protected static function get_teachers($cm): array {
        $context = \context_module::instance($cm->id);
        $teacher_names = [];
        $teachers = get_users_by_capability(
            $context,
            'mod/certificate:printteacher',
            '',
            'u.lastname ASC',
            '',
            '',
            '',
            '',
            false
        );

        foreach ($teachers as $teacher) {
            $teacher_names[] = fullname($teacher);
        }

        return $teacher_names;
    }

    /**
     * Get the certificate type this generator generates a view for.
     *
     * @return string
     */
    abstract public function get_type(): string;
}