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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module performelement_linked_review
-->

<template>
  <FormRow :helpmsg="helpMsg" :label="label" :required="true">
    <div v-if="relationships.length < 1">
      {{ $str('no_participants', 'mod_perform') }}
    </div>
    <FormRadioGroup :name="fieldName" :validations="v => [v.required()]">
      <Radio
        v-for="relationship in relationships"
        :key="relationship.id"
        :value="relationship.id"
      >
        {{ relationship.name }}
      </Radio>
    </FormRadioGroup>
  </FormRow>
</template>

<script>
import { FormRadioGroup, FormRow } from 'tui/components/uniform';
import Radio from 'tui/components/form/Radio';

export default {
  components: {
    FormRadioGroup,
    FormRow,
    Radio,
  },

  props: {
    fieldName: {
      type: String,
      required: true,
    },
    helpMsg: String,
    label: {
      type: String,
      required: true,
    },
    relationships: Array,
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "no_participants"
    ]
  }
</lang-strings>
