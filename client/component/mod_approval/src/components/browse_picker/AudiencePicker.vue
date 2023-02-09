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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<script>
import PickerCommon from 'mod_approval/components/browse_picker/PickerCommon';
import cohorts from 'core/graphql/cohorts';

export default {
  components: {
    PickerCommon,
  },

  props: {
    value: [Number, String, Array],
    multiple: Boolean,
    disabledIds: Array,
    filterTitle: String,
    forceLoading: Boolean,
    tableHeaderName: String,
    /**
     * Pass the context id along if you want to show audiences
     * from a specific context or higher. If omitted it will
     * load all system audiences (if the current user has the capability
     * to see them)
     */
    contextId: {
      type: Number,
    },
  },

  methods: {
    async $_getItems(options) {
      const filters = options.filters || {};
      const result = await this.$apollo.query({
        query: cohorts,
        fetchPolicy: filters.search ? 'no-cache' : 'cache-first',
        variables: {
          query: {
            leaf_context_id:
              this.contextId != null ? String(this.contextId) : undefined,
            cursor: options.cursor,
            filters: {
              name: filters.search,
            },
          },
        },
      });

      return result.data.core_cohorts;
    },
  },

  render(h) {
    return h(PickerCommon, {
      props: {
        value: this.value,
        disabledIds: this.disabledIds,
        hierarchy: true,
        multiple: this.multiple,
        filterTitle: this.$str('filter_audiences', 'totara_core'),
        forceLoading: this.forceLoading,
        getItems: this.$_getItems,
        columns: [
          {
            id: 'name',
            label: this.$str('cohortname', 'totara_cohort'),
            size: 9,
          },
          {
            id: 'idnumber',
            label: this.$str('shortname', 'totara_cohort'),
            size: 3,
          },
        ],
      },
      on: {
        input: x => this.$emit('input', x),
      },
    });
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "filter_audiences"
  ],
  "totara_cohort": [
    "cohortname",
    "shortname"
  ]
}
</lang-strings>
