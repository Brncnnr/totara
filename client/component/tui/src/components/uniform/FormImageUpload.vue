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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <FormField
    v-slot="{ attrs, value, update, touch }"
    :name="name"
    :validate="validate"
    :validations="validations"
  >
    <ImageUpload
      v-bind="attrs"
      :href="href"
      :item-id="itemId"
      :repository-id="repositoryId"
      :type="type"
      :current-url="value ? value.url : null"
      :default-url="defaultUrl"
      :accepted-types="acceptedTypes"
      @update="x => update(formatUpdate(x)) && touch()"
    />
  </FormField>
</template>

<script>
import { FormField } from 'tui/components/uniform';
import ImageUpload from 'tui/components/form/ImageUpload';

export default {
  components: {
    FormField,
    ImageUpload,
  },

  props: {
    name: String,
    validate: Function,
    validations: [Function, Array],

    href: {
      type: String,
      required: true,
    },
    itemId: {
      type: Number,
      required: true,
    },
    repositoryId: {
      type: Number,
      required: true,
    },
    type: Object,
    defaultUrl: String,
    acceptedTypes: Array,
  },

  methods: {
    formatUpdate(e) {
      return e ? { url: e.url } : null;
    },
  },
};
</script>
