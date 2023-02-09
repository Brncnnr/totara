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

  @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
  @module mod_facetoface
-->
<template>
  <FormRow
    :label="
      $str('notification_include_ical_attachment_label', 'totara_notification')
    "
    :is-stacked="false"
  >
    <FormCheckboxGroup name="ical" :disabled="disabled">
      <FormCheckbox value="include_ical_attachment">
        {{ $str('enabled', 'totara_core') }}
      </FormCheckbox>
    </FormCheckboxGroup>
    <slot name="override-toggle" />
  </FormRow>
</template>

<script>
import { FormRow, FormCheckboxGroup } from 'tui/components/uniform';
import FormCheckbox from 'tui/components/form/Checkbox';

export default {
  components: {
    FormRow,
    FormCheckboxGroup,
    FormCheckbox,
  },

  props: {
    disabled: Boolean,
  },
};
</script>

<lang-strings>
{
  "totara_notification": [
    "notification_include_ical_attachment_label"
  ],
  "totara_core": [
    "enabled"
  ]
}
</lang-strings>
