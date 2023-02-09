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
  <div class="tui-customRatingScaleAdminSummary">
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
    type: Object,
  },

  data() {
    const options = this.data.options.map(option => {
      return {
        value: this.$str(
          'answer_output',
          'performelement_custom_rating_scale',
          {
            count: option.value.score,
            label: option.value.text,
          }
        ),
        descriptionEnabled: option.descriptionEnabled,
        descriptionHtml: option.descriptionHtml,
      };
    });

    return {
      extraFields: [
        {
          title: this.$str(
            'custom_rating_options',
            'performelement_custom_rating_scale'
          ),
          options,
        },
      ],
    };
  },
};
</script>

<lang-strings>
  {
    "performelement_custom_rating_scale": [
      "answer_output",
      "custom_rating_options"
    ]
  }
</lang-strings>
