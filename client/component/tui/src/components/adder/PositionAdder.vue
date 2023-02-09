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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module tui
-->

<script>
import HierarchicalAdder from 'tui/components/adder/HierarchicalAdder';

// Gql
import PositionFramework from 'totara_hierarchy/graphql/position_frameworks';
import PositionsHierarchy from 'totara_hierarchy/graphql/positions';

export default {
  components: {
    HierarchicalAdder,
  },

  props: {
    existingItems: {
      type: Array,
      default: () => [],
    },
    open: Boolean,
    adderTitle: String,
    filterTitle: String,
    showLoadingBtn: Boolean,
  },

  computed: {
    getAdderTitle() {
      return this.$str('select_organisation', 'totara_core');
    },
  },

  render(h) {
    return h(HierarchicalAdder, {
      props: {
        existingItems: this.existingItems,
        open: this.open,
        customQuery: PositionsHierarchy,
        customQueryKey: 'totara_hierarchy_positions',
        customFrameworkQuery: PositionFramework,
        customFrameworkQueryKey: 'totara_hierarchy_position_frameworks',
        adderTitle:
          this.adderTitle || this.$str('select_position', 'totara_core'),
        filterTitle:
          this.filterTitle || this.$str('filter_position', 'totara_core'),
        showLoadingBtn: this.showLoadingBtn,
        tableHeaderName: this.$str('position_name', 'totara_core'),
      },
      on: this.$listeners,
      scopedSlots: this.$scopedSlots,
    });
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "select_position",
    "filter_position",
    "position_name"
  ]
}
</lang-strings>
