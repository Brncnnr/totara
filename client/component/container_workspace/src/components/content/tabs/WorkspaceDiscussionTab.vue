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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @author Riana Rossouw <riana.rossouw@totaralearning.com>
  @module container_workspace
-->

<template>
  <div class="tui-workspaceDiscussionTab">
    <template v-if="canPostDiscussion">
      <PostDiscussionForm
        :avatar-image-alt="user.profileimagealt"
        :avatar-image-src="user.profileimageurl"
        :submitting="submitting"
        :workspace-context-id="workspaceContextId"
        :has-error="hasError"
        :avatar-image-url="$url('/user/profile.php', { id: user.id })"
        @submit="submit"
      />

      <Separator :normal="true" />
    </template>

    <DiscussionFilter
      v-if="workspaceTotalDiscussions"
      :sort="sort"
      :search-term="searchTerm"
      :workspace-id="workspaceId"
      class="tui-workspaceDiscussionTab__filter"
      @update-search-term="searchTerm = $event"
      @update-sort="sort = $event"
    />

    <div
      v-if="!$apollo.loading && search.cursor.total === 0 && hasSearch"
      class="tui-workspaceDiscussionTab__message"
    >
      {{ $str('no_discussion_result', 'container_workspace') }}
    </div>

    <div v-if="hasSearch">
      <VirtualScroll
        data-key="id"
        :data-list="search.results"
        :aria-label="$str('search_contents', 'container_workspace')"
        :is-loading="$apollo.loading"
        :page-mode="true"
        class="tui-workspaceDiscussionTab__resultList"
        @scrollbottom="onScrollToBottom"
      >
        <template v-slot:item="{ item, posInSet, setSize }">
          <DiscussionContentResultCard
            :id="item.id"
            :creator-fullname="item.owner.fullname"
            :creator-image-alt="
              item.owner.profileimagealt || item.owner.fullname
            "
            :creator-image-src="item.owner.profileimageurl"
            :creator-id="item.owner.id"
            :discussion-content="item.content"
            :time-description="item.time_description"
            :discussion-id="item.discussion_id"
            :aria-labelledby="$id(`item-${item.id}`)"
            :label-id="$id(`item-${item.id}`)"
            :workspace-context-id="item.workspace_context_id"
            class="tui-workspaceDiscussionTab__resultCard"
          />
        </template>
        <template v-slot:footer>
          <PageLoader :fullpage="false" :loading="$apollo.loading" />
        </template>
      </VirtualScroll>

      <div
        v-if="loadMoreSearchResultsVisibility"
        class="tui-workspaceDiscussionTab__loadMoreContainer"
      >
        <div class="tui-workspaceDiscussionTab__viewedDiscussions">
          {{
            $str('vieweditems', 'container_workspace', search.results.length)
          }}
          {{
            $str(
              'total_search_results',
              'container_workspace',
              search.cursor.total
            )
          }}
        </div>
        <Button
          class="tui-workspaceDiscussionTab__loadMore"
          :text="$str('loadmore', 'container_workspace')"
          @click="loadMoreSearchResults"
        />
      </div>
    </div>

    <div v-else>
      <!-- Using the discussion's id so that we can make sure the state is being reset after ward. -->
      <VirtualScroll
        data-key="id"
        :data-list="page.discussions"
        :aria-label="$str('discussions_list', 'container_workspace')"
        :is-loading="$apollo.loading"
        :page-mode="true"
        @scrollbottom="onScrollToBottom"
      >
        <template v-slot:item="{ item, posInSet, setSize }">
          <DiscussionCard
            :creator-fullname="item.owner.fullname"
            :creator-image-alt="
              item.owner.profileimagealt || item.owner.fullname
            "
            :creator-image-src="item.owner.profileimageurl"
            :creator-id="item.owner.id"
            :discussion-content="item.content"
            :time-description="item.time_description"
            :total-comments="item.total_comments"
            :total-reactions="item.total_reactions"
            :discussion-id="item.id"
            :first-comment-cursor="item.comment_cursor"
            :reacted="item.discussion_interactor.reacted"
            :update-able="item.discussion_interactor.can_update"
            :delete-able="item.discussion_interactor.can_delete"
            :comment-able="item.discussion_interactor.can_comment"
            :react-able="item.discussion_interactor.can_react"
            :report-able="item.discussion_interactor.can_report"
            :removed="item.discussion_interactor.removed"
            :edited="item.edited"
            :aria-posinset="posInSet"
            :aria-setsize="setSize"
            :aria-labelledby="$id(`item-${item.id}`)"
            :label-id="$id(`item-${item.id}`)"
            :workspace-context-id="item.workspace_context_id"
            class="tui-workspaceDiscussionTab__card"
            @update-discussion-react-status="updateDiscussionReactStatus"
            @update-discussion="updateDiscussion"
            @add-new-comment="addNewComment"
            @delete-discussion="deleteDiscussion"
          />
        </template>
        <template v-slot:footer>
          <PageLoader :fullpage="false" :loading="$apollo.loading" />
        </template>
      </VirtualScroll>

      <div
        v-if="loadMoreDiscussionsVisibility"
        class="tui-workspaceDiscussionTab__loadMoreContainer"
      >
        <div class="tui-workspaceDiscussionTab__viewedDiscussions">
          {{
            $str('vieweditems', 'container_workspace', page.discussions.length)
          }}
          {{
            $str('total_discussions', 'container_workspace', page.cursor.total)
          }}
        </div>
        <Button
          class="tui-workspaceDiscussionTab__loadMore"
          :text="$str('loadmore', 'container_workspace')"
          @click="loadMoreDiscussions"
        />
      </div>
    </div>
  </div>
