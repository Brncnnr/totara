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
  @module performelement_static_content
-->

<template>
  <div class="tui-staticContentAdminSummary">
    <PerformAdminCustomElementSummary
      :html-content="data.content"
      :identifier="identifier"
      :is-required="isRequired"
      :settings="settings"
      :title="title"
      @display="$emit('display')"
    />
  </div>
</template>

<script>
import PerformAdminCustomElementSummary from 'mod_perform/components/element/PerformAdminCustomElementSummary';

export default {
  components: {
    PerformAdminCustomElementSummary,
  },

  inheritAttrs: false,

  props: {
    data: Object,
    identifier: String,
    isRequired: Boolean,
    settings: Object,
    title: String,
  },
};
</script>
