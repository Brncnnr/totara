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
  @module totara_topic
-->
<template>
  <TagList
    class="tui-topicsSelector"
    :filter="searchTerm"
    :items="displayTopics"
    :tags="pickedTopics"
    :input-placeholder="inputPlaceholder"
    @filter="searchTerm = $event"
    @select="selectTopic"
    @remove="removeTopic"
  >
    <template v-slot:item="{ item: { value } }">
      {{ value }}
    </template>
  </TagList>
</template>

<script>
import TagList from 'tui/components/tag/TagList';

// GraphQL queries
import findTopics from 'totara_topic/graphql/find_topics';

export default {
  components: {
    TagList,
  },

  props: {
    selectedTopics: {
      type: Array,
      default() {
        return [];
      },

      validator(prop) {
        let items = Array.prototype.filter.call(prop, item => {
          return !('value' in item) || !('id' in item);
        });

        return 0 === items.length;
      },
    },

    inputPlaceholder: String,
  },

  apollo: {
    topics: {
      query: findTopics,
      variables() {
        return {
          search: this.searchTerm,
          exclude: this.selectedTopicIds,
        };
      },
    },
  },

  data() {
    return {
      searchTerm: '',
    };
  },

  computed: {
    selectedTopicIds() {
      if (0 === this.selectedTopics.length) {
        return [];
      }

      return Array.prototype.map.call(this.selectedTopics, ({ id }) => id);
    },

    pickedTopics() {
      return Array.prototype.map.call(this.selectedTopics, ({ value, id }) => {
        return {
          id: id,
          text: value,
        };
      });
    },

    /**
     * Get the topics to display in the topic dropdown (topics less selected topics).
     * Reduces number of ajax requests, and improves UI response time
     */
    displayTopics() {
      if (!this.topics) {
        return [];
      }
      return this.topics.filter(topic => {
        let contains = this.selectedTopics.filter(selected => {
          return selected.id == topic.id;
        });

        return contains.length === 0;
      });
    },
  },

  methods: {
    /**
     * Selects a topic and moves it to the selected tags list
     *
     * @param {Number} id the id of the tag being selected
     * @param {String} value the human readible name of the tag beins selected
     */
    async selectTopic({ id, value }) {
      const selectedTopics = Array.prototype.concat.call(this.selectedTopics, {
        id,
        value,
      });

      this.$emit('change', selectedTopics);

      await this.$nextTick();
      if (this.displayTopics.length === 0) {
        // all viewable items have been selected - reset the filter
        this.searchTerm = '';
        this.$apollo.queries.topics.refetch();
      }
    },

    /**
     *
     * @param {Number} id
     */
    removeTopic({ id }) {
      const selectedTopics = Array.prototype.filter.call(
        this.selectedTopics,
        item => {
          return item.id !== id;
        }
      );

      // Need to update the list again.
      this.$emit('change', selectedTopics);
      this.$apollo.queries.topics.refetch();
    },
  },
};
</script>

<style lang="scss">
.tui-topicsSelector {
  width: 100%;
}
</style>
