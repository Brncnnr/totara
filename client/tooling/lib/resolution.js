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
 * @module totara_core
 */

const path = require('path');
const fs = require('fs');
const { rootDir } = require('./common');
const graphqlMap = require(path.join(rootDir + 'graphql_locations.json'));

const graphqlMapEntries = Object.entries(
  graphqlMap.locations
).map(([componentRegex, path]) => ({
  componentRegex: new RegExp('^' + componentRegex + '$'),
  path,
}));

const graphqlImportRegexp = /^([a-zA-Z0-9_]+)\/graphql\/(.*)$/;

const defaultFolder = 'js';

// vue mapped folders
const vueFolders = ['components', 'pages'];

// all mapped folders
const subfolders = [].concat(vueFolders);

const dirMaps = [{ idBaseSuffix: '', path: './' + defaultFolder }].concat(
  subfolders.map(x => ({ idBaseSuffix: '/' + x, path: './' + x }))
);

const clientDirExists = new Map();

/**
 * Get the directory a Totara client component lives in, relative to `rootDir`.
 *
 * @param {string} component
 * @returns {?string} null if component is not a client component (probably an npm package)
 */
function getClientDir(component) {
  if (!component) {
    return null;
  }
  const dir = 'client/component/' + component + '/src';
  if (!clientDirExists.has(component)) {
    clientDirExists.set(component, fs.existsSync(dir));
  }
  return clientDirExists.get(component) ? dir : null;
}

/**
 * Resolve an import request to a file.
 *
 * @param {string} req
 * @returns {?string}
 *   Path to file to include (may be missing file extension), or null if not a TUI-resolved request.
 *   Relative to root dir.
 */
function resolveRequest(req) {
  let result;

  if (req[0] == '.') return;

  // First check if this is a static alias.
  result = resolveStaticAlias(req);
  if (result) return path.resolve(result);

  // Check if this is one of ours.
  const parsedReq = parseComponentRequest(req);
  if (!parsedReq) return;
  const { dir, rest, restParts } = parsedReq;

  // subfolders get mapped directly under tui/, everything else goes in tui/js/ (defaultFolder = 'js')
  const prefix = subfolders.some(x => restParts[0] == x)
    ? ''
    : defaultFolder + '/';

  let extensions = [''];
  if (!rest.match(/\.[a-z]+$/)) {
    extensions = ['.mjs', '.js', '.json', '.vue', '.graphql', ''];
  }

  let i = 0;
  for (i in extensions) {
    if (!extensions.hasOwnProperty(i)) {
      continue;
    }
    let ext = extensions[i];
    const file = path.resolve(`${dir}/${prefix}${rest}${ext}`);
    if (fs.existsSync(file)) {
      return file;
    }
  }

  return null;
}

/**
 * Resolve an import request to a filename, if it has a static alias.
 *
 * Imports with static aliases are resolved at build time rather than at runtime.
 *
 * @param {string} req
 * @returns {?string}
 *   Path to file to include, or null if no static alias was found.
 *   Relative to root dir.
 */
function resolveStaticAlias(req) {
  if (req[0] == '.') return;
  return resolveGraphQLImport(req);
}

/**
 * Resolves a GraphQL import to a file.
 *
 * @param {string} req
 * @returns {?string} Path if found.
 */
function resolveGraphQLImport(req) {
  const graphqlResult = graphqlImportRegexp.exec(req);
  if (graphqlResult) {
    const component = graphqlResult[1];
    const subPath = graphqlResult[2];
    for (let { componentRegex, path } of graphqlMapEntries) {
      const componentMatch = componentRegex.exec(component);
      if (componentMatch) {
        const result = path.replace(/\{\$(\w+)\}/g, (match, capture) => {
          // positional capture
          if (capture && !isNaN(capture)) {
            return componentMatch[capture];
          }
        }) + subPath + '.graphql';
        return result;
      }
    }
  }
}

/**
 * Parse a request for a file inside a component and return parts
 *
 * e.g.
 *   - totara_core/foo/bar -> { dir: 'totara/core', rest: 'foo/bar', restParts: ['foo', 'bar'] }
 *
 * @param {string} req
 * @returns {object}
 */
function parseComponentRequest(req) {
  const [component, ...restParts] = req.split('/');
  if (!restParts) return null;
  const rest = restParts.join('/');
  const dir = getClientDir(component);
  if (!dir) {
    return null;
  }
  return { component, dir, rest, restParts };
}

module.exports = {
  subfolders,
  defaultFolder,
  dirMaps,
  vueFolders,
  getClientDir,
  resolveRequest,
  resolveStaticAlias,
};
