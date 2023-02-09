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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_playlist
-->
<template>
  <Form
    class="tui-playlistTitleForm"
    :vertical="true"
    input-width="full"
    @submit.prevent="submit"
  >
    <FormRow
      v-slot="{ id, label }"
      :label="$str('playlisttitle', 'totara_playlist')"
      :hidden="true"
    >
      <InputText
        :id="id"
        v-model="innerTitle"
        :placeholder="label"
        :aria-label="label"
        :maxlength="75"
        :autofocus="focusInput"
        name="playlist title"
        @keydown.native.esc="$emit('cancel', $event)"
      />
    </FormRow>

    <DoneCancelGroup
      :loading="submitting"
      :disabled="0 >= innerTitle.length"
      @done.prevent="submit"
      @cancel.prevent="$emit('cancel', $event)"
    />
  </Form>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import DoneCancelGroup from 'totara_engage/components/buttons/DoneCancelGroup';

export default {
  components: {
    DoneCancelGroup,
    Form,
    FormRow,
    InputText,
  },

  props: {
    submitting: Boolean,
    title: {
      type: String,
      default: '',
    },
    /**
     * A flag to tell whether this form should focus on the input or not.
     */
    focusInput: Boolean,
  },

  data() {
    return {
      innerTitle: this.title,
    };
  },

  watch: {
    /**
     *
     * @param {String} value
     */
    title(value) {
      this.innerTitle = value;
    },
  },

  methods: {
    submit() {
      this.$emit('submit', { title: this.innerTitle });
    },
  },
};
</script>

<lang-strings>
  {
    "totara_playlist": [
      "playlisttitle"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-playlistTitleForm {
  @include tui-font-body();
  display: flex;
  flex-direction: column;
  width: 100%;

  // Override form row
  .tui-formRow {
    &__desc {
      display: none;
    }

    &__action {
      margin: 0;
    }
  }
}
</style>
