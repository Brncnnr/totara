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

  @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
  @deprecated since Totara 14.0
-->

<template>
  <div>
    <Loader :loading="$apollo.loading">
      <Filters
        :is-for-archived="filteringToArchived"
        :default-order="order"
        :default-filter-values="{
          proficient: proficientFilter,
          search: searchFilter,
        }"
        @filters-updated="filtersUpdated"
        @order-updated="orderUpdated"
      />
      <ListBody
        :archived="filteringToArchived"
        :competencies="competencies"
        :base-url="baseUrl"
        :user-id="userId"
        :is-mine="isMine"
      />
    </Loader>
  </div>
</template>

<script>
import Loader from 'tui/components/loading/Loader';
import CompetencyProgressQuery from 'totara_competency/graphql/competency_progress_for_user';
import Filters from 'totara_competency/components/profile/competency_list/Filters';
import ListBody from 'totara_competency/components/profile/competency_list/ListBody';
import { pick } from 'tui/util';

export default {
  components: {
    Loader,
    ListBody,
    Filters,
  },

  props: {
    filters: {
      required: false,
      type: Object,
      default: () => ({}),
    },
    userId: {
      required: true,
      type: Number,
    },
    baseUrl: {
      required: true,
      type: String,
    },
    isMine: {
      required: true,
      type: Boolean,
    },
  },

  data() {
    return {
      competencies: [],
      order: 'alphabetical',
      proficientFilter: null,
      searchFilter: '',
    };
  },

  computed: {
    selectedFilters() {
      // We gotta conditionally add proficient filter, since apparently it's not designed to be used for
      // archived assignments
      const extraFilters = {
        search: this.searchFilter,
        proficient: this.filteringToArchived ? null : this.proficientFilter,
      };

      return Object.assign(
        extraFilters,
        pick(this.filters, [
          'status',
          'type',
          'user_group_id',
          'user_group_type',
        ])
      );
    },
    /**
     * Are we currently filtering to archived.
     *
     * @return {boolean}
     */
    filteringToArchived() {
      return this.filters && this.filters.status === 2;
    },
    groupedCompetencyData() {
      return this.competencies.map(x => ({
        id: x.competency.id,
        rows: x.items.map(item =>
          Object.assign(
            {
              competency: x.competency,
              link: this.competencyDetailsLink(x),
            },
            item
          )
        ),
      }));
    },
  },

  apollo: {
    competencies: {
      query: CompetencyProgressQuery,
      variables() {
        if (this.filteringToArchived) {
          if (this.order === 'recently-assigned') {
            this.order = 'alphabetical';
          }
        } else {
          if (this.order === 'recently-archived') {
            this.order = 'alphabetical';
          }
        }

        return {
          user_id: this.userId,
          order: this.order,
          filters: this.selectedFilters,
        };
      },
      update({ totara_competency_profile_competency_progress: data }) {
        return data;
      },
    },
  },

  methods: {
    filtersUpdated({ search, proficient }) {
      this.searchFilter = search;
      this.proficientFilter = proficient;
    },
    orderUpdated(order) {
      this.order = order;
    },
  },
};
</script>
