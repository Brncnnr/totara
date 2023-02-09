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
import PickerStandardHierarchy from 'mod_approval/components/browse_picker/PickerStandardHierarchy';
import PositionFrameworks from 'totara_hierarchy/graphql/position_frameworks';
import PositionsHierarchy from 'totara_hierarchy/graphql/positions';

export default {
  components: {
    PickerStandardHierarchy,
  },

  props: {
    value: [Number, String, Array],
    disabledIds: Array,
    forceLoading: Boolean,
  },

  render(h) {
    return h(PickerStandardHierarchy, {
      props: {
        value: this.value,
        disabledIds: this.disabledIds,
        customQuery: PositionsHierarchy,
        customQueryKey: 'totara_hierarchy_positions',
        customFrameworkQuery: PositionFrameworks,
        customFrameworkQueryKey: 'totara_hierarchy_position_frameworks',
        filterTitle: this.$str('filter_position', 'totara_core'),
        forceLoading: this.forceLoading,
        tableHeaderName: this.$str('position_name', 'totara_core'),
      },
      on: this.$listeners,
    });
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "filter_position",
    "position_name"
  ]
}
</lang-strings>
