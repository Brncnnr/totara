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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module totara_engage
 */

import AccessManager from '../access_manager';

export default {
  props: {
    instanceId: {
      required: true,
      type: [String, Number],
    },

    name: {
      type: String,
    },

    summary: {
      type: String,
    },

    access: {
      required: true,
      type: String,
      validator(prop) {
        return AccessManager.isValid(prop);
      },
    },

    timeCreated: {
      required: true,
      type: String,
    },

    /**
     * A json string being passed from the server. This property should be parsed into JSON within the
     * card implementation in order to use the fields within it.
     */
    extra: {
      type: String,
    },

    topics: {
      type: Array,
    },

    rating: {
      required: true,
      type: [Number, String],
    },

    bookmarked: {
      type: Boolean,
      default: false,
    },

    numberOfPeopleRated: {
      type: [Number, String],
      default: 0,
    },

    totalComments: {
      required: true,
      type: [Number, String],
    },

    totalReactions: {
      required: true,
      type: [Number, String],
    },

    sharedbycount: {
      required: true,
      type: [Number, String],
    },

    owned: {
      type: Boolean,
      default: false,
    },

    userFullName: {
      type: String,
      required: true,
    },

    userId: {
      type: [String, Number],
      required: true,
    },

    userProfileImageUrl: {
      type: String,
      required: true,
    },

    userProfileImageAlt: {
      type: String,
      default: '',
    },

    /**
     * Footnotes that needs to display below card.
     * Refer to the Footnotes component for more information.
     */
    showFootnotes: {
      type: Boolean,
      default: false,
    },
    footnotes: {
      type: Array,
      required: false,
    },

    labelId: {
      type: String,
      default: '',
    },

    url: {
      type: String,
      default: '',
    },

    showBookmark: {
      type: Boolean,
      default: false,
    },
  },

  methods: {
    emitUpdated() {
      this.$emit('updated', { instanceId: this.instanceId });
    },

    emitDeleted() {
      this.$emit('deleted', { instanceId: this.instanceId });
    },
  },
};
