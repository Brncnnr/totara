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
  @module performelement_custom_rating_scale
-->

<template>
  <div class="tui-customRatingScaleAdminView">
    <Form input-width="full" :vertical="true">
      <FormRow>
        <RadioGroup v-model="tempVal" :aria-label="title" :char-length="50">
          <template v-for="(item, index) in data.options">
            <Radio :key="index" :name="item.name" :value="item.value">
              {{
                $str('answer_output', 'performelement_custom_rating_scale', {
                  label: item.value.text,
                  count: item.value.score,
                })
              }}
            </Radio>
            <ElementDescription
              v-if="item.descriptionEnabled"
              :key="'description' + index"
              class="tui-customRatingScaleAdminView__description"
              :content-html="item.descriptionHtml"
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
    data: Object,
    title: String,
  },

  data() {
    return {
      tempVal: false,
    };
  },
};
</script>

<style lang="scss">
.tui-customRatingScaleAdminView {
  &__description {
    margin-top: var(--gap-2);
    margin-left: var(--gap-4);
  }
}
</style>

<lang-strings>
{
  "performelement_custom_rating_scale": [
    "answer_output"
  ]
}
</lang-strings>
