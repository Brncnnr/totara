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
  @module container_workspace
-->
<template>
  <FormRow
    :label="$str('attendancestatus', 'mod_facetoface')"
    :is-stacked="false"
    :required="required"
  >
    <FormCheckboxGroup
      name="attendanceStatus"
      :validations="v => [v.required()]"
      :disabled="disabled"
      :required="required"
    >
      <Checkbox value="status_fully_attended">
        {{ $str('status_fully_attended', 'mod_facetoface') }}
      </Checkbox>
      <Checkbox value="status_partially_attended">
        {{ $str('status_partially_attended', 'mod_facetoface') }}
      </Checkbox>
      <Checkbox value="status_unable_to_attend">
        {{ $str('status_unable_to_attend', 'mod_facetoface') }}
      </Checkbox>
      <Checkbox value="status_no_show">
        {{ $str('status_no_show', 'mod_facetoface') }}
      </Checkbox>
      <Checkbox value="status_not_set">
        {{ $str('status_not_set', 'mod_facetoface') }}
      </Checkbox>
    </FormCheckboxGroup>
    <slot name="override-toggle" />
  </FormRow>
</template>
<script>
import { FormRow, FormCheckboxGroup } from 'tui/components/uniform';
import Checkbox from 'tui/components/form/Checkbox';

export default {
  components: {
    FormRow,
    FormCheckboxGroup,
    Checkbox,
  },

  props: {
    disabled: Boolean,
    required: Boolean,
  },
};
</script>
<lang-strings>
{
  "mod_facetoface": [
    "attendancestatus",
    "status_fully_attended",
    "status_partially_attended",
    "status_unable_to_attend",
    "status_no_show",
    "status_not_set"
  ]
}
</lang-strings>
