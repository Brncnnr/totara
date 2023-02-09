<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Gihan Hewaralalage <gihan.hewaralalge@totaralearning.com>
  @module mod_perform
-->
<template>
  <FormRow
    :label="$str('notification_subject_status', 'mod_perform')"
    :is-stacked="false"
    :required="required"
  >
    <FormCheckboxGroup
      name="recipients"
      :validations="v => [v.required()]"
      :disabled="disabled"
      :required="required"
    >
      <Checkbox value="subject">
        {{ $str('notification_participant_role_subject', 'mod_perform') }}
      </Checkbox>
      <Checkbox value="manager">
        {{ $str('notification_participant_role_manager', 'mod_perform') }}
      </Checkbox>
      <Checkbox value="managers_manager">
        {{
          $str('notification_participant_role_managers_manager', 'mod_perform')
        }}
      </Checkbox>
      <Checkbox value="appraiser">
        {{ $str('notification_participant_role_appraiser', 'mod_perform') }}
      </Checkbox>
      <Checkbox value="perform_peer">
        {{ $str('notification_participant_role_peer', 'mod_perform') }}
      </Checkbox>
      <Checkbox value="perform_mentor">
        {{ $str('notification_participant_role_mentor', 'mod_perform') }}
      </Checkbox>
      <Checkbox value="perform_reviewer">
        {{ $str('notification_participant_role_reviewer', 'mod_perform') }}
      </Checkbox>
      <Checkbox value="direct_report">
        {{ $str('notification_participant_role_direct_report', 'mod_perform') }}
      </Checkbox>
      <Checkbox value="perform_external">
        {{
          $str(
            'notification_participant_role_external_respondent',
            'mod_perform'
          )
        }}
      </Checkbox>
    </FormCheckboxGroup>
    <slot name="override-toggle" />
  </FormRow>
</template>

<script>
import { FormCheckboxGroup, FormRow } from 'tui/components/uniform';
import Checkbox from 'tui/components/form/Checkbox';

export default {
  components: {
    Checkbox,
    FormCheckboxGroup,
    FormRow,
  },

  props: {
    disabled: Boolean,
    required: Boolean,
  },
};
</script>
<lang-strings>
{
  "mod_perform": [
    "notification_participant_role_subject",
    "notification_participant_role_manager",
    "notification_participant_role_managers_manager",
    "notification_participant_role_appraiser",
    "notification_participant_role_peer",
    "notification_participant_role_mentor",
    "notification_participant_role_reviewer",
    "notification_participant_role_direct_report",
    "notification_participant_role_external_respondent",
    "notification_subject_status"
  ]
}
</lang-strings>
