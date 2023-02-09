<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2023 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totara.com>
 * @package tool_smtp_test
 */

$string['infomessage'] = 'This tool sends an email using Totara\'s configured SMTP settings, and provides debugging information.';
$string['label_message'] = 'Email message';
$string['label_subject'] = 'Email subject';
$string['label_to'] = 'Send to address';
$string['pluginname'] = 'Outgoing email test';
$string['smtp_test'] = 'Test outgoing email settings';
$string['test_failure'] = 'Message failed to send, see debugging messages above.';
$string['test_message'] = 'Hello,

This is a test email from {$a->sitename}.

If you received this message in error, please contact {$a->email}.

Kind regards,{$a->admin}';
$string['test_subject'] = 'Test email from {$a->sitename}';
$string['test_success'] = 'Message sent.';
