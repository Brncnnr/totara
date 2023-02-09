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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @module performelement_competency_rating
-->

<template>
  <div class="tui-competencyRatingAdminView">
    <Form input-width="full" :vertical="true">
      <FormRow>
        <RadioGroup v-model="tempVal" :aria-label="title" :char-length="50">
          <template v-for="(item, index) in options">
            <Radio :key="item.name" :name="item.name" :value="item.value">
              {{ item.value }}
            </Radio>
            <ElementDescription
              v-if="data.scaleDescriptionsEnabled"
              :key="'description' + index"
              class="tui-competencyRatingAdminView__description"
              :content-html="
                $str(
                  'scale_description_dummy_text',
                  'totara_competency',
                  index + 1
                )
              "
              initially-closed
            />
          </template>
        </RadioGroup>
      </FormRow>
    </Form>
  </div>
</template>

<script>
import ElementDescription from 'mod_perform/components/element/participant_form/ElementDescription';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';

export default {
  components: {
    ElementDescription,
    Form,
    FormRow,
    Radio,
    RadioGroup,
  },

  inheritAttrs: false,

  props: {
    title: String,
    data: Object,
  },

  data() {
    return {
      tempVal: false,

      /**
       * Sample selection options.
       *
       * @return {Array}
       */
      options: [
        {
          value: this.$str('rating_value', 'totara_competency', 1),
          name: '1',
        },
        {
          value: this.$str('rating_value', 'totara_competency', 2),
          name: '2',
        },
        {
          value: this.$str('rating_value', 'totara_competency', 3),
          name: '3',
        },
      ],
    };
  },
};
</script>

<style lang="scss">
.tui-competencyRatingAdminView {
  &__description {
    margin-top: var(--gap-2);
    margin-left: var(--gap-4);
  }
}
</style>

<lang-strings>
  {
    "totara_competency": [
      "scale_description_dummy_text",
      "rating_value"
    ]
  }
</lang-strings>
