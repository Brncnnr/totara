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
  @module totara_comment
-->
<template>
  <div class="tui-commentReplyCard">
    <div class="tui-commentReplyCard__profilePicture">
      <slot name="profile-picture" />
    </div>

    <div class="tui-commentReplyCard__body">
      <div class="tui-commentReplyCard__header">
        <slot name="body-header" />
      </div>

      <div class="tui-commentReplyCard__content">
        <slot name="body-content" />
      </div>

      <div class="tui-commentReplyCard__footer">
        <slot name="body-footer" />
      </div>
    </div>
  </div>
</template>

<style lang="scss">
.tui-commentReplyCard {
  display: flex;
  align-items: flex-start;

  &__profilePicture {
    margin-right: var(--gap-2);
  }

  &__body {
    flex: 1 1 auto;
  }

  &__header {
    margin-bottom: var(--gap-2);
  }

  &__content {
    margin-bottom: var(--gap-2);
    padding-right: var(--gap-2);
    @include tui-wordbreak--hyphens;
  }
}
</style>
