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
  <div
    class="tui-editorTextarea"
    role="group"
    :aria-label="ariaLabel"
    :aria-labelledby="ariaLabelledby"
    :aria-describedby="ariaDescribedby"
    :aria-invalid="ariaInvalid"
  >
    <Select
      v-if="!lockFormat"
      :aria-label="$str('format', 'core')"
      :value="format"
      :options="formatOptions"
      @input="handleSelect"
    />
    <Textarea
      ref="textarea"
      :aria-label="$str('content', 'core')"
      :value="value && value.content"
      :disabled="disabled"
      class="tui-editorTextarea__textarea"
      char-length="full"
      rows="8"
      @input="handleInput"
    />
  </div>
</template>

<script>
import Select from 'tui/components/form/Select';
import Textarea from 'tui/components/form/Textarea';
import { Format } from 'tui/editor';

export default {
  components: {
    Textarea,
    Select,
  },

  props: {
    value: {
      required: true,
      type: Object,
      validator(value) {
        const keys = Object.keys(value);
        return ['content', 'format'].every(prop => keys.includes(prop));
      },
    },
    disabled: Boolean,
    ariaLabel: String,
    ariaLabelledby: String,
    ariaDescribedby: String,
    ariaInvalid: String,
    lockFormat: Boolean,
  },

  data() {
    return {
      formatOptions: [
        { id: Format.PLAIN, label: this.$str('formatplain', 'core') },
        { id: Format.HTML, label: this.$str('formathtml', 'core') },
        { id: Format.MARKDOWN, label: this.$str('formatmarkdown', 'core') },
      ],
    };
  },

  computed: {
    format() {
      return this.value.format || Format.PLAIN;
    },
  },

  mounted() {
    this.$emit('ready');
  },

  methods: {
    handleSelect(format) {
      this.$emit('input', { content: this.$refs.textarea.value, format });
    },

    handleInput(content) {
      this.$emit('input', { content, format: this.format });
    },
  },
};
</script>
<lang-strings>
  {
    "core": [
      "content",
      "format",
      "formatplain",
      "formatmarkdown",
      "formathtml"
    ]
  }
</lang-strings>
<style lang="scss">
.tui-editorTextarea {
  display: flex;
  flex-direction: column;
  // expand to full width if in horizontal flex
  width: 100%;

  // needed a more specific selector to override tui-select { flex-grow: 1; }
  > :first-child {
    flex-grow: 0;
  }

  &__textarea {
    // stretch child input to be full height if the editor is given a specific height
    flex-grow: 1;
    margin-top: var(--gap-1);
  }
}
</style>
