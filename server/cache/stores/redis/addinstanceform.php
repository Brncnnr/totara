<?php
// This file is part of Moodle - http://moodle.org/
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
 * Redis Cache Store - Add instance form
 *
 * @package   cachestore_redis
 * @copyright 2013 Adam Durana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\redis\sentinel;
use core\redis\util;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cache/forms.php');

/**
 * Form for adding instance of Redis Cache Store.
 *
 * @copyright   2013 Adam Durana
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_redis_addinstance_form extends cachestore_addinstance_form {
    /**
     * Builds the form for creating an instance.
     */
    protected function configuration_definition() {
        global $OUTPUT;

        $form = $this->_form;

        if (sentinel::is_supported()) {
            $form->addElement('text', 'sentinelhosts', get_string('sentinelhosts', 'cachestore_redis'), array('size' => 100));
            $form->setType('sentinelhosts', PARAM_RAW);
            $form->addHelpButton('sentinelhosts', 'sentinelhosts', 'cachestore_redis');

            $form->addElement('text', 'sentinelmaster', get_string('sentinelmaster', 'cachestore_redis'));
            $form->setType('sentinelmaster', PARAM_RAW);
            $form->addHelpButton('sentinelmaster', 'sentinelmaster', 'cachestore_redis');

            $form->addElement('passwordunmask', 'sentinelpassword', get_string('sentinelpassword', 'cachestore_redis'));
            $form->setType('sentinelpassword', PARAM_RAW);
        } else {
            $form->addElement('hidden', 'sentinelhosts');
            $form->setType('sentinelhosts', PARAM_RAW);

            $form->addElement('hidden', 'sentinelmaster');
            $form->setType('sentinelmaster', PARAM_RAW);

            $form->addElement('hidden', 'sentinelpassword');
            $form->setType('sentinelpassword', PARAM_RAW);
        }

        $form->addElement('text', 'server', get_string('server', 'cachestore_redis'), array('size' => 24));
        $form->setType('server', PARAM_RAW);
        $form->addHelpButton('server', 'server', 'cachestore_redis');
        if (!sentinel::is_supported()) {
            $form->addRule('server', get_string('required'), 'required');
        }

        $form->addElement('passwordunmask', 'password', get_string('password', 'cachestore_redis'));
        $form->setType('password', PARAM_RAW);
        $form->addHelpButton('password', 'password', 'cachestore_redis');

        // Adding a configuration for a read replica
        $form->addElement('text', 'read_server', get_string('read_server', 'cachestore_redis'), array('size' => 24));
        $form->setType('read_server', PARAM_RAW);
        $form->addHelpButton('read_server', 'read_server', 'cachestore_redis');

        $form->addElement('passwordunmask', 'read_password', get_string('password', 'cachestore_redis'));
        $form->setType('read_password', PARAM_RAW);
        $form->addHelpButton('read_password', 'read_password', 'cachestore_redis');
        // End read replica configuration


        $form->addElement('text', 'database', get_string('database', 'cachestore_redis'));
        $form->setType('database', PARAM_INT);
        $form->setDefault('database', 0);
        $form->addHelpButton('database', 'database', 'cachestore_redis');

        $form->addElement('text', 'prefix', get_string('prefix', 'cachestore_redis'), array('size' => 16));
        $form->setType('prefix', PARAM_RAW); // We set to text but we have a rule to limit to alphanumext.
        $form->addHelpButton('prefix', 'prefix', 'cachestore_redis');
        $form->addRule('prefix', get_string('prefixinvalid', 'cachestore_redis'), 'regex', '#^[a-zA-Z0-9\-_]+$#');

        $form->addElement('static', 'serwarning', '', $OUTPUT->notification(markdown_to_html(get_string('useserializer_warning', 'cachestore_redis')), \core\output\notification::NOTIFY_WARNING));

        $serializeroptions = cachestore_redis::config_get_serializer_options();
        $form->addElement('select', 'serializer', get_string('useserializer', 'cachestore_redis'), $serializeroptions);
        $form->addHelpButton('serializer', 'useserializer', 'cachestore_redis');
        $form->setDefault('serializer', Redis::SERIALIZER_PHP);
        $form->setType('serializer', PARAM_INT);

        $compressoroptions = cachestore_redis::config_get_compressor_options();
        $form->addElement('select', 'compressor', get_string('usecompressor', 'cachestore_redis'), $compressoroptions);
        $form->addHelpButton('compressor', 'usecompressor', 'cachestore_redis');
        $form->setDefault('compressor', cachestore_redis::COMPRESSOR_NONE);
        $form->setType('compressor', PARAM_INT);
    }

    /**
     * Validates the add instance form data
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['database'] && $data['database'] < 0) {
            $errors['database'] = get_string('errordatabasenegative', 'cachestore_redis');
        }

        if (sentinel::is_supported()) {
            if ($data['sentinelhosts'] !== '') {
                $sentinels = explode(',', $data['sentinelhosts']);
                $parsed = util::parse_sentinel_hosts($data['sentinelhosts']);
                if (count($sentinels) !== count($parsed)) {
                    $errors['sentinelhosts'] = get_string('error');
                }
                if (trim($data['sentinelmaster']) === '') {
                    $errors['sentinelmaster'] = get_string('required');
                }
            }
        } else {
            if (trim($data['server']) === '') {
                $errors['server'] = get_string('required');
            }
        }

        return $errors;
    }
}
