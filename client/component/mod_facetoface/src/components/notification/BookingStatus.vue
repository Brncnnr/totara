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

  @author Brian Barnes <brian.barnes@totaralearning.com>
  @module mod_facetoface
-->
<template>
  <FormRow
    :label="$str('notification_subject_status', 'mod_facetoface')"
    :is-stacked="false"
    :required="required"
  >
    <FormCheckboxGroup
      name="recipients"
      :validations="v => [v.required()]"
      :disabled="disabled"
      :required="required"
    >
      <Checkbox value="status_waitlisted">
        {{ $str('status_waitlisted', 'mod_facetoface') }}
      </Checkbox>
      <Checkbox value="status_user_cancelled">
        {{ $str('status_user_cancelled', 'mod_facetoface') }}
      </Checkbox>
      <Checkbox value="status_pending_requests">
        {{ $str('status_pending_requests', 'mod_facetoface') }}
      </Checkbox>
      <Checkbox value="status_booked">
        {{ $str('status_booked', 'mod_facetoface') }}
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
  },
};
</script>
<lang-strings>
{
  "mod_facetoface": [
    "notification_subject_status",
    "status_booked",
    "status_pending_requests",
    "status_user_cancelled",
    "status_waitlisted"
  ]
}
</lang-strings>
