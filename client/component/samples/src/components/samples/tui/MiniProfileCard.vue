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
  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module samples
-->
<template>
  <div class="tui-samples-miniProfileCard">
    <SamplesExample>
      <Separator :thick="true" :spread="true">
        Without dropdown
      </Separator>
      <MiniProfileCard
        :style="{ width: boxWidthPercent + '%' }"
        :display="cardData"
        class="tui-samples-miniProfileCard__card"
        :has-dropdown="false"
        :no-border="noBorder"
        :no-padding="noPadding"
        :horizontal="horizontal"
        :has-shadow="hasShadow"
        :read-only="readOnly"
      />

      <Separator :thick="true" :spread="true">
        With dropdown
      </Separator>
      <MiniProfileCard
        :style="{ width: boxWidthPercent + '%' }"
        :display="cardData"
        class="tui-samples-miniProfileCard__card"
        :has-dropdown="true"
        :no-border="noBorder"
        :no-padding="noPadding"
        :horizontal="horizontal"
        :has-shadow="hasShadow"
        :read-only="readOnly"
      >
        <template v-slot:drop-down-items>
          <DropdownItem @click="modalOpen = true">Open Modal</DropdownItem>
        </template>
      </MiniProfileCard>

      <ModalPresenter :open="modalOpen" @request-close="modalOpen = false">
        <Modal>
          <ModalContent title="Title prop for the ModalContent" :close="true">
            <h5>This is modal content which could be easily customised</h5>
          </ModalContent>
        </Modal>
      </ModalPresenter>
    </SamplesExample>
    <SamplesPropCtl>
      <FormRow v-slot="{ id, label }" label="Description Row 1">
        <InputText :id="id" v-model="descriptionRow1" :placeholder="label" />
      </FormRow>
      <FormRow v-slot="{ id, label }" label="Description Row 2">
        <InputText :id="id" v-model="descriptionRow2" :placeholder="label" />
      </FormRow>
      <FormRow v-slot="{ id, label }" label="Description Row 3">
        <InputText :id="id" v-model="descriptionRow3" :placeholder="label" />
      </FormRow>
      <FormRow v-slot="{ id, label }" label="Description Row 4">
        <InputText :id="id" v-model="descriptionRow4" :placeholder="label" />
      </FormRow>
      <FormRow v-slot="{ id }" label="No Border">
        <RadioGroup :id="id" v-model="noBorder" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow v-slot="{ id }" label="No Padding">
        <RadioGroup :id="id" v-model="noPadding" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow v-slot="{ id }" label="Horizontal">
        <RadioGroup :id="id" v-model="horizontal" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow v-slot="{ id }" label="Has Shadow">
        <RadioGroup :id="id" v-model="hasShadow" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow v-slot="{ id }" label="Read Only">
        <RadioGroup :id="id" v-model="readOnly" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow v-slot="{ id }" label="Box Width Percentage">
        <InputNumber :id="id" v-model="boxWidthPercent" :min="0" :max="100" />
      </FormRow>
    </SamplesPropCtl>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import { createSilhouetteImage } from '../../../../../tui/src/js/internal/placeholder_generator.js';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import FormRow from 'tui/components/form/FormRow';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import Modal from 'tui/components/modal/Modal';
import InputText from 'tui/components/form/InputText';
import InputNumber from 'tui/components/form/InputNumber';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import Separator from 'tui/components/decor/Separator';

export default {
  components: {
    Radio,
    RadioGroup,
    FormRow,
    DropdownItem,
    MiniProfileCard,
    ModalPresenter,
    Modal,
    ModalContent,
    InputText,
    InputNumber,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
    Separator,
  },

  data() {
    return {
      modalOpen: false,
      noBorder: false,
      noPadding: false,
      horizontal: false,
      hasShadow: false,
      readOnly: false,
      boxWidthPercent: 100,
      descriptionRow1: 'Charles F. Oliver',
      descriptionRow2: '@herecomescharlie',
      descriptionRow3:
        '1621 Frum Street, Nashville, TN, Tennessee, United States',
      descriptionRow4: 'charles.f.oliver@example.com',
      codeTemplate: `<MiniProfileCard
        :display="cardData"
        class="tui-samples-miniProfileCard__card"
        :has-dropdown="false"
        :no-border="noBorder"
        :no-padding="noPadding"
        :horizontal="horizontal"
        :has-shadow="hasShadow"
        :read-only="readOnly"
      />`,
      codeScript: `import MiniProfileCard from 'tui/components/profile/MiniProfileCard';

export default {
  components: {
    MiniProfileCard,
  }
}`,
    };
  },

  computed: {
    fields() {
      let fields = [
        {
          label: 1,
          value: this.descriptionRow1,
          associate_url: this.$url('/user/profile.php'),
        },
        {
          label: 2,
          value: this.descriptionRow2,
          associate_url: null,
        },
        {
          label: 3,
          value: this.descriptionRow3,
          associate_url: null,
        },
        {
          label: 4,
          value: this.descriptionRow4,
          associate_url: null,
        },
      ];

      // Remove empty fields
      return fields.filter(x => x.value);
    },

    cardData() {
      return {
        profile_picture_alt: 'Charles F. Oliver picture',
        profile_picture_url: createSilhouetteImage('#3c9'),
        profile_url: this.$url('/user/profile.php'),
        display_fields: this.fields,
      };
    },
  },
};
</script>

<style lang="scss">
.tui-samples-miniProfileCard {
  display: flex;
  flex-direction: column;

  &__card {
    margin-bottom: var(--gap-3);
  }
}
</style>
