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
  @module performelement_numeric_rating_scale
-->

<template>
  <div class="tui-numericRatingScaleAdminView">
    <Form input-width="full" :vertical="true">
      <FormRow>
        <div class="tui-numericRatingScaleAdminView__input">
          <Range
            :char-length="30"
            :default-value="data.defaultValue"
            :disabled="true"
            :max="data.highValue"
            :min="data.lowValue"
            :show-labels="false"
            aria-hiden="true"
          />
          <div>
            <InputNumber
              name="response"
              :min="Number(data.lowValue)"
              :max="Number(data.highValue)"
              char-length="5"
            />
          </div>
        </div>
      </FormRow>
      <FormRow>
        <ElementDescription
          v-if="isDataExist"
          :aria-region-label="
            $str('scale_description', 'performelement_numeric_rating_scale')
          "
          :content-html="data.descriptionHtml"
        />
      </FormRow>
    </Form>
  </div>
</template>

<script>
import ElementDescription from 'mod_perform/components/element/participant_form/ElementDescription';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import Range from 'tui/components/form/Range';
import InputNumber from 'tui/components/form/InputNumber';

export default {
  components: {
    ElementDescription,
    Form,
    FormRow,
    InputNumber,
    Range,
  },
  props: {
    data: Object,
  },

  computed: {
    isDataExist() {
      return (
        this.data && this.data.descriptionEnabled && this.data.descriptionHtml
      );
    },
  },
};
</script>

<style lang="scss">
.tui-numericRatingScaleAdminView {
  &__input {
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;

    & > * + * {
      margin-left: var(--gap-4);
    }
  }
}
</style>

<lang-strings>
{
  "performelement_numeric_rating_scale": [
    "scale_description"
  ]
}
</lang-strings>
