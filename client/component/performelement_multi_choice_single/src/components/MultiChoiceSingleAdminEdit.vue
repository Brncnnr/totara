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
  @module performelement_multi_choice_single
-->

<template>
  <div class="tui-multiChoiceSingleAdminEdit">
    <PerformAdminCustomElementEdit
      v-if="ready"
      :initial-values="initialValues"
      :settings="settings"
      @cancel="$emit('display')"
      @update="$emit('update', $event)"
    >
      <FormRow
        v-slot="{ labelId }"
        :label="
          $str('single_select_options', 'performelement_multi_choice_single')
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
              <div class="tui-multiChoiceSingleAdminEdit__option">
                <FormText
                  :name="[index, 'value']"
                  :validations="v => [v.required()]"
                  :aria-label="
                    $str(
                      'answer_text',
                      'performelement_multi_choice_single',
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
                class="tui-multiChoiceSingleAdminEdit__addOption"
                @click="push(createField())"
              >
                <AddIcon />
              </ButtonIcon>
            </template>
          </Repeater>
        </FieldArray>
      </FormRow>
    </PerformAdminCustomElementEdit>
  </div>
</template>

<script>
import AddIcon from 'tui/components/icons/Add';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import FormText from 'tui/components/uniform/FormText';
import PerformAdminCustomElementEdit from 'mod_perform/components/element/PerformAdminCustomElementEdit';
import Repeater from 'tui/components/form/Repeater';
import { FormRow, FieldArray } from 'tui/components/uniform';

export default {
  components: {
    AddIcon,
    ButtonIcon,
    FieldArray,
    FormRow,
    FormText,
    PerformAdminCustomElementEdit,
    Repeater,
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
        options: [],
        rawTitle: this.rawTitle,
        responseRequired: this.isRequired,
      },
      minRows: 2,
      ready: false,
    };
  },

  mounted() {
    // If no existing data
    if (!this.rawData.options) {
      this.initialValues.options.push(this.createField(), this.createField());
    } else {
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
  },
};
</script>

<lang-strings>
{
  "core": [
    "add"
  ],
  "performelement_multi_choice_single": [
    "answer_text",
    "single_select_options"
  ]
}
</lang-strings>
