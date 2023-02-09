<?php

// This file is part of the Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * letter_embedded certificate type
 *
 * @package    mod_certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (isset($certrecord->timearchived)) {
    // Use archived values
    $timecompleted = certificate_get_date_completed_formatted($certificate, $certrecord->timecompleted);
    $grade = $certrecord->grade;
    $outcome = $certrecord->outcome;
    $code = $certrecord->code;
} else {
    $timecompleted = certificate_get_date($certificate, $certrecord, $course);
    $grade = certificate_get_grade($certificate, $course);
    $outcome = certificate_get_outcome($certificate, $course);
    $code = certificate_get_code($certificate, $certrecord);
}

$pdf = new PDF($certificate->orientation, 'pt', 'Letter', true, 'UTF-8', false);

$pdf->SetTitle(format_string($certificate->name));
$pdf->SetProtection(array('modify'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// Define variables
// Landscape
if ($certificate->orientation == 'L') {
    $x = 28;
    $y = 125;
    $sealx = 590;
    $sealy = 425;
    $sigx = 130;
    $sigy = 440;
    $custx = 133;
    $custy = 440;
    $wmarkx = 100;
    $wmarky = 90;
    $wmarkw = 600;
    $wmarkh = 420;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 792;
    $brdrh = 612;
    $codey = 505;
} else { // Portrait
    $x = 28;
    $y = 170;
    $sealx = 440;
    $sealy = 590;
    $sigx = 85;
    $sigy = 580;
    $custx = 88;
    $custy = 580;
    $wmarkx = 78;
    $wmarky = 130;
    $wmarkw = 450;
    $wmarkh = 480;
    $brdrx = 10;
    $brdry = 10;
    $brdrw = 594;
    $brdrh = 771;
    $codey = 660;
}

// Get font families.
$fontsans = get_config('certificate', 'fontsans');
$fontserif = get_config('certificate', 'fontserif');

// Add images and lines
certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
certificate_draw_frame_letter($pdf, $certificate);
// Set alpha to semi-transparency
$pdf->SetAlpha(0.1);
certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
$pdf->SetAlpha(1);
certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, '', '');
certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

// Add text
$pdf->SetTextColor(0, 0, 120);
certificate_print_text($pdf, $x, $y, 'C', $fontsans, '', 30, get_string('title', 'certificate'));
$pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $x, $y + 55, 'C', $fontserif, '', 20, get_string('certify', 'certificate'));
certificate_print_text($pdf, $x, $y + 105, 'C', $fontserif, '', 30, fullname($USER));
certificate_print_text($pdf, $x, $y + 155, 'C', $fontserif, '', 20, get_string('statement', 'certificate'));
certificate_print_text($pdf, $x, $y + 205, 'C', $fontserif, '', 20, format_string($course->fullname));
certificate_print_text($pdf, $x, $y + 255, 'C', $fontserif, '', 14, $timecompleted);

// Conditionally increase $y by a set value to print out additional info. Leave $y as is if nothing to print.
$ycond = $y + 255 + 28; // Left as a sum on purpose to show a set increase.
certificate_print_text($pdf, $x, $ycond, 'C', $fontserif, '', 10, $grade);
if ($outcome !== '') {
    $ycond = $ycond + 28;
    certificate_print_text($pdf, $x, $ycond, 'C', $fontserif, '', 10, $outcome);
}
if ($certificate->printhours) {
    $ycond = $ycond + 28;
    certificate_print_text($pdf, $x, $ycond, 'C', $fontserif, '', 10, get_string('credithours', 'certificate') . ': ' . $certificate->printhours);
}
certificate_print_text($pdf, $x, $codey, 'C', $fontserif, '', 10, $code);
if ($certificate->printteacher) {
    $context = context_module::instance($cm->id);
    if ($teachers = get_users_by_capability($context, 'mod/certificate:printteacher', '', $sort = 'u.lastname ASC', '', '', '', '', false)) {
        $i = 0;
        foreach ($teachers as $teacher) {
            certificate_print_text($pdf, $sigx, $sigy + ($i * 12), 'L', $fontserif, '', 12, fullname($teacher));
            $i++;
        }
    }
}

// If we have printed trainers already, treat custom text as a custom text. Otherwise, print it in place of trainers list.
if ($certificate->printteacher) {
    certificate_print_text($pdf, $x, $ycond + 28, 'C', null, null, null, format_text($certificate->customtext));
} else {
    certificate_print_text($pdf, $custx, $custy, 'L', $fontserif, '', 12, format_text($certificate->customtext));
}
