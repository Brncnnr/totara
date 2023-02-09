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

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<template>
  <div>
    <SubPageHeading :title="$str('form', 'mod_approval')">
      <template v-slot:buttons>
        <ButtonIcon
          :text="$str('preview_form', 'mod_approval')"
          :aria-label="$str('preview_form', 'mod_approval')"
          :styleclass="{ textFirst: true }"
          @click="handlePreviewClick"
        >
          <ExternalLinkIcon />
        </ButtonIcon>
      </template>
    </SubPageHeading>

    <FormAdmin
      v-if="$selectors.getActiveStage($context)"
      class="tui-mod_approval-formViewSection__formAdmin"
      :sections="$selectors.getFormSchemaSections($context)"
      :field-config="$selectors.getActiveStageFormviewsObject($context)"
      :section-config="$selectors.getActiveStageSectionConfig($context)"
      :editable="$selectors.getWorkflowIsDraft(this.$context)"
      @update-field-config="updateFieldConfig"
      @update-section-config="updateSectionConfig"
    />
  </div>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ExternalLinkIcon from 'tui/components/icons/ExternalLink';
import SubPageHeading from 'mod_approval/components/page/SubPageHeading';
import FormAdmin from 'mod_approval/components/workflow/form_views/FormAdmin';
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';

export default {
  components: {
    ButtonIcon,
    ExternalLinkIcon,
    SubPageHeading,
    FormAdmin,
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },

  methods: {
    handlePreviewClick() {
      window.open(this.$selectors.getFormPreviewUrl(this.$context));
    },
    updateFieldConfig(key, update) {
      const stageId = this.$selectors.getActiveStageId(this.$context);
      this.$send({
        type: this.$e.UPDATE_FORMVIEW,
        workflowStageId: stageId,
        key,
        update,
      });
    },
    updateSectionConfig(key, update, prev) {
      if (update.visible == null || update.visible === prev.visible) {
        // no change
        return;
      }
      const stageId = this.$selectors.getActiveStageId(this.$context);
      this.$send({
        type: this.$e.UPDATE_SECTION_VISIBILITY,
        workflowStageId: stageId,
        key,
        visible: update.visible,
      });
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "form",
    "preview_form"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-formViewSection {
  &__formAdmin {
    margin-top: var(--gap-4);
  }
}
</style>