</template>

<script>
import apolloClient from 'tui/apollo_client';
import Button from 'tui/components/buttons/Button';
import DiscussionCard from 'container_workspace/components/card/DiscussionWithCommentCard';
import DiscussionContentResultCard from 'container_workspace/components/card/DiscussionContentResultCard';
import DiscussionFilter from 'container_workspace/components/filter/DiscussionFilter';
import { notify } from 'tui/notifications';
import PageLoader from 'tui/components/loading/Loader';
import PostDiscussionForm from 'container_workspace/components/form/PostDiscussionForm';
import Separator from 'tui/components/decor/Separator';
import VirtualScroll from 'tui/components/virtualscroll/VirtualScroll';

// GraphQL
import getDiscussions from 'container_workspace/graphql/get_discussions';
import getWorkspaceInteractor from 'container_workspace/graphql/workspace_interactor';
import postDiscussion from 'container_workspace/graphql/post_discussion';
import searchContent from 'container_workspace/graphql/search_discussion_content';

const AUTOLOAD_LIMIT = 40;

export default {
  components: {
    Button,
    DiscussionCard,
    DiscussionContentResultCard,
    DiscussionFilter,
    PageLoader,
    PostDiscussionForm,
    Separator,
    VirtualScroll,
  },

  props: {
    workspaceId: {
      type: [Number, String],
      required: true,
    },

    workspaceContextId: {
      type: [Number, String],
      required: true,
    },

    selectedSort: {
      type: String,
      required: true,
    },

    /**
     * A total (aggregate) number of discussions within a workspace. This number will be
     * completely different from the number total from `page.cursor`
     */
    workspaceTotalDiscussions: {
      type: [Number, String],
      required: true,
    },
  },

  apollo: {
    interactor: {
      query: getWorkspaceInteractor,
      context: { batch: true },
      variables() {
        return {
          workspace_id: this.workspaceId,
        };
      },
    },

    /**
     * Fetching the none-pinned discussions within this workspace.
     */
    page: {
      query: getDiscussions,
      fetchPolicy: 'network-only',
      context: { batch: true },
      variables() {
        return {
          workspace_id: this.workspaceId,
          sort: this.sort,
        };
      },

      update({ cursor, discussions }) {
        return {
          cursor: cursor,
          discussions: discussions,
        };
      },
    },

    /**
     * Searching discussion content
     */
    search: {
      query: searchContent,
      fetchPolicy: 'network-only',
      skip() {
        return !this.searchTerm;
      },
      variables() {
        return {
          workspace_id: this.workspaceId,
          search_term: this.searchTerm,
        };
      },

      update({ cursor, results }) {
        // We want to have a unique id for labels. Using row index
        let updatedResults = results.map((result, idx) => {
          return Object.assign({}, result, { id: idx });
        });

        return {
          cursor: cursor,
          results: updatedResults,
        };
      },
    },
  },

  data() {
    return {
      interactor: {},
      submitting: false,

      /**
       * This is for the page's discussions.
       */
      page: {
        cursor: {
          total: 0,
          next: null,
        },
        discussions: [],
      },

      search: {
        cursor: {
          total: 0,
          next: null,
        },
        results: [],
      },

      sort: this.selectedSort,
      searchTerm: '',
      hasError: false,
    };
  },

  computed: {
    /**
     * If the current user in session is already a member of a workspace,
     * then he/she should be able to post the discussion
     * @return {Boolean}
     */
    canPostDiscussion() {
      return this.interactor.can_create_discussions;
    },

    /**
     * Returning the user object within workspace's interactor object.
     * @return {Object}
     */
    user() {
      if (!this.interactor.user) {
        return {};
      }

      return this.interactor.user;
    },

    /**
     * @return {{
     *   workspace_id: Number,
     *   sort: String,
     * }}
     */
    discussionQueryVariables() {
      return {
        workspace_id: this.workspaceId,
        sort: this.sort,
      };
    },

    /**
     * @return {{
     *   workspace_id: Number,
     *   search_term: String
     * }}
     */
    searchQueryVariables() {
      return {
        workspace_id: this.workspaceId,
        search_term: this.searchTerm,
      };
    },

    loadMoreDiscussionsVisibility() {
      return !this.$apollo.loading && !this.hasSearch && this.page.cursor.next;
    },

    loadMoreSearchResultsVisibility() {
      return !this.$apollo.loading && this.hasSearch && this.search.cursor.next;
    },

    hasSearch() {
      return !!this.searchTerm;
    },
  },

  watch: {
    /**
     * @param {String} value
     */
    selectedSort(value) {
      this.sort = value;
    },
  },

  methods: {
    /**
     * @return {{
     *   cursor: Object,
     *   discussions: Array
     * }}
     */
    $_getDiscussionCache() {
      return apolloClient.readQuery({
        query: getDiscussions,
        variables: this.discussionQueryVariables,
      });
    },

    /**
     *
     * @param {String} content
     * @param {Number} itemId
     */
    async submit({ content, itemId }) {
      if (this.submitting) {
        return;
      }

      this.submitting = true;

      try {
        await this.$apollo.mutate({
          mutation: postDiscussion,
          refetchAll: false,
          variables: {
            workspace_id: this.workspaceId,
            content: content,
            draft_id: itemId,
          },

          update: (proxy, { data: { discussion } }) => {
            this.updatePageCache(proxy, discussion);
            this.updateSearchCache(proxy, discussion);
          },
        });

        this.$emit('add-discussion');
        this.hasError = false;
      } catch (e) {
        this.hasError = true;
        await notify({
          message: this.$str('error:create_discussion', 'container_workspace'),
          type: 'error',
        });
      } finally {
        this.submitting = false;
      }
    },

    /**
     * @param {InMemoryCache} proxy
     * @param {Object} discussion
     */
    updatePageCache(proxy, discussion) {
      const variables = this.discussionQueryVariables;

      let { cursor, discussions } = proxy.readQuery({
        query: getDiscussions,
        variables: variables,
      });

      cursor = Object.assign({}, cursor);
      cursor.total += 1;

      proxy.writeQuery({
        query: getDiscussions,
        variables: variables,
        data: {
          cursor: cursor,
          discussions: Array.prototype.concat.call([discussion], discussions),
        },
      });
    },

    /**
     * @param {InMemoryCache} proxy
     * @param {Object} discussion
     */
    updateSearchCache(proxy, discussion) {
      if (!this.hasSearch) {
        return;
      }

      let regex = new RegExp(this.searchTerm, 'i');
      if (regex.test(discussion.content)) {
        const variables = this.searchQueryVariables;

        let { cursor, results } = proxy.readQuery({
          query: searchContent,
          variables: variables,
        });

        cursor = Object.assign({}, cursor);
        cursor.total += 1;
        const newResult = {
          id: results.length + 1,
          workspace_id: discussion.workspace_id,
          discussion_id: discussion.id,
          instance_type: 'DISCUSSION',
          instance_id: discussion.id,
          content: discussion.content,
          owner: Object.assign({}, discussion.owner),
          time_description: discussion.time_description,
          __typename: 'container_workspace_discussion_search_result',
        };

        proxy.writeQuery({
          query: searchContent,
          variables: variables,
          data: {
            cursor: cursor,
            results: Array.prototype.concat.call([newResult], results),
          },
        });
      }
    },

    /**
     *
     * @param {Number}  discussionId
     * @param {Boolean} status
     */
    updateDiscussionReactStatus({ discussionId, status }) {
      let { discussions, cursor } = this.$_getDiscussionCache();

      apolloClient.writeQuery({
        query: getDiscussions,
        variables: this.discussionQueryVariables,
        data: {
          cursor: cursor,
          discussions: Array.prototype.map.call(discussions, discussion => {
            if (discussion.id == discussionId) {
              let innerDiscussion = Object.assign({}, discussion),
                interactor = Object.assign(
                  {},
                  innerDiscussion.discussion_interactor
                );

              interactor.reacted = status;
              innerDiscussion.discussion_interactor = interactor;

              if (status) {
                innerDiscussion.total_reactions += 1;
              } else if (0 != innerDiscussion.total_reactions) {
                innerDiscussion.total_reactions -= 1;
              }

              return innerDiscussion;
            }

            return discussion;
          }),
        },
      });
    },

    /**
     *
     * @param {Object} discussion
     */
    updateDiscussion(discussion) {
      let { discussions, cursor } = this.$_getDiscussionCache();
      apolloClient.writeQuery({
        query: getDiscussions,
        variables: this.discussionQueryVariables,
        data: {
          cursor: cursor,
          discussions: Array.prototype.map.call(
            discussions,
            cacheDiscussion => {
              if (cacheDiscussion.id === discussion.id) {
                return discussion;
              }

              return cacheDiscussion;
            }
          ),
        },
      });
    },

    /**
     *
     * @param {Number} discussionId
     */
    addNewComment(discussionId) {
      let { discussions, cursor } = this.$_getDiscussionCache();
      apolloClient.writeQuery({
        query: getDiscussions,
        variables: this.discussionQueryVariables,
        data: {
          cursor: cursor,
          discussions: Array.prototype.map.call(discussions, discussion => {
            if (discussion.id == discussionId) {
              let newDiscussion = Object.assign({}, discussion);
              newDiscussion.total_comments += 1;

              return newDiscussion;
            }

            return discussion;
          }),
        },
      });
    },

    /**
     * Triggers any requested autoloading
     */
    async onScrollToBottom() {
      const count = this.hasSearch
        ? this.search.results.length
        : this.page.discussions.length;
      if (count >= AUTOLOAD_LIMIT || this.$apollo.loading) {
        return;
      }
      await this.loadMoreItems();
    },

    /**
     * Deprecated - use loadMoreItems instead
     *
     * @deprecated since 15.0
     */
    async loadMore() {
      console.warn(
        'WorkspaceDiscussionTab.loadMore() is deprecated - use loadMoreItems instead'
      );
      await this.loadMoreItems();
    },

    async loadMoreItems() {
      if (this.hasSearch) {
        await this.loadMoreSearchResults();
      } else {
        await this.loadMoreDiscussions();
      }
    },

    async loadMoreDiscussions() {
      if (!this.page.cursor.next || this.$apollo.queries.page.loading) {
        return;
      }
      await this.$apollo.queries.page.fetchMore({
        variables: {
          cursor: this.page.cursor.next,
          workspace_id: this.workspaceId,
          sort: this.sort,
        },
        updateQuery: (previousResult, { fetchMoreResult }) => {
          const oldData = previousResult;
          const newData = fetchMoreResult;
          let newList = oldData.discussions.concat(newData.discussions);

          // Since we're using an offset cursor, it's possible to end up with
          // duplicates in the results if new items are added to the top, so we
          // must filter out any duplicates.
          newList = newList.filter(
            (x, index) => newList.findIndex(y => x.id === y.id) === index
          );

          return {
            cursor: newData.cursor,
            discussions: newList,
          };
        },
      });
    },

    async loadMoreSearchResults() {
      if (!this.search.cursor.next || this.$apollo.queries.search.loading) {
        return;
      }
      await this.$apollo.queries.search.fetchMore({
        variables: {
          cursor: this.search.cursor.next,
          workspace_id: this.workspaceId,
          search_term: this.searchTerm,
        },
        updateQuery: (previousResult, { fetchMoreResult }) => {
          const oldData = previousResult;
          const newData = fetchMoreResult;
          const newList = oldData.results.concat(newData.results);

          // We want to have a unique id for labels. Using row index
          let updatedResults = newList.map((result, idx) => {
            return Object.assign({}, result, { id: idx });
          });

          return {
            cursor: newData.cursor,
            results: updatedResults,
          };
        },
      });
    },

    deleteDiscussion(discussionId) {
      let { discussions, cursor } = this.$_getDiscussionCache();
      apolloClient.writeQuery({
        query: getDiscussions,
        variables: this.discussionQueryVariables,
        data: {
          cursor: cursor,
          discussions: Array.prototype.filter.call(discussions, ({ id }) => {
            return id != discussionId;
          }),
        },
      });
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "discussions_list",
      "error:create_discussion",
      "loadmore",
      "no_discussion_result",
      "search_contents",
      "total_discussions",
      "total_search_results",
      "vieweditems"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceDiscussionTab {
  display: flex;
  flex-direction: column;

  &__filter {
    margin-bottom: var(--gap-4);
  }

  &__card {
    margin-bottom: var(--gap-8);
  }

  &__loadMoreContainer {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding-bottom: var(--gap-8);
  }

  &__viewedDiscussions {
    display: flex;
    align-self: center;
    margin-bottom: var(--gap-1);
  }

  &__loadMore {
    display: flex;
    align-self: center;
  }

  &__message {
    @include tui-font-body();
  }

  .tui-actionLink {
    max-height: 1em;
  }

  .tui-workspaceDiscussionTab__resultList {
    margin-bottom: var(--gap-8);
  }
  .tui-workspaceDiscussionTab__resultCard {
    margin-top: -1px;
  }
}
</style>
