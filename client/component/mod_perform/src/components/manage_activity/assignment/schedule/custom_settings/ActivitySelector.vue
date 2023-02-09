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

  @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
  @module mod_perform
-->
<template>
  <FormScope path="dynamicCustomSettings" :validate="activityValidator">
    <FormRow :label="$str('schedule_range_activity', 'mod_perform')">
      <Loader :loading="$apollo.loading">
        <FormSelect
          :id="$id('relative-date-reference-date')"
          char-length="25"
          :options="activityOptions"
          name="activity"
        />
      </Loader>
    </FormRow>
  </FormScope>
</template>

<script>
import Loader from 'tui/components/loading/Loader';
import { FormRow, FormSelect, FormScope } from 'tui/components/uniform';
// graphQL
import performActivitiesQuery from 'mod_perform/graphql/activities';

export default {
  components: {
    FormRow,
    FormSelect,
    FormScope,
    Loader,
  },

  props: {
    data: {
      type: Object,
      required: true,
    },
    configData: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      activities: [],
      visible: true,
    };
  },

  computed: {
    activityOptions() {
      let defaultOption = {
        id: null,
        label: this.$str(
          'schedule_dynamic_another_activity_select',
          'mod_perform'
        ),
      };
      let options = this.activities
        .filter(item => item.id != this.configData.activityId)
        .map(item => {
          return {
            id: item.id,
            label: item.name,
          };
        });

      options.unshift(defaultOption);

      return options;
    },
  },

  apollo: {
    activities: {
      query: performActivitiesQuery,
      update: data => data.mod_perform_activities,
    },
  },

  beforeDestroy() {
    this.visible = false;
  },

  methods: {
    activityValidator(v) {
      const activityIsRequired = this.$str(
        'schedule_dynamic_activity_required',
        'mod_perform'
      );
      const errors = {};

      if (this.visible && !v.activity) {
        errors.activity = activityIsRequired;
      }

      return errors;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "schedule_dynamic_activity_required",
      "schedule_dynamic_another_activity_select",
      "schedule_range_activity"
    ]
  }
</lang-strings>
