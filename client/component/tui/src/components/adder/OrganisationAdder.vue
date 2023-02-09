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
import OrganisationFramework from 'totara_hierarchy/graphql/organisation_frameworks';
import OrganisationHierarchy from 'totara_hierarchy/graphql/organisations';

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

  render(h) {
    return h(HierarchicalAdder, {
      props: {
        existingItems: this.existingItems,
        open: this.open,
        customQuery: OrganisationHierarchy,
        customQueryKey: 'totara_hierarchy_organisations',
        customFrameworkQuery: OrganisationFramework,
        customFrameworkQueryKey: 'totara_hierarchy_organisation_frameworks',
        adderTitle:
          this.adderTitle || this.$str('select_organisation', 'totara_core'),
        filterTitle:
          this.filterTitle || this.$str('filter_organisation', 'totara_core'),
        showLoadingBtn: this.showLoadingBtn,
        tableHeaderName: this.$str('organisation_name', 'totara_core'),
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
    "select_organisation",
    "filter_organisation",
    "organisation_name"
  ]
}
</lang-strings>
