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
  @module performelement_long_text
-->

<template>
  <div class="tui-longTextAdminView">
    <Form input-width="full" :vertical="true">
      <FormRow>
        <!--
          We pass a dummy file ID, so the file attachment options are shown.
          They can't be interacted with due to the pointer-events: none css
        -->
        <Weka
          :value="emptyValue"
          :usage-identifier="{
            component: 'performelement_long_text',
            area: 'response',
          }"
          variant="description"
          :file-item-id="1"
        />
      </FormRow>
    </Form>
  </div>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';

export default {
  components: {
    Form,
    FormRow,
    Weka,
  },

  inheritAttrs: false,

  data() {
    return {
      emptyValue: WekaValue.empty(),
    };
  },
};
</script>

<style lang="scss">
.tui-longTextAdminView {
  pointer-events: none;
}
</style>
