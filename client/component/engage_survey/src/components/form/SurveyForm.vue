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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module engage_survey
-->

<template>
  <Uniform
    class="tui-engageSurveyForm"
    :initial-values="initialValues"
    :vertical="true"
    input-width="full"
    @submit="submit"
    @change="change"
  >
    <div class="tui-engageSurveyForm__title">
      <FieldContextProvider>
        <FormText
          name="question"
          :validations="v => [v.required()]"
          :maxlength="75"
          :aria-label="$str('formtitle', 'engage_survey')"
          :placeholder="$str('formtitle', 'engage_survey')"
          :disabled="submitting"
        />
      </FieldContextProvider>
    </div>

    <FormRow :label="$str('formtypetitle', 'engage_survey')">
      <FormRadioGroup
        name="optionType"
        :validations="v => [v.required()]"
        :horizontal="true"
      >
        <Radio
          name="optionType"
          :value="singleChoice"
          :class="[
            `tui-engageSurveyForm__optionType`,
            `tui-engageSurveyForm__optionType--single`,
          ]"
        >
          {{ $str('optionsingle', 'engage_survey') }}
        </Radio>
        <Radio
          name="optionType"
          :value="multiChoice"
          :class="[
            `tui-engageSurveyForm__optionType`,
            `tui-engageSurveyForm__optionType--multiple`,
          ]"
        >
          {{ $str('optionmultiple', 'engage_survey') }}
        </Radio>
      </FormRadioGroup>
    </FormRow>

    <FormRow
      v-slot="{ labelId }"
      :label="$str('optionstitle', 'engage_survey')"
      class="tui-engageSurveyForm__answerTitle"
    >
      <FieldArray v-slot="{ items, push, remove }" path="options">
        <Repeater
          :rows="items"
          :min-rows="minOptions"
          :max-rows="maxOptions"
          :disabled="submitting"
          :delete-icon="true"
          :allow-deleting-first-items="false"
          class="tui-engageSurveyForm__repeater"
          :aria-labelledby="labelId"
          @add="push(newOption())"
          @remove="(item, i) => remove(i)"
        >
          <template v-slot="{ index }">
            <div class="tui-engageSurveyForm__optionInput">
              <FieldContextProvider>
                <FormText
                  :name="[index, 'text']"
                  :validations="v => [v.required()]"
                  :maxlength="80"
                  :aria-label="$str('option', 'engage_survey')"
                />
              </FieldContextProvider>
            </div>
          </template>
        </Repeater>
      </FieldArray>
    </FormRow>

    <ButtonGroup
      class="tui-engageSurveyForm__buttons"
      :class="{
        'tui-engageSurveyForm__buttons--right': showButtonRight,
        'tui-engageSurveyForm__buttons--left': showButtonLeft,
      }"
    >
      <LoadingButton
        class="tui-engageSurveyForm__button"
        type="submit"
        :loading="submitting"
        :primary="true"
        :disabled="disabled"
        :text="buttonText"
      />
      <CancelButton
        :disabled="submitting"
        class="tui-engageSurveyForm__cancelButton"
        @click="cancel"
      />
    </ButtonGroup>

    <UnsavedChangesWarning
      v-if="hasUnsavedChanges"
      :value="{ hasUnsavedChanges }"
    />
  </Uniform>
</template>

<script>
import {
  Uniform,
  FieldArray,
  FormRow,
  FormText,
  FormRadioGroup,
} from 'tui/components/uniform';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import CancelButton from 'tui/components/buttons/Cancel';
import FieldContextProvider from 'tui/components/reform/FieldContextProvider';
import LoadingButton from 'totara_engage/components/buttons/LoadingButton';
import Radio from 'tui/components/form/Radio';
import Repeater from 'tui/components/form/Repeater';
import UnsavedChangesWarning from 'totara_engage/components/form/UnsavedChangesWarning';
import { AnswerType } from 'totara_engage/index';

export default {
  components: {
    ButtonGroup,
    CancelButton,
    FieldArray,
    FieldContextProvider,
    FormRadioGroup,
    FormRow,
    FormText,
    LoadingButton,
    Radio,
    Repeater,
    Uniform,
    UnsavedChangesWarning,
  },

  props: {
    survey: {
      type: Object,
      default() {
        return {
          question: '',
          type: '',
          options: [],
          questionId: null,
        };
      },

      validator: prop =>
        'question' in prop && 'type' in prop && 'options' in prop,
    },

    submitting: {
      type: Boolean,
      default: false,
    },

    buttonContent: {
      type: String,
      default() {
        return this.$str('next', 'core');
      },
    },

    showButtonRight: {
      type: Boolean,
      default: true,
    },
    showButtonLeft: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    const minOptions = 2;

    const options = Array.isArray(this.survey.options)
      ? this.survey.options
      : [];
    while (options.length < minOptions) {
      options.push(this.newOption());
    }

    return {
      multiChoice: String(AnswerType.MULTI_CHOICE),
      singleChoice: String(AnswerType.SINGLE_CHOICE),
      minOptions,
      maxOptions: 10,
      disabled: true,

      initialValues: {
        question: this.survey.question,
        options,
        optionType: this.survey.type || String(AnswerType.MULTI_CHOICE),
      },
      hasUnsavedChanges: false,
    };
  },
  computed: {
    buttonText() {
      return this.buttonContent;
    },
  },
  methods: {
    /**
     * @returns {object}
     */
    newOption() {
      return { text: '', id: 0 };
    },

    submit(values) {
      const params = {
        options: values.options,
        question: values.question,
        type: values.optionType,

        // If it is for creation, then this should be null.
        questionId: this.survey.questionId,
      };
      this.hasUnsavedChanges = false;
      this.$emit('next', params);
    },

    change(values) {
      if (!this.submitting && !this.hasUnsavedChanges) {
        this.$emit('unsaved-changes');
        this.hasUnsavedChanges = true;
      }
      const { question, options } = values;
      this.disabled = true;
      if (question.length > 0) {
        const result = Array.prototype.slice
          .call(options, 0, 2)
          .filter(option => option.text !== '');

        if (result.length === 2) {
          this.disabled = false;
        }
      }
    },
    cancel() {
      this.hasUnsavedChanges = false;
      // Emit event on next tick so the unload handler can be removed first.
      this.$nextTick(() => {
        this.$emit('cancel');
      });
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "next"
    ],

    "engage_survey": [
      "formtitle",
      "formtypetitle",
      "optionstitle",
      "optionsingle",
      "optionmultiple",
      "option"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSurveyForm {
  display: flex;
  flex: 1;
  flex-direction: column;
  margin-top: var(--gap-8);

  &__title {
    display: flex;
    padding-bottom: var(--gap-4);
  }

  &__optionType {
    width: 200px;
  }

  &__options {
    margin-top: var(--gap-8);

    &__input {
      display: flex;
    }
  }

  &__repeater {
    .tui-repeater__row {
      .tui-engageSurveyForm__optionInput {
        width: 80%;
        .tui-formField {
          width: 100%;
          input[type='text'].tui-formInput {
            width: 100%;
            max-width: 100%;
          }
        }
      }
    }
  }

  &__buttons {
    flex-grow: 1;
    align-items: flex-end;
    margin-top: var(--gap-2);
    button.tui-engageSurveyForm__cancelButton {
      margin-bottom: 0;
    }
    &--left {
      justify-content: flex-start;
    }
    &--right {
      justify-content: flex-end;
    }
  }
}
</style>
