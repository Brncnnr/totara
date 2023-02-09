<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module weka_simple_multi_lang
-->
<template>
  <Uniform
    :initial-values="formValue"
    input-width="full"
    class="tui-wekaSimpleMultiLangForm"
    @submit="submitForm"
  >
    <FormRow
      :label="$str('lang_code', 'weka_simple_multi_lang')"
      :required="true"
      :helpmsg="$str('lang_code_help', 'weka_simple_multi_lang')"
    >
      <template>
        <FormText
          :name="['lang', 'value']"
          :maxlength="5"
          :char-length="5"
          :validations="v => [v.required()]"
        />
      </template>
    </FormRow>

    <FormRow
      :label="$str('content', 'weka_simple_multi_lang')"
      :required="true"
      :helpmsg="$str('content_help', 'weka_simple_multi_lang')"
    >
      <template>
        <FormField
          :name="['content', 'value']"
          char-length="full"
          :validate="validateContentEditor"
        >
          <template v-slot="{ value, update }">
            <Weka
              :value="value"
              :compact="editorCompact"
              :extra-extensions="editorExtraExtensions"
              variant="simple"
              @input="update"
            />
          </template>
        </FormField>
      </template>
    </FormRow>

    <ButtonGroup class="tui-wekaSimpleMultiLangForm__buttonGroup">
      <template>
        <Button
          :styleclass="{ primary: true }"
          :text="$str('save', 'totara_core')"
          :aria-label="$str('save', 'totara_core')"
          type="submit"
        />
        <Cancel @click="$emit('cancel')" />
      </template>
    </ButtonGroup>
  </Uniform>
</template>

<script>
import Uniform from 'tui/components/uniform/Uniform';
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';
import FormRow from 'tui/components/form/FormRow';
import FormText from 'tui/components/uniform/FormText';
import FormField from 'tui/components/uniform/FormField';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import Cancel from 'tui/components/buttons/Cancel';

export default {
  components: {
    Uniform,
    FormRow,
    FormText,
    FormField,
    Weka,
    ButtonGroup,
    Cancel,
    Button,
  },

  props: {
    lang: {
      type: String,
      validator(lang) {
        return lang.length <= 5;
      },
    },
    /**
     * Weka content value, which is the json encoded string of the
     * list of paragaraph nodes only.
     */
    content: {
      type: Array,
      validator(nodes) {
        return nodes.every(
          node =>
            node.type && (node.type === 'paragraph' || node.type === 'heading')
        );
      },
      default() {
        return [];
      },
    },

    editorCompact: Boolean,
    editorExtraExtensions: {
      type: Array,
      default() {
        return [];
      },
      validator(extensions) {
        return extensions.every(extension => 'name' in extension);
      },
    },
  },

  data() {
    let wekaValue = null;
    if (this.content.length) {
      wekaValue = WekaValue.fromDoc({
        type: 'doc',
        content: this.content,
      });
    }

    return {
      formValue: {
        lang: {
          type: String,
          value: this.lang,
        },

        content: {
          type: WekaValue,
          value: wekaValue,
        },
      },
    };
  },

  methods: {
    submitForm(formValue) {
      const submitParameters = {
        lang: formValue.lang.value,
        content: [],
      };

      const { content } = formValue.content.value.getDoc();

      if (content && content.length) {
        submitParameters.content = content;
      }

      this.$emit('submit', submitParameters);
    },

    /**
     *
     * @param {WekaValue} content
     */
    validateContentEditor(content) {
      if (!content) {
        return this.$str('required', 'core');
      }

      return content.isEmpty ? this.$str('required', 'core') : '';
    },
  },
};
</script>

<lang-strings>
{
  "weka_simple_multi_lang": [
    "lang_code",
    "content",
    "content_help",
    "lang_code_help"
  ],
  "totara_core": [
    "save"
  ],
  "core": [
    "required"
  ]
}
</lang-strings>

<style lang="scss">
.tui-wekaSimpleMultiLangForm {
  &__buttonGroup {
    display: flex;
    justify-content: flex-end;
  }
}
</style>
