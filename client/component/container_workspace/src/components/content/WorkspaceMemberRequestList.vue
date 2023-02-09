<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<template>
  <div class="tui-workspaceMemberRequestList">
    <h3 class="tui-workspaceMemberRequestList__title">
      {{ $str('requests_to_join', 'container_workspace') }}
    </h3>

    <div class="tui-workspaceMemberRequestList__cards">
      <template v-if="memberRequests.items.length">
        <MemberRequestCard
          v-for="({ user, id, is_accepted, is_declined, request_content },
          index) in memberRequests.items"
          :key="index"
          :user-id="user.id"
          :user-fullname="user.fullname"
          :user-card-display="user.card_display"
          :member-request-id="id"
          :is-approved="is_accepted"
          :is-declined="is_declined"
          :request-content="request_content"
          class="tui-workspaceMemberRequestList__card"
          @accept-request="acceptRequest"
          @decline-request="declineRequest"
        />
      </template>
    </div>
  </div>
</template>

<script>
import MemberRequestCard from 'container_workspace/components/card/MemberRequestCard';
import apolloClient from 'tui/apollo_client';

// GraphQL Queries
import pendingMemberRequests from 'container_workspace/graphql/pending_member_requests';

export default {
  components: {
    MemberRequestCard,
  },

  apollo: {
    memberRequests: {
      query: pendingMemberRequests,
      variables() {
        return {
          workspace_id: this.workspaceId,
        };
      },
      update({ cursor, member_requests }) {
        return {
          items: member_requests,
          cursor: cursor,
        };
      },
    },
  },

  props: {
    workspaceId: {
      type: [Number, String],
      required: true,
    },
  },

  data() {
    return {
      memberRequests: {
        cursor: {
          total: 0,
          next: null,
        },
        items: [],
      },
    };
  },

  methods: {
    /**
     *
     * @param {Number} id
     */
    acceptRequest(id) {
      apolloClient.writeQuery({
        query: pendingMemberRequests,
        variables: {
          workspace_id: this.workspaceId,
        },
        data: {
          cursor: this.memberRequests.cursor,
          membe_requests: this.memberRequests.items.map(function(item) {
            if (item.id == id) {
              item = Object.assign({}, item);
              item.is_accepted = true;
            }

            return item;
          }),
        },
      });

      this.$emit('accept-member-request');
    },

    /**
     *
     * @param {Number} id
     */
    declineRequest(id) {
      apolloClient.writeQuery({
        query: pendingMemberRequests,
        variables: {
          workspace_id: this.workspaceId,
        },
        data: {
          cursor: this.memberRequests.cursor,
          membe_requests: this.memberRequests.items.map(function(item) {
            if (item.id == id) {
              item = Object.assign({}, item);
              item.is_declined = true;
            }

            return item;
          }),
        },
      });

      this.$emit('decline-member-request');
    },
  },
};
</script>

<lang-strings>
{
  "container_workspace": [
    "requests_to_join"
  ]
}
</lang-strings>

<style lang="scss">
.tui-workspaceMemberRequestList {
  &__title {
    @include tui-font-heading-x-small();
    margin: 0;
    padding: 0 var(--gap-4) var(--gap-4) var(--gap-4);
  }

  &__cards {
    display: flex;
    flex-direction: column;
  }

  &__card {
    border: var(--border-width-thin) solid var(--color-neutral-5);
    &:not(:last-child) {
      margin-bottom: var(--gap-2);
    }
  }
}
</style>
