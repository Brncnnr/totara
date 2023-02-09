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
import OrganisationFrameworks from 'totara_hierarchy/graphql/organisation_frameworks';
import OrganisationHierarchy from 'totara_hierarchy/graphql/organisations';

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
        customQuery: OrganisationHierarchy,
        customQueryKey: 'totara_hierarchy_organisations',
        customFrameworkQuery: OrganisationFrameworks,
        customFrameworkQueryKey: 'totara_hierarchy_organisation_frameworks',
        filterTitle: this.$str('filter_organisation', 'totara_core'),
        forceLoading: this.forceLoading,
        tableHeaderName: this.$str('organisation_name', 'totara_core'),
      },
      on: this.$listeners,
    });
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "filter_organisation",
    "organisation_name"
  ]
}
</lang-strings>
