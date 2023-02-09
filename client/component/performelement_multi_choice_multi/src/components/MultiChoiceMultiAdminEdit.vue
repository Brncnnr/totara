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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module performelement_multi_choice_multi
-->

<template>
  <div class="tui-multiChoiceMultiAdminEdit">
    <PerformAdminCustomElementEdit
      v-if="ready"
      :initial-values="initialValues"
      :settings="settings"
      @cancel="$emit('display')"
      @change="updateValidationValues"
      @update="$emit('update', $event)"
    >
      <FormRow
        v-slot="{ labelId }"
        :label="
          $str('multi_select_options', 'performelement_multi_choice_multi')
        "
        :required="true"
      >
        <FieldArray v-slot="{ items, push, remove }" path="options">
          <Repeater
            :rows="items"
            :min-rows="minRows"
            :delete-icon="true"
            :allow-deleting-first-items="false"
            :aria-labelledby="labelId"
            @add="push()"
            @remove="(item, i) => remove(i)"
          >
            <template v-slot="{ index, lastRow }">
              <div class="tui-multiChoiceMultiAdminEdit__option">
                <FormText
                  :name="[index, 'value']"
                  :validations="v => [v.required()]"
                  :aria-label="
                    $str(
                      'answer_text',
                      'performelement_multi_choice_multi',
                      index + 1
                    )
                  "
                  :autofocus="lastRow"
                />
              </div>
            </template>
            <template v-slot:add>
              <ButtonIcon
                :aria-label="$str('add', 'core')"
                :styleclass="{ small: true }"
                class="tui-multiChoiceMultiAdminEdit__addOption"
                @click="push(createField())"
              >
                <AddIcon />
              </ButtonIcon>
            </template>
          </Repeater>
        </FieldArray>
      </FormRow>

      <FormRow
        v-slot="{ labelId }"
        :label="
          $str('response_restriction', 'performelement_multi_choice_multi')
        "
        :helpmsg="
          $str(
            'restriction_response_help_text',
            'performelement_multi_choice_multi'
          )
        "
      >
        <InputSet :aria-labelledby="labelId" :vertical="true">
          <InputSet char-length="full" :label-id="null">
            <FormNumber
              name="min"
              char-length="4"
              :aria-label="
                $str(
                  'restriction_minimum_label',
                  'performelement_multi_choice_multi'
                )
              "
              :validations="
                v =>
                  minRequired
                    ? [
                        v.min(1),
                        v.max(numberOfOptions),
                        v.max(maxOptions),
                        v.required(),
                      ]
                    : [v.min(1), v.max(numberOfOptions), v.max(maxOptions)]
              "
            />
            <InputSizedText>
              {{
                $str(
                  'restriction_minimum_label',
                  'performelement_multi_choice_multi'
                )
              }}
            </InputSizedText>
          </InputSet>

          <InputSet char-length="full" :label-id="null">
            <FormNumber
              name="max"
              char-length="4"
              :validations="v => [v.min(1), v.max(numberOfOptions)]"
              :aria-label="
                $str(
                  'restriction_maximum_label',
                  'performelement_multi_choice_multi'
                )
              "
            />
            <InputSizedText>
              {{
                $str(
                  'restriction_maximum_label',
                  'performelement_multi_choice_multi'
                )
              }}
            </InputSizedText>
          </InputSet>
        </InputSet>
      </FormRow>
    </PerformAdminCustomElementEdit>
  </div>
</template>

<script>
import AddIcon from 'tui/components/icons/Add';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import InputSet from 'tui/components/form/InputSet';
import InputSizedText from 'tui/components/form/InputSizedText';
import PerformAdminCustomElementEdit from 'mod_perform/components/element/PerformAdminCustomElementEdit';
import Repeater from 'tui/components/form/Repeater';
import {
  FormRow,
  FieldArray,
  FormNumber,
  FormText,
} from 'tui/components/uniform';

export default {
  components: {
    AddIcon,
    ButtonIcon,
    FieldArray,
    FormRow,
    FormNumber,
    FormText,
    Repeater,
    InputSet,
    InputSizedText,
    PerformAdminCustomElementEdit,
  },

  inheritAttrs: false,

  props: {
    data: Object,
    identifier: String,
    isRequired: Boolean,
    rawData: Object,
    rawTitle: String,
    settings: Object,
  },

  data() {
    return {
      initialValues: {
        identifier: this.identifier,
        max: this.rawData.max ? this.rawData.max : null,
        min: this.rawData.min ? this.rawData.min : null,
        options: [],
        rawTitle: this.rawTitle,
        responseRequired: this.isRequired,
      },
      numberOfOptions: null,
      maxOptions: null,
      minRows: 1,
      minRequired: this.isRequired,
      ready: false,
    };
  },

  mounted() {
    // If no existing data
    if (!this.rawData.options) {
      this.initialValues.options.push(this.createField());
    } else {
      this.numberOfOptions = this.rawData.options.length;
      this.maxOptions = this.rawData.max
        ? this.rawData.max
        : this.rawData.options.length;
      this.initialValues.options = this.rawData.options;
    }

    this.ready = 'true';
  },

  methods: {
    /**
     * Provide unique name for new repeater options
     *
     * @returns {Object}
     */
    createField() {
      const randomInt = Math.floor(Math.random() * Math.floor(10000000));
      return { name: 'option_' + randomInt, value: undefined };
    },

    /**
     * Provide validation values based on existing form inputs
     *
     * @param values {Object}
     */
    updateValidationValues(values) {
      // Don't require an explicit min selection when there is only one checkbox.
      // This way we can prevent displaying the somewhat nonsensical hint text in the participant form.
      if (values.options.length === 1) {
        this.minRequired = false;
      } else {
        this.minRequired = values.responseRequired;
      }

      this.numberOfOptions = values.options.length;
      this.maxOptions = values.max ? values.max : values.options.length;
    },
  },
};
</script>

<lang-strings>
  {
  "core": [
    "add"
  ],
  "performelement_multi_choice_multi": [
    "answer_text",
    "multi_select_options",
    "response_restriction",
    "restriction_minimum_label",
    "restriction_maximum_label",
    "restriction_response_help_text"
  ]
  }
</lang-strings>
