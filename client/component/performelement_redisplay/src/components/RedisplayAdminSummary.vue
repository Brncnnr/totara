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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @module performelement_redisplay
  -->

<template>
  <div class="tui-redisplayAdminSummary">
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
  props: {
    data: [Object, Array],
    identifier: String,
    isRequired: Boolean,
    settings: Object,
    title: String,
    type: Object,
    currentActivityId: Number,
  },
  data() {
    return {
      extraFields: [
        {
          title: this.$str('source_activity_value', 'performelement_redisplay'),
          value: this.data.activityName
            ? this.$str(
                'activity_name_with_status',
                'performelement_redisplay',
                {
                  activity_name: this.data.activityName,
                  activity_status: this.activityStatus,
                }
              )
            : this.$str('source_activity_missing', 'performelement_redisplay'),
        },
        {
          title: this.data.elementTitle
            ? this.$str(
                'source_question_element_value',
                'performelement_redisplay'
              )
            : '',
          value: this.data.elementTitle
            ? this.$str('source_element_option', 'performelement_redisplay', {
                element_title: this.data.elementTitle,
                element_plugin_name: this.data.elementPluginName,
              })
            : '',
        },
      ],
    };
  },
  calculated: {
    activityStatus() {
      return parseInt(this.data.activityId) === this.currentActivityId
        ? this.$str('current_activity', 'performelement_redisplay')
        : this.data.activityStatus;
    },
  },
};
</script>

<lang-strings>
  {
    "performelement_redisplay": [
      "activity_name_with_status",
      "current_activity",
      "source_activity_value",
      "source_activity_missing",
      "source_element_option",
      "source_question_element_value"
    ]
  }
</lang-strings>
