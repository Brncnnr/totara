<?php
/**
 * This file is part of Totara Learn
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\controllers\helpers;

use core\entity\user;
use core\notification;
use moodle_url;
use totara_evidence\controllers\item;
use totara_evidence\customfield_area\field_helper;
use totara_evidence\entity\evidence_type_field;
use totara_evidence\forms\edit_evidence;
use totara_evidence\forms\view_field;
use totara_evidence\models\evidence_item;
use totara_evidence\models\evidence_type;

/**
 * Loads additional data required for a custom fields form
 *
 * @package totara_evidence\controllers\helpers
 */
class customfield_form_helper {

    public static function get_create_form(
        evidence_type $type,
        user $user,
        moodle_url $submit_url,
        moodle_url $cancel_url
    ): edit_evidence {
        $customfield_data = field_helper::load_field_data([
            'id' => 0,
            'typeid' => $type->get_id(),
            'user_id' => $user->id,
            'name' => null,
            'submit_url' => $submit_url->out_as_local_url(),
            'cancel_url' => $cancel_url->out_as_local_url(),
        ]);
        $form = new edit_evidence(null, [
            'id'   => 0,
            'item' => $customfield_data
        ]);
        $form->set_data($customfield_data);

        if ($submitted_data = $form->get_data()) {
            $item = evidence_item::create($type, $user, $submitted_data, $submitted_data->name);

            notification::add(
                get_string('notification_item_created', 'totara_evidence', $item->get_display_name()),
                notification::SUCCESS
            );
            redirect(item::get_return_url($item, 'submit_url'));
        } else if ($form->is_cancelled()) {
            redirect(new moodle_url(required_param('cancel_url', PARAM_URL)));
        }

        return $form;
    }

    public static function get_edit_form(
        evidence_item $item,
        moodle_url $submit_url = null,
        moodle_url $cancel_url = null
    ): edit_evidence {
        $submit_url = $submit_url ?? item::get_return_url($item, 'return_to');
        $cancel_url = $cancel_url ?? item::get_return_url($item, 'return_to');

        $customfield_data = field_helper::load_field_data([
            'id' => $item->id,
            'typeid' => $item->typeid,
            'user_id' => $item->user_id,
            'name' => $item->name,
            'submit_url' => $submit_url->out_as_local_url(),
            'cancel_url' => $cancel_url->out_as_local_url(),
        ]);
        $form = new edit_evidence(null, [
            'id'   => $item->get_id(),
            'item' => $customfield_data
        ]);
        $form->set_data($customfield_data);

        if ($submitted_data = $form->get_data()) {
            $item->update($submitted_data, $submitted_data->name);

            notification::add(
                get_string('notification_item_updated', 'totara_evidence', $item->get_display_name()),
                notification::SUCCESS
            );
            redirect(item::get_return_url($item, 'submit_url'));
        } else if ($form->is_cancelled()) {
            redirect(item::get_return_url($item, 'cancel_url'));
        }

        return $form;
    }

    public static function get_view_field_form(evidence_type_field $field_entity): view_field {
        global $TEXTAREA_OPTIONS;

        // Get the description text for this custom field
        /** @var evidence_type_field $field */
        $field = (object) $field_entity->to_array();
        $field->descriptionformat = FORMAT_HTML;
        $field = file_prepare_standard_editor(
            $field,
            'description',
            $TEXTAREA_OPTIONS,
            $TEXTAREA_OPTIONS['context'],
            'totara_customfield',
            'textarea',
            $field->id
        );

        // Get the placeholder text for this text area custom field
        if ($field->datatype == 'textarea') {
            $field->defaultdataformat = FORMAT_HTML;
            $field = file_prepare_standard_editor(
                $field,
                'defaultdata',
                $TEXTAREA_OPTIONS,
                $TEXTAREA_OPTIONS['context'],
                'totara_customfield',
                'textarea',
                $field->id
            );
        }

        $form = new view_field(null, [
            'field' => $field
        ]);

        return $form;
    }

}
