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

/**
 * Wrapper around PickerCommon, fetching from the standard hierarchy queries
 * (as also used by the adder).
 */
export default {
  components: {
    PickerCommon,
  },

  props: {
    value: [Number, String, Array],
    multiple: Boolean,
    disabledIds: Array,
    customQuery: Object,
    customQueryKey: String,
    customFrameworkQuery: Object,
    customFrameworkQueryKey: String,
    filterTitle: String,
    forceLoading: Boolean,
    tableHeaderName: String,
  },

  methods: {
    async $_getFrameworks() {
      const result = await this.$apollo.query({
        query: this.customFrameworkQuery,
      });
      return result.data[this.customFrameworkQueryKey];
    },

    async $_getItems(options) {
      const filters = options.filters || {};
      const result = await this.$apollo.query({
        query: this.customQuery,
        fetchPolicy: filters.search ? 'no-cache' : 'cache-first',
        variables: {
          query: {
            cursor: options.cursor,
            filters: {
              framework_id: filters.frameworkId,
              name: filters.search,
              parent_id: options.parentId == -1 ? 0 : options.parentId,
              ids: options.ids,
            },
          },
        },
      });

      return result.data[this.customQueryKey];
    },
  },

  render(h) {
    return h(PickerCommon, {
      props: {
        value: this.value,
        disabledIds: this.disabledIds,
        filterFrameworks: true,
        hierarchy: true,
        multiple: this.multiple,
        filterTitle: this.filterTitle,
        forceLoading: this.forceLoading,
        getFrameworks: this.$_getFrameworks,
        getItems: this.$_getItems,
        columns: [{ id: 'fullname', label: this.tableHeaderName, size: 11 }],
      },
      on: {
        input: x => this.$emit('input', x),
      },
    });
  },
};
</script>
