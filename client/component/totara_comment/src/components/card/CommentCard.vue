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
  <div class="tui-commentCard">
    <div class="tui-commentCard__comment">
      <div class="tui-commentCard__profilePicture">
        <slot name="profile-picture" />
      </div>

      <div class="tui-commentCard__body">
        <div class="tui-commentCard__header">
          <slot name="body-header" />
        </div>

        <div class="tui-commentCard__content">
          <slot name="body-content" />
        </div>

        <div class="tui-commentCard__footer">
          <slot name="body-footer" />
        </div>
      </div>
    </div>

    <div class="tui-commentCard__replyBox">
      <slot name="reply-box" />
    </div>
  </div>
</template>

<style lang="scss">
.tui-commentCard {
  display: flex;
  flex-direction: column;

  &__comment {
    display: flex;
    align-items: flex-start;
  }

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
