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
  @module performelement_competency_rating
-->
<template>
  <div class="tui-competencyRatingAdminSummary">
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
          'include_rating_scale_descriptions',
          'performelement_competency_rating'
        ),
        value: this.data.scaleDescriptionsEnabled
          ? this.$str('yes', 'core')
          : this.$str('no', 'core'),
      },
    ];

    return { extraFields };
  },
};
</script>

<lang-strings>
{
  "core": [
    "no",
    "yes"
  ],
  "performelement_competency_rating": [
    "include_rating_scale_descriptions"
  ]
}
</lang-strings>
