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
 * mod_survey data generator.
 *
 * @package    mod_survey
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_survey\testing;

use coding_exception;

/**
 * mod_survey data generator class.
 *
 * @package    mod_survey
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class generator extends \core\testing\mod_generator {

    /**
     * Cached list of available templates.
     * @var array
     */
    private $templates = null;

    public function reset() {
        $this->templates = null;
        parent::reset();
    }

    public function create_instance($record = null, array $options = null) {
        global $DB;

        if ($this->templates === null) {
            $this->templates = $DB->get_records_menu('survey', array('template' => 0), 'name', 'id, name');
        }
        if (empty($this->templates)) {
            throw new coding_exception('cannotfindsurveytmpt', 'survey');
        }
        $record = (array)$record;
        if (isset($record['template']) && !is_number($record['template'])) {
            // Substitute template name with template id.
            $record['template'] = array_search($record['template'], $this->templates);
        }
        if (isset($record['template']) && !array_key_exists($record['template'], $this->templates)) {
            throw new coding_exception('cannotfindsurveytmpt', 'survey');
        }

        // Add default values for survey.
        if (!isset($record['template'])) {
            reset($this->templates);
            $record['template'] = key($this->templates);
        }

        return parent::create_instance($record, (array)$options);
    }
}
