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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @module mod_approval
 */

import { config } from 'tui/config';
import { getString } from 'tui/i18n';
import { langString as makeLangString, loadLangStrings } from 'tui/i18n';

const successMessageTable = {
  // application
  'done.invoke.approveApplication': 'success:approve_application',
  'done.invoke.rejectApplication': 'success:reject_application',
  // workflow
  'done.invoke.archiveWorkflow': 'success:archive_workflow',
  'done.invoke.unarchiveWorkflow': 'success:unarchive_workflow',
  'done.invoke.deleteWorkflow': 'success:delete_workflow',
  'done.invoke.editWorkflow': 'success:edit_workflow',
  'done.invoke.publishWorkflowVersion': 'success:publish_workflow_version',
  'done.invoke.saveOverrides': 'success:save_overrides',
  'done.invoke.archiveOverrides': 'success:archive_overrides',
  'done.invoke.createOverrideAssignment': 'success:create_override',
  'done.invoke.updateDefaultTransition': 'success:update_default_transition',
};

const errorMessageTable = {
  // TODO remove unused?

  // application
  'error.platform.createComment': 'error:reject_application',
  'error.platform.rejectApplication': 'error:reject_application',
  'error.platform.approveApplication': 'error:approve_application',
  'error.platform.saveApplication': 'error:save_application',
  'error.platform.saveAsDraftApplication': 'error:save_application',
  'error.platform.submitApplication': 'error:submit_application',
  'error.platform.deleteApplication': 'error:delete_application',
  'error.platform.withdrawApplication': 'error:withdraw_application',
  'error.platform.cloneApplication': 'error:clone_application',
  'error.platform.cloningApplication': 'error:clone_application',
  'error.platform.creatingApplicationMutation': 'error:create_application',
  // workflow
  'error.platform.editWorkflow': 'error:edit_workflow_details',
  'error.platform.unarchiveWorkflow': 'error:unarchive_workflow',
  'error.platform.archiveWorkflow': 'error:archive_workflow',
  'error.platform.cloneWorkflow': 'error:clone_workflow',
  'error.platform.deleteWorkflow': 'error:delete_workflow',
  'error.platform.publishWorkflowVersion': 'error:publish_workflow_version',
};

class Loader {
  constructor(table) {
    this.table = table;
  }
  getMessage(event) {
    if (event.type in this.table) {
      return this.resolve(this.table[event.type]);
    }
    const message = this._undefinedError(event.type);
    return this.error(message);
  }
  _suggestName(eventType) {
    const snakeCase = s => s.replace(/([A-Z])/g, '_$1').toLowerCase();
    if (/^done\.invoke\.[a-z]/.test(eventType)) {
      return 'success:' + snakeCase(eventType.substring(12));
    } else if (/^error\.platform\.[a-z]/.test(eventType)) {
      return 'error:' + snakeCase(eventType.substring(15));
    }
    return false;
  }
  _undefinedError(eventType) {
    // Display debug info in development.
    if (process.env.NODE_ENV === 'development') {
      let message = `message for ${eventType} is not defined.`;
      const suggestion = this._suggestName(eventType);
      if (suggestion) {
        message += ` (${suggestion})`;
      }
      console.error(message);
      return `Error: ${message}`;
    }
    // Display '?' in behat.
    if (config.behatSiteRunning) {
      return '?';
    }
    // Display 'an error occurred' in production.
    return getString('error:generic', 'mod_approval');
  }
}

class SyncLoader extends Loader {
  constructor(table) {
    super(table);
  }
  resolve(langId) {
    return getString(langId, 'mod_approval');
  }
  error(message) {
    return message;
  }
}

class AsyncLoader extends Loader {
  constructor(table) {
    super(table);
  }
  resolve(langId) {
    const langString = makeLangString(langId, 'mod_approval');
    return loadLangStrings([langString]).then(() => langString.toString());
  }
  error(message) {
    return Promise.resolve(message);
  }
}

export function getSuccessMessage(event) {
  return new SyncLoader(successMessageTable).getMessage(event);
}

export function getSuccessMessageAsync(event) {
  return new AsyncLoader(successMessageTable).getMessage(event);
}

export function getErrorMessage(event) {
  return new SyncLoader(errorMessageTable).getMessage(event);
}

export function getErrorMessageAsync(event) {
  return new AsyncLoader(errorMessageTable).getMessage(event);
}
