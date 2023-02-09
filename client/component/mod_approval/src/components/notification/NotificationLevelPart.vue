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

  @author Nathan Lewis <nathan.lewis@totaralearning.com>
  @module mod_approval
-->

<template>
  <ComponentLoading v-if="loading" />
  <FormRow
    v-else
    :label="$str('notification:level_base_approval_level', 'mod_approval')"
    required
  >
    <FormSelect
      :disabled="disabled"
      name="approval_level_id"
      :options="levels"
    />
  </FormRow>
</template>

<script>
import { FormRow, FormSelect } from 'tui/components/uniform';
import ComponentLoading from 'tui/components/loading/ComponentLoading';
import notificationLevelsQuery from 'mod_approval/graphql/approval_levels';

export default {
  components: {
    ComponentLoading,
    FormRow,
    FormSelect,
  },

  props: {
    disabled: Boolean,

    extendedContext: {
      type: Object,
      required: false,
    },
  },

  data() {
    return {
      loading: true,
      levels: [],
    };
  },

  async mounted() {
    const all_levels = [
      {
        id: null,
        label: this.$str('notification:level_base_all_levels', 'mod_approval'),
      },
    ];
    if (
      this.extendedContext &&
      this.extendedContext.component == 'mod_approval' &&
      this.extendedContext.area == 'workflow_stage'
    ) {
      const result = await this.$apollo.query({
        query: notificationLevelsQuery,
        variables: {
          input: {
            workflow_stage_id: this.extendedContext.itemId,
          },
        },
      });

      let stage_levels = result.data.mod_approval_workflow_stage.stage.approval_levels.map(
        function(level) {
          return {
            id: level.id,
            label: level.name,
          };
        }
      );

      this.levels = all_levels.concat(stage_levels);
    } else {
      this.levels = all_levels;
    }

    this.loading = false;
  },
};
</script>

<lang-strings>
  {
    "mod_approval": [
      "notification:level_base_all_levels",
      "notification:level_base_approval_level"
    ]
  }
</lang-strings>
