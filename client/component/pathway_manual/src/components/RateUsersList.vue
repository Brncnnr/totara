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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module pathway_manual
-->

<template>
  <div class="tui-bulkManualRatingRateUsersList">
    <div class="tui-bulkManualRatingRateUsersList__search">
      <SearchFilter
        v-model="userFullName"
        drop-label
        :label="$str('search_people', 'pathway_manual')"
        :placeholder="$str('search_people', 'pathway_manual')"
      />
    </div>
    <Loader :loading="$apollo.loading">
      <h4>
        {{ $str('number_of_people', 'pathway_manual', users.length) }}
      </h4>
      <Table v-if="users.length > 0" :data="users" :expandable-rows="false">
        <template v-slot:header-row>
          <HeaderCell size="5">
            {{ $str('name', 'core') }}
          </HeaderCell>
          <HeaderCell size="3">
            {{ $str('competencies', 'totara_hierarchy') }}
          </HeaderCell>
          <HeaderCell size="4">
            <div class="tui-bulkManualRatingRateUsersList__flexRow">
              {{ $str('last_rated', 'pathway_manual') }}
              <span class="tui-bulkManualRatingRateUsersList__help">
                <LastRatingHelp />
              </span>
            </div>
          </HeaderCell>
        </template>
        <template v-slot:row="{ row }">
          <Cell size="5" valign="center">
            <Avatar
              :src="row.user.profileimageurl"
              :alt="row.user.profileimagealt"
              size="small"
              class="tui-bulkManualRatingRateUsersList__avatar"
            />
            <a :href="getRatingUrl(row.user.id)">{{ row.user.fullname }}</a>
          </Cell>
          <Cell
            size="3"
            :column-header="$str('competencies', 'totara_hierarchy')"
            valign="center"
          >
            {{ row.competency_count }}
          </Cell>
          <Cell
            size="4"
            :column-header="$str('last_rated', 'pathway_manual')"
            valign="center"
          >
            <LastRatingBlock
              :show-value="false"
              :latest-rating="row.latest_rating"
              :current-user-id="currentUserId"
            />
          </Cell>
        </template>
      </Table>
      <div v-else class="tui-bulkManualRatingRateUsersList__noUsers">
        {{ $str('filter_no_users', 'pathway_manual') }}
      </div>
    </Loader>
  </div>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import LastRatingBlock from 'pathway_manual/components/LastRatingBlock';
import LastRatingHelp from 'pathway_manual/components/LastRatingHelp';
import Loader from 'tui/components/loading/Loader';
import RateableUsersQuery from 'pathway_manual/graphql/rateable_users';
import SearchFilter from 'tui/components/filters/SearchFilter';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    Avatar,
    Cell,
    HeaderCell,
    LastRatingBlock,
    LastRatingHelp,
    Loader,
    SearchFilter,
    Table,
  },

  props: {
    role: {
      required: true,
      type: String,
    },
    currentUserId: {
      required: true,
      type: Number,
    },
  },

  data() {
    return {
      users: [],
      userFullName: '',
    };
  },

  methods: {
    /**
     * Get the URL for rating an individual user.
     * @param userId The user.
     * @returns {string}
     */
    getRatingUrl(userId) {
      return this.$url('/totara/competency/rate_competencies.php', {
        user_id: userId,
        role: this.role,
      });
    },
  },

  apollo: {
    users: {
      query: RateableUsersQuery,
      variables() {
        return {
          role: this.role,
          filters: {
            user_full_name: this.userFullName,
          },
        };
      },
      update({ pathway_manual_rateable_users: users }) {
        return users;
      },
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "name"
    ],
    "pathway_manual": [
      "filter_no_users",
      "last_rated",
      "number_of_people",
      "rate_user",
      "search_people"
    ],
    "totara_hierarchy": [
      "competencies"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-bulkManualRatingRateUsersList {
  display: block;

  &__avatar {
    margin-right: var(--gap-1);
  }

  &__flexRow {
    display: flex;
    flex-direction: row;
  }

  &__search {
    @media (min-width: $tui-screen-sm) {
      width: 50%;
    }
    margin-top: var(--gap-4);
    margin-bottom: var(--gap-4);
  }

  &__noUsers {
    @include tui-font-hint;
  }
}
</style>
