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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

import { announce } from 'tui/accessibility';
import { getString, langString } from 'tui/i18n';

export default class DragDropAnnouncer {
  constructor(manager) {
    this.manager = manager;
    this.isRestorationAnnounced = false;
  }

  isNullMove({ dragItem, dropDesc }) {
    return (
      dropDesc &&
      dragItem.descriptor.sourceId === dropDesc.sourceId &&
      dragItem.descriptor.index === dropDesc.index
    );
  }

  handleDragStart({ dropDesc }) {
    this.isRestorationAnnounced = false;
    announce({
      message: getString('dragdrop_announce_lift', 'totara_core', {
        from_position: dropDesc.index + 1,
      }),
    });
  }

  handleDragMove({ dragItem, dropDesc, valid }) {
    this.isRestorationAnnounced = false;
    if (!dropDesc) {
      announce({
        message: getString('dragdrop_announce_no_drop', 'totara_core'),
      });
      return;
    }

    const fromSource = this.manager.getSource(dragItem.descriptor.sourceId);
    const toSource = this.manager.getSource(dropDesc.sourceId);

    const formatParams = {
      from_position: dragItem.descriptor.index + 1,
      from_source_name: fromSource && fromSource.sourceName,
      to_position: dropDesc.index + 1,
      to_source_name: toSource && toSource.sourceName,
    };

    const invalidAppend = valid
      ? ''
      : ' ' + getString('dragdrop_announce_no_drop', 'totara_core');

    if (valid && this.isNullMove({ dragItem, dropDesc })) {
      this.isRestorationAnnounced = true;
      announce({
        message:
          getString(
            'dragdrop_announce_move_null',
            'totara_core',
            formatParams
          ) + invalidAppend,
      });
    } else if (fromSource == toSource) {
      announce({
        message:
          getString(
            'dragdrop_announce_move_same',
            'totara_core',
            formatParams
          ) + invalidAppend,
      });
    } else {
      // moving lists via keyboard while in drag mode is not supported at the
      // moment, but handle it anyway
      if (
        fromSource &&
        toSource &&
        fromSource.sourceName &&
        toSource.sourceName
      ) {
        announce({
          message:
            getString(
              'dragdrop_announce_move_other',
              'totara_core',
              formatParams
            ) + invalidAppend,
        });
      } else {
        announce({
          message:
            getString(
              'dragdrop_announce_move_unknown',
              'totara_core',
              formatParams
            ) + invalidAppend,
        });
      }
    }
  }

  handleDragEnd({ dragItem, dropDesc, drop }) {
    const fromSource = this.manager.getSource(dragItem.descriptor.sourceId);
    const toSource = this.manager.getSource(dropDesc.sourceId);
    const formatParams = {
      from_position: dragItem.descriptor.index + 1,
      from_source_name: fromSource && fromSource.sourceName,
      to_position: dropDesc.index + 1,
      to_source_name: toSource && toSource.sourceName,
    };

    if (!drop) {
      if (this.isRestorationAnnounced) {
        // silence because "you have restored the item" is the previous announcement.
        return;
      }
      if (this.isNullMove({ dragItem, dropDesc })) {
        announce({
          message: getString(
            'dragdrop_announce_move_null',
            'totara_core',
            formatParams
          ),
        });
        return;
      }
      announce({
        message: getString(
          'dragdrop_announce_drop_cancel',
          'totara_core',
          formatParams
        ),
      });
      return;
    }

    if (drop) {
      if (fromSource == toSource) {
        announce({
          message: getString(
            'dragdrop_announce_drop_same',
            'totara_core',
            formatParams
          ),
        });
      } else {
        if (fromSource.sourceName && toSource.sourceName) {
          announce({
            message: getString(
              'dragdrop_announce_drop_other',
              'totara_core',
              formatParams
            ),
          });
        } else {
          announce({
            message: getString(
              'dragdrop_announce_drop_unknown',
              'totara_core',
              formatParams
            ),
          });
        }
      }
    }
  }
}

DragDropAnnouncer.langStrings = [
  langString('dragdrop_announce_lift', 'totara_core'),
  langString('dragdrop_announce_move_null', 'totara_core'),
  langString('dragdrop_announce_move_same', 'totara_core'),
  langString('dragdrop_announce_move_other', 'totara_core'),
  langString('dragdrop_announce_move_unknown', 'totara_core'),
  langString('dragdrop_announce_no_drop', 'totara_core'),
  langString('dragdrop_announce_drop_cancel', 'totara_core'),
  langString('dragdrop_announce_drop_same', 'totara_core'),
  langString('dragdrop_announce_drop_other', 'totara_core'),
  langString('dragdrop_announce_drop_unknown', 'totara_core'),
];
