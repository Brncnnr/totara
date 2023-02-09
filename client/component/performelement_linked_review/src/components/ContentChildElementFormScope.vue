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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @module performelement_linked_review
-->

<template>
  <FormScope :path="contentPath()" :process="normalizeContentResponses">
    <FormScope :path="contentItem.id" :process="contentResponsesProcessor">
      <ChildElementFormScope
        :key="childElement.id"
        :element="childElement"
        :child-element-responses-identifier="childElementResponsesIdentifier"
      >
        <slot />
      </ChildElementFormScope>
    </FormScope>
  </FormScope>
</template>

<script>
import FormScope from 'tui/components/reform/FormScope';
import ChildElementFormScope from 'mod_perform/components/element/ChildElementFormScope';

export default {
  components: {
    ChildElementFormScope,
    FormScope,
  },
  props: {
    childElement: Object,
    contentItem: Object,
    path: [Array, String],
    sectionElement: Object,
  },
  computed: {
    childElementResponsesIdentifier() {
      return this.sectionElement.element.element_plugin.child_element_config
        .child_element_responses_identifier;
    },
  },
  methods: {
    /**
     * Generates content path.
     * @return {Array}
     */
    contentPath() {
      let contentPath = [];
      let repeatingItemIdentifier = this.sectionElement.element.element_plugin
        .child_element_config.repeating_item_identifier;

      if (this.path instanceof String) {
        contentPath.push(this.path);
      }

      if (this.path instanceof Array) {
        this.path.forEach(pathItem => contentPath.push(pathItem));
      }
      contentPath.push('response', repeatingItemIdentifier);

      return contentPath;
    },

    /**
     * Normalize the content responses from default array provides to an object.
     *
     * @param {Object|Array} value
     * @return {Object}
     */
    normalizeContentResponses(value) {
      if (Array.isArray(value)) {
        let result = {};
        value.map(contentResponses => {
          result[contentResponses.content_id] = contentResponses;
        });

        return result;
      }

      return value;
    },

    /**
     * Parses the content responses and appends the content item id.
     *
     * Converts the content responses from the default array uniform provides to an object.
     * @param {Object|Array} value
     * @return {Object}
     */
    contentResponsesProcessor(value) {
      if (Array.isArray(value[this.childElementResponsesIdentifier])) {
        let childElementResponses = {};

        value[this.childElementResponsesIdentifier].map(childResponse => {
          childElementResponses[childResponse.child_element_id] = childResponse;
        });

        value[this.childElementResponsesIdentifier] = childElementResponses;
      }

      if (!value.content_id) {
        value.content_id = this.contentItem.id;
      }

      value[
        this.childElementResponsesIdentifier
      ] = this.removeInvalidChildResponses(
        value[this.childElementResponsesIdentifier]
      );

      return value;
    },

    /**
     * Removes invalid child element responses.
     *
     * @param {Object} childElementResponses
     * @return {Object}
     */
    removeInvalidChildResponses(childElementResponses) {
      let InvalidResponseKeys = Object.keys(childElementResponses).filter(
        childElementId => {
          let childElementResponse = childElementResponses[childElementId];

          return this.withoutChildElementId(childElementResponse);
        }
      );

      InvalidResponseKeys.map(key => {
        delete childElementResponses[key];
      });

      return childElementResponses;
    },

    /**
     * Checks if the child response does not have a child element id.
     *
     * @param {Object} response
     * @return {Boolean}
     */
    withoutChildElementId(response) {
      return !response.child_element_id;
    },
  },
};
</script>
