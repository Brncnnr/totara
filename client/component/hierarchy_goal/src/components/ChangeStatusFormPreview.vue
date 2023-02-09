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

  @author Riana Rossouw <riana.rossouw@totaralearning.com>
  @module hierarchy_goal
-->

<template>
  <div
    v-show="settings.enable_status_change"
    class="tui-linkedReviewChangeStatusFormPreview"
  >
    <ChangeStatus v-if="settings.enable_status_change" :required="true">
      <template v-slot:form="{ titleId }">
        <div class="tui-linkedReviewChangeStatusFormPreview__form">
          <Form input-width="full" :vertical="true">
            <FormSelect
              :aria-labelledby="titleId"
              :options="scaleValueOptions"
              char-length="15"
              :value="null"
            />
          </Form>
        </div>
      </template>
    </ChangeStatus>
  </div>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormSelect from 'tui/components/form/Select';
import ChangeStatus from 'hierarchy_goal/components/ChangeStatus';

export default {
  components: {
    Form,
    FormSelect,
    ChangeStatus,
  },

  props: {
    settings: Object,
  },

  computed: {
    /**
     * Generate select list options with placeholder value
     *
     * @return {array}
     */
    scaleValueOptions() {
      let values = [
        {
          id: null,
          label: this.$str('goal_status_select', 'hierarchy_goal'),
        },
      ];

      return values;
    },
  },
};
</script>

<lang-strings>
{
  "hierarchy_goal": [
    "goal_status_select"
  ]
}
</lang-strings>
