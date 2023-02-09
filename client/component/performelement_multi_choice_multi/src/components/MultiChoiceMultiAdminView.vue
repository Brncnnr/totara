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
  <div class="tui-multiChoiceMultiAdminView">
    <Form input-width="full" :vertical="true">
      <FormRow>
        <CheckboxGroup>
          <Checkbox
            v-for="item in data.options"
            :key="item.name"
            :name="item.name"
            value="item.value"
          >
            {{ item.value }}
          </Checkbox>
        </CheckboxGroup>
      </FormRow>
    </Form>
  </div>
</template>

<script>
import Checkbox from 'tui/components/form/Checkbox';
import CheckboxGroup from 'tui/components/form/CheckboxGroup';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';

export default {
  components: {
    CheckboxGroup,
    Checkbox,
    Form,
    FormRow,
  },

  inheritAttrs: false,

  props: {
    data: Object,
  },
};
</script>
