<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @package mod_perform
-->

<template>
  <FieldArray v-slot="{ items, push, remove }" :path="name">
    <Repeater
      align="start"
      row-align="center"
      direction="vertical"
      :rows="items"
      :delete-icon="true"
      :allow-deleting-first-items="true"
      @add="push({ email: '', name: '' })"
      @remove="
        (item, i) => {
          remove(i);
          if (items.length === 0) {
            push({ email: '', name: '' });
          }
        }
      "
    >
      <template v-slot="{ row, index }">
        <InputSet split :stack-below="25" :char-length="30">
          <FormText
            :name="[index, 'name']"
            :aria-label="
              $str('external_user_name_help', 'mod_perform', index + 1)
            "
            :placeholder="$str('external_user_name', 'mod_perform')"
            :validations="v => [v.required(), v.maxLength(255)]"
          />
          <FormEmail
            :name="[index, 'email']"
            :aria-label="
              $str('external_user_email_help', 'mod_perform', index + 1)
            "
            :placeholder="$str('external_user_email', 'mod_perform')"
            :validations="v => [v.required(), v.maxLength(100), v.email()]"
          />
        </InputSet>
      </template>
    </Repeater>
  </FieldArray>
</template>

<script>
import FieldArray from 'tui/components/reform/FieldArray';
import InputSet from 'tui/components/form/InputSet';
import Repeater from 'tui/components/form/Repeater';
import { FormEmail, FormText } from 'tui/components/uniform';

export default {
  components: {
    FieldArray,
    FormEmail,
    FormText,
    InputSet,
    Repeater,
  },

  props: {
    name: {
      required: true,
      type: String,
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "external_user_email",
      "external_user_email_help",
      "external_user_name",
      "external_user_name_help"
    ]
  }
</lang-strings>
