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
  <div class="tui-numericRatingScaleAdminSummary">
    <PerformAdminCustomElementSummary
      :extra-fields="extraFields"
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

  data() {
    const extraFields = [
      {
        title: this.$str(
          'low_value_label',
          'performelement_numeric_rating_scale'
        ),
        value: this.data.lowValue,
      },
      {
        title: this.$str(
          'high_value_label',
          'performelement_numeric_rating_scale'
        ),
        value: this.data.highValue,
      },
      {
        title: this.$str(
          'default_number_label',
          'performelement_numeric_rating_scale'
        ),
        value: this.data.defaultValue,
      },
    ];

    if (this.data.descriptionEnabled) {
      extraFields.push({
        title: this.$str('element_description', 'mod_perform'),
        value: this.data.descriptionHtml,
        htmlContent: true,
      });
    }

    return { extraFields };
  },
};
</script>

<lang-strings>
{
  "performelement_numeric_rating_scale": [
    "low_value_label",
    "high_value_label",
    "default_number_label"
  ],
  "mod_perform": [
    "element_description"
  ]
}
</lang-strings>
