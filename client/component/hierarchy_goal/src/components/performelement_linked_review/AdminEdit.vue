<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Riana Rossouw <riana.rossouw@totaralearning.com>
  @module hierarchy_goal
-->

<template>
  <FormRowStack spacing="large">
    <FormRow>
      <FormCheckbox name="enable_status_change">
        {{ $str('enable_goal_status_change', 'hierarchy_goal') }}
      </FormCheckbox>
    </FormRow>

    <Field v-slot="{ value }" name="enable_status_change">
      <SelectParticipant
        v-if="value"
        field-name="status_change_relationship"
        :help-msg="$str('goal_change_status_help', 'hierarchy_goal')"
        :label="$str('enable_goal_status_change_participant', 'hierarchy_goal')"
        :relationships="relationships"
      />
    </Field>
  </FormRowStack>
</template>

<script>
import Field from 'tui/components/reform/Field';
import { FormCheckbox, FormRow } from 'tui/components/uniform';
import FormRowStack from 'tui/components/form/FormRowStack';
import SelectParticipant from 'performelement_linked_review/components/SelectParticipant';

export default {
  components: {
    Field,
    FormRowStack,
    FormCheckbox,
    FormRow,
    SelectParticipant,
  },

  inheritAttrs: false,

  props: {
    relationships: Array,
  },
};
</script>

<lang-strings>
{
  "hierarchy_goal": [
    "enable_goal_status_change",
    "enable_goal_status_change_participant",
    "goal_change_status_help"
  ]
}
</lang-strings>
