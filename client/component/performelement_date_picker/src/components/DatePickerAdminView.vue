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
  @module performelement_date_picker
-->

<template>
  <div class="tui-datePickerAdminView">
    <Form input-width="full" :vertical="true">
      <FormRow>
        <DateSelector
          :initial-current-date="false"
          :year-range-start="data.yearRangeStart"
          :year-range-end="data.yearRangeEnd"
        />
      </FormRow>
    </Form>
  </div>
</template>

<script>
import DateSelector from 'tui/components/form/DateSelector';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';

export default {
  components: {
    DateSelector,
    Form,
    FormRow,
  },

  inheritAttrs: false,

  props: {
    data: Object,
  },
};
</script>
