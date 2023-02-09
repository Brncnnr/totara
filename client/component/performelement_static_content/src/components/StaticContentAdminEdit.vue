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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module performelement_static_content
-->

<template>
  <div class="tui-staticContentAdminEdit">
    <PerformAdminCustomElementEdit
      v-if="ready"
      :initial-values="initialValues"
      :settings="settings"
      @cancel="$emit('display')"
      @update="$emit('update', processData($event))"
    >
      <FormRow
        v-slot="{ id }"
        :label="
          $str('static_content_placeholder', 'performelement_static_content')
        "
        :required="true"
      >
        <FormField
          v-slot="{ value, update }"
          name="wekaDoc"
          :validate="validateEditor"
        >
          <Weka
            :id="id"
            :context-id="activityContextId"
            :value="value"
            :usage-identifier="{
              component: 'performelement_static_content',
              area: 'content',
              instanceId: actualElementId,
            }"
            variant="full"
            :file-item-id="initialValues.draftId"
            :placeholder="
              $str('weka_enter_content', 'performelement_static_content')
            "
            @input="update"
          />
        </FormField>
      </FormRow>
    </PerformAdminCustomElementEdit>
  </div>
</template>

<script>
import PerformAdminCustomElementEdit from 'mod_perform/components/element/PerformAdminCustomElementEdit';
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';
import { FormField, FormRow } from 'tui/components/uniform';

// GraphQL queries
import fileDraftId from 'core/graphql/file_unused_draft_item_id';
import prepareDraftArea from 'performelement_static_content/graphql/prepare_draft_area';

export default {
  components: {
    FormField,
    FormRow,
    PerformAdminCustomElementEdit,
    Weka,
  },

  inheritAttrs: false,

  props: {
    data: Object,
    elementId: [Number, String],
    identifier: String,
    rawData: Object,
    rawTitle: String,
    sectionId: [Number, String],
    settings: Object,
    activityContextId: [Number, String],
  },

  data() {
    return {
      initialValues: {
        data: this.data,
        draftId: null,
        identifier: this.identifier,
        rawTitle: this.rawTitle,
        wekaDoc: WekaValue.empty(),
      },
      ready: false,
    };
  },

  computed: {
    actualElementId() {
      return this.elementId && !isNaN(this.elementId) ? this.elementId : null;
    },
  },

  async mounted() {
    if (this.rawData && this.rawData.wekaDoc) {
      this.initialValues.wekaDoc = WekaValue.fromDoc(
        JSON.parse(this.rawData.wekaDoc)
      );
    }
    if (this.sectionId && this.actualElementId) {
      await this.$_loadExistingDraftId();
    } else {
      await this.$_loadNewDraftId();
    }

    this.ready = true;
  },

  methods: {
    async $_loadNewDraftId() {
      const {
        data: { item_id },
      } = await this.$apollo.mutate({ mutation: fileDraftId });
      this.initialValues.draftId = item_id;
    },

    async $_loadExistingDraftId() {
      const {
        data: { draft_id },
      } = await this.$apollo.mutate({
        mutation: prepareDraftArea,
        variables: {
          section_id: parseInt(this.sectionId),
          element_id: parseInt(this.elementId),
        },
      });
      this.initialValues.draftId = draft_id;
    },

    /**
     * Stringify weka value and structure form data correctly for query
     *
     * @param {Object} values
     * @returns {String}
     */
    processData(values) {
      let modifiedValues = {
        data: {
          docFormat: 'FORMAT_JSON_EDITOR',
          draftId: values.data.draftId,
          format: 'HTML',
          wekaDoc: JSON.stringify(values.data.wekaDoc.getDoc()),
        },
        title: values.title,
      };

      return modifiedValues;
    },

    /**
     * Validate that weka editor value
     *
     * @param {Text} value
     * @returns {String}
     */
    validateEditor(value) {
      if (!value || value.isEmpty) {
        return this.$str('required', 'performelement_static_content');
      }
    },
  },
};
</script>

<lang-strings>
   {
     "performelement_static_content": [
       "required",
       "static_content_placeholder",
       "weka_enter_content"
     ]
   }
   </lang-strings>
