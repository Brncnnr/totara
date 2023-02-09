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
  @module performelement_linked_review
-->

<template>
  <FormScope
    :path="[childElementResponsesIdentifier, element.id]"
    :process="childElementProcessor"
  >
    <slot />
  </FormScope>
</template>

<script>
import FormScope from 'tui/components/reform/FormScope';

export default {
  components: {
    FormScope,
  },
  props: {
    childElementResponsesIdentifier: String,
    element: Object,
  },
  methods: {
    /**
     * Stringifies the child element response and
     * appends the child_element_id to it.
     *
     * @param {null|Object} value
     * @return {Object}
     */
    childElementProcessor(value) {
      if (!value) {
        return {};
      }
      value.child_element_id = this.element.id;

      // stringify response
      value.response_data = JSON.stringify(value.response_data);

      return value;
    },
  },
};
</script>
