/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Qingyang Liu <Qingyang.liu@totaralearning.com>
 * @module engage_survey
 */
import getSurvey from 'engage_survey/graphql/get_survey';
import updateBookmark from 'totara_engage/graphql/update_bookmark';

export default {
  props: {
    resourceId: {
      type: Number,
      required: true,
    },

    backButton: {
      type: Object,
      required: false,
    },

    navigationButtons: {
      type: Object,
      required: false,
    },

    interactor: {
      type: Object,
      default: () => ({
        user_id: 0,
        can_bookmark: false,
        can_comment: false,
        can_react: false,
        can_share: false,
        show_share_button: false,
      }),
    },
  },

  apollo: {
    survey: {
      query: getSurvey,
      variables() {
        return {
          resourceid: this.resourceId,
        };
      },
      result({ data: { survey } }) {
        this.bookmarked = survey.bookmarked;
      },
    },
  },

  data() {
    return {
      survey: {},
      bookmarked: false,
    };
  },

  computed: {
    /**
     *
     * @returns {Object}
     */
    firstQuestion() {
      if (!this.survey) {
        return {};
      }

      return Array.prototype.slice.call(this.survey.questions).shift();
    },
  },

  methods: {
    updateBookmark() {
      this.bookmarked = !this.bookmarked;
      this.$apollo.mutate({
        mutation: updateBookmark,
        refetchAll: false,
        variables: {
          itemid: this.resourceId,
          component: 'engage_survey',
          bookmarked: this.bookmarked,
        },
      });
    },
  },
};
