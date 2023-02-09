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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<template>
  <div class="tui-mod_approval-workflowFormViewPreview">
    <SchemaForm ref="schemaForm" :schema="formSchema" @submit="handleSubmit">
      <template v-slot:sections="{ sections, renderSection }">
        <div class="tui-mod_approval-workflowFormViewPreview__sections">
          <div
            v-for="section in sections"
            :key="section.key"
            class="tui-mod_approval-workflowFormViewPreview__section"
          >
            <h3 class="tui-mod_approval-workflowFormViewPreview__sectionTitle">
              {{ section.title || sectionTitle(section) }}
            </h3>
            <render :vnode="renderSection(section)" />
          </div>
        </div>
      </template>

      <template v-slot:below>
        <div
          ref="footer"
          class="tui-mod_approval-workflowFormViewPreview__footer"
        >
          <div class="tui-mod_approval-workflowFormViewPreview__footer-inner">
            <FormRow>
              <Button
                type="submit"
                :text="$str('validate_form', 'mod_approval')"
                :styleclass="{ primary: true }"
              />
              <div class="tui-mod_approval-workflowFormViewPreview__footerInfo">
                <InfoIcon
                  state="none"
                  class="tui-mod_approval-workflowFormViewPreview__footerInfoIcon"
                /><span>{{ $str('validate_form_info', 'mod_approval') }}</span>
              </div>
            </FormRow>
          </div>
        </div>
      </template>
    </SchemaForm>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import FormRow from 'tui/components/form/FormRow';
import InfoIcon from 'tui/components/icons/Info';
import SchemaForm from 'mod_approval/components/schema_form/SchemaForm';
import { notify } from 'tui/notifications';

export default {
  components: {
    Button,
    FormRow,
    InfoIcon,
    SchemaForm,
  },

  props: {
    workflowId: {
      type: Number,
      required: true,
    },
    stageId: {
      type: Number,
      required: true,
    },
    schemaJson: {
      type: String,
      required: true,
    },
  },

  computed: {
    formSchema() {
      return JSON.parse(this.schemaJson);
    },

    backUrl() {
      return this.$url('/mod/approval/workflow/edit.php', {
        workflow_id: this.workflowId,
        stage_id: this.stageId,
        sub_section: 'formViews',
      });
    },
  },

  methods: {
    sectionTitle(section) {
      return `${section.line} - ${section.label}`;
    },

    handleSubmit() {
      notify({
        message: this.$str('form_validated_message', 'mod_approval'),
      });
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "form_validated_message",
    "validate_form",
    "validate_form_info"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-workflowFormViewPreview {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 var(--gap-8) 20rem var(--gap-8);

  &__sections {
    @include tui-stack-vertical(var(--gap-12));
  }

  &__sectionTitle {
    margin-top: 0;
    margin-bottom: var(--gap-6);
    padding-top: var(--gap-8);
  }

  &__footer {
    position: fixed;
    right: 0;
    bottom: 0;
    left: 0;
    margin-top: var(--gap-8);
    background: var(--color-neutral-3);

    &-inner {
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      padding: var(--gap-6) var(--gap-8);
    }
  }

  &__footerInfo {
    color: var(--color-neutral-6);
  }

  &__footerInfoIcon {
    margin-right: var(--gap-2);
  }
}
</style>
