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
  <div class="tui-longTextAdminEdit">
    <PerformAdminCustomElementEdit
      :initial-values="initialValues"
      :settings="settings"
      @cancel="$emit('display')"
      @update="$emit('update', $event)"
    />
  </div>
</template>

<script>
import PerformAdminCustomElementEdit from 'mod_perform/components/element/PerformAdminCustomElementEdit';

export default {
  components: {
    PerformAdminCustomElementEdit,
  },

  inheritAttrs: false,

  props: {
    identifier: String,
    isRequired: Boolean,
    rawTitle: String,
    settings: Object,
  },

  data() {
    return {
      initialValues: {
        rawTitle: this.rawTitle,
        identifier: this.identifier,
        responseRequired: this.isRequired,
      },
    };
  },
};
</script>
