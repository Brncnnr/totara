/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./client/component/totara_oauth2/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\]internal[/\\\\]).)*$":
/*!***********************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/ sync ^(?:(?%21__[a-z]*__%7C[/\\]internal[/\\]).)*$ ***!
  \***********************************************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

eval("var map = {\n\t\"./Oauth2ProviderContent\": \"./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue\",\n\t\"./Oauth2ProviderContent.vue\": \"./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue\",\n\t\"./action/Oauth2ProviderAction\": \"./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue\",\n\t\"./action/Oauth2ProviderAction.vue\": \"./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue\",\n\t\"./modal/Oauth2ProviderModal\": \"./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue\",\n\t\"./modal/Oauth2ProviderModal.vue\": \"./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue\"\n};\n\n\nfunction webpackContext(req) {\n\tvar id = webpackContextResolve(req);\n\treturn __webpack_require__(id);\n}\nfunction webpackContextResolve(req) {\n\tif(!__webpack_require__.o(map, req)) {\n\t\tvar e = new Error(\"Cannot find module '\" + req + \"'\");\n\t\te.code = 'MODULE_NOT_FOUND';\n\t\tthrow e;\n\t}\n\treturn map[req];\n}\nwebpackContext.keys = function webpackContextKeys() {\n\treturn Object.keys(map);\n};\nwebpackContext.resolve = webpackContextResolve;\nmodule.exports = webpackContext;\nwebpackContext.id = \"./client/component/totara_oauth2/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\";\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/_sync_^(?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/pages sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\]internal[/\\\\]).)*$":
/*!******************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/pages/ sync ^(?:(?%21__[a-z]*__%7C[/\\]internal[/\\]).)*$ ***!
  \******************************************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

eval("var map = {\n\t\"./Oauth2Provider\": \"./client/component/totara_oauth2/src/pages/Oauth2Provider.vue\",\n\t\"./Oauth2Provider.vue\": \"./client/component/totara_oauth2/src/pages/Oauth2Provider.vue\"\n};\n\n\nfunction webpackContext(req) {\n\tvar id = webpackContextResolve(req);\n\treturn __webpack_require__(id);\n}\nfunction webpackContextResolve(req) {\n\tif(!__webpack_require__.o(map, req)) {\n\t\tvar e = new Error(\"Cannot find module '\" + req + \"'\");\n\t\te.code = 'MODULE_NOT_FOUND';\n\t\tthrow e;\n\t}\n\treturn map[req];\n}\nwebpackContext.keys = function webpackContextKeys() {\n\treturn Object.keys(map);\n};\nwebpackContext.resolve = webpackContextResolve;\nmodule.exports = webpackContext;\nwebpackContext.id = \"./client/component/totara_oauth2/src/pages sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\";\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/pages/_sync_^(?");

/***/ }),

/***/ "./server/totara/oauth2/webapi/ajax/client_providers.graphql":
/*!*******************************************************************!*\
  !*** ./server/totara/oauth2/webapi/ajax/client_providers.graphql ***!
  \*******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n\n    var doc = {\"kind\":\"Document\",\"definitions\":[{\"kind\":\"OperationDefinition\",\"operation\":\"query\",\"name\":{\"kind\":\"Name\",\"value\":\"totara_oauth2_client_providers\"},\"variableDefinitions\":[{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"input\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"totara_oauth2_client_providers_input\"}}},\"directives\":[]}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"alias\":{\"kind\":\"Name\",\"value\":\"providers\"},\"name\":{\"kind\":\"Name\",\"value\":\"totara_oauth2_client_providers\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"input\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"input\"}}}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"items\"},\"arguments\":[],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"id\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"client_id\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"client_secret\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"name\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"format\"},\"value\":{\"kind\":\"EnumValue\",\"value\":\"PLAIN\"}}],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"description\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"format\"},\"value\":{\"kind\":\"EnumValue\",\"value\":\"HTML\"}}],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"scope\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"detail_scope\"},\"arguments\":[],\"directives\":[]}]}}]}}]}}]};\n    /* harmony default export */ __webpack_exports__[\"default\"] = (doc);\n  \n\n//# sourceURL=webpack:///./server/totara/oauth2/webapi/ajax/client_providers.graphql?");

/***/ }),

/***/ "./server/totara/oauth2/webapi/ajax/create_provider.graphql":
/*!******************************************************************!*\
  !*** ./server/totara/oauth2/webapi/ajax/create_provider.graphql ***!
  \******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n\n    var doc = {\"kind\":\"Document\",\"definitions\":[{\"kind\":\"OperationDefinition\",\"operation\":\"mutation\",\"name\":{\"kind\":\"Name\",\"value\":\"totara_oauth2_create_provider\"},\"variableDefinitions\":[{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"input\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"totara_oauth2_provider_input\"}}},\"directives\":[]}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"alias\":{\"kind\":\"Name\",\"value\":\"provider\"},\"name\":{\"kind\":\"Name\",\"value\":\"totara_oauth2_create_provider\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"input\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"input\"}}}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"id\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"client_id\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"client_secret\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"name\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"format\"},\"value\":{\"kind\":\"EnumValue\",\"value\":\"PLAIN\"}}],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"description\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"format\"},\"value\":{\"kind\":\"EnumValue\",\"value\":\"HTML\"}}],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"scope\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"detail_scope\"},\"arguments\":[],\"directives\":[]}]}}]}}]};\n    /* harmony default export */ __webpack_exports__[\"default\"] = (doc);\n  \n\n//# sourceURL=webpack:///./server/totara/oauth2/webapi/ajax/create_provider.graphql?");

/***/ }),

/***/ "./server/totara/oauth2/webapi/ajax/delete_provider.graphql":
/*!******************************************************************!*\
  !*** ./server/totara/oauth2/webapi/ajax/delete_provider.graphql ***!
  \******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n\n    var doc = {\"kind\":\"Document\",\"definitions\":[{\"kind\":\"OperationDefinition\",\"operation\":\"mutation\",\"name\":{\"kind\":\"Name\",\"value\":\"totara_oauth2_delete_provider\"},\"variableDefinitions\":[{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"id\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"core_id\"}}},\"directives\":[]}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"alias\":{\"kind\":\"Name\",\"value\":\"result\"},\"name\":{\"kind\":\"Name\",\"value\":\"totara_oauth2_delete_provider\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"id\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"id\"}}}],\"directives\":[]}]}}]};\n    /* harmony default export */ __webpack_exports__[\"default\"] = (doc);\n  \n\n//# sourceURL=webpack:///./server/totara/oauth2/webapi/ajax/delete_provider.graphql?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/tui.json":
/*!*****************************************************!*\
  !*** ./client/component/totara_oauth2/src/tui.json ***!
  \*****************************************************/
/***/ (function(__unused_webpack_module, __unused_webpack_exports, __webpack_require__) {

eval("!function() {\n\"use strict\";\n\nif (typeof tui !== 'undefined' && tui._bundle.isLoaded(\"totara_oauth2\")) {\n  console.warn(\n    '[tui bundle] The bundle \"' + \"totara_oauth2\" +\n    '\" is already loaded, skipping initialisation.'\n  );\n  return;\n};\ntui._bundle.register(\"totara_oauth2\")\ntui._bundle.addModulesFromContext(\"totara_oauth2/components\", __webpack_require__(\"./client/component/totara_oauth2/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\"));\ntui._bundle.addModulesFromContext(\"totara_oauth2/pages\", __webpack_require__(\"./client/component/totara_oauth2/src/pages sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\"));\n}();\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/tui.json?");

/***/ }),

/***/ "./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* export default binding */ __WEBPACK_DEFAULT_EXPORT__; }\n/* harmony export */ });\n/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(component) {\n        component.options.__langStrings = \n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n{\n  \"totara_oauth2\": [\n    \"oauth_url_desc\",\n    \"oauth_url_title\",\n    \"xapi_url_desc\"\n  ]\n}\n;\n    }\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* export default binding */ __WEBPACK_DEFAULT_EXPORT__; }\n/* harmony export */ });\n/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(component) {\n        component.options.__langStrings = \n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n{\n  \"core\": [\n    \"delete\"\n  ],\n  \"totara_oauth2\": [\n    \"actions_for\",\n    \"delete_provider_name\"\n  ]\n}\n;\n    }\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* export default binding */ __WEBPACK_DEFAULT_EXPORT__; }\n/* harmony export */ });\n/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(component) {\n        component.options.__langStrings = \n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n{\n  \"totara_oauth2\": [\n    \"add_provider\",\n    \"description\",\n    \"required_fields\",\n    \"scopes\",\n    \"xapi_write\"\n  ],\n  \"core\": [\n    \"name\"\n  ]\n}\n;\n    }\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* export default binding */ __WEBPACK_DEFAULT_EXPORT__; }\n/* harmony export */ });\n/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(component) {\n        component.options.__langStrings = \n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n{\n  \"totara_oauth2\": [\n    \"add_provider\",\n    \"add_oauth2_provider\",\n    \"client_provider_description\",\n    \"client_id\",\n    \"client_secret\",\n    \"continue\",\n    \"delete_confirm_body\",\n    \"delete_confirm_title\",\n    \"delete_modal_title\",\n    \"delete_success\",\n    \"scopes\",\n    \"no_record_found\",\n    \"oauth2providerdetails\",\n    \"provider_added\",\n    \"show\",\n    \"show_client_secret_aria\",\n    \"hide\",\n    \"hide_client_secret_aria\"\n  ]\n}\n;\n    }\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue":
/*!*********************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue ***!
  \*********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _Oauth2ProviderContent_vue_vue_type_template_id_037c9a63___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Oauth2ProviderContent.vue?vue&type=template&id=037c9a63& */ \"./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=template&id=037c9a63&\");\n/* harmony import */ var _Oauth2ProviderContent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Oauth2ProviderContent.vue?vue&type=script&lang=js& */ \"./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _Oauth2ProviderContent_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Oauth2ProviderContent.vue?vue&type=style&index=0&lang=scss& */ \"./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=style&index=0&lang=scss&\");\n/* harmony import */ var _Oauth2ProviderContent_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_Oauth2ProviderContent_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n/* harmony import */ var _Oauth2ProviderContent_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Oauth2ProviderContent.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=custom&index=0&blockType=lang-strings\");\n\n\n\n;\n\n\n/* normalize component */\n\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(\n  _Oauth2ProviderContent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _Oauth2ProviderContent_vue_vue_type_template_id_037c9a63___WEBPACK_IMPORTED_MODULE_0__.render,\n  _Oauth2ProviderContent_vue_vue_type_template_id_037c9a63___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\n/* custom blocks */\n;\nif (typeof _Oauth2ProviderContent_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"] === 'function') (0,_Oauth2ProviderContent_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"])(component)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue\"\n/* harmony default export */ __webpack_exports__[\"default\"] = (component.exports);\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue":
/*!***************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue ***!
  \***************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _Oauth2ProviderAction_vue_vue_type_template_id_3f76ccf1___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Oauth2ProviderAction.vue?vue&type=template&id=3f76ccf1& */ \"./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=template&id=3f76ccf1&\");\n/* harmony import */ var _Oauth2ProviderAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Oauth2ProviderAction.vue?vue&type=script&lang=js& */ \"./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n/* harmony import */ var _Oauth2ProviderAction_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Oauth2ProviderAction.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=custom&index=0&blockType=lang-strings\");\n\n\n\n\n\n/* normalize component */\n;\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(\n  _Oauth2ProviderAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _Oauth2ProviderAction_vue_vue_type_template_id_3f76ccf1___WEBPACK_IMPORTED_MODULE_0__.render,\n  _Oauth2ProviderAction_vue_vue_type_template_id_3f76ccf1___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\n/* custom blocks */\n;\nif (typeof _Oauth2ProviderAction_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_3__[\"default\"] === 'function') (0,_Oauth2ProviderAction_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(component)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue\"\n/* harmony default export */ __webpack_exports__[\"default\"] = (component.exports);\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue":
/*!*************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _Oauth2ProviderModal_vue_vue_type_template_id_42694335___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Oauth2ProviderModal.vue?vue&type=template&id=42694335& */ \"./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=template&id=42694335&\");\n/* harmony import */ var _Oauth2ProviderModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Oauth2ProviderModal.vue?vue&type=script&lang=js& */ \"./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _Oauth2ProviderModal_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Oauth2ProviderModal.vue?vue&type=style&index=0&lang=scss& */ \"./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=style&index=0&lang=scss&\");\n/* harmony import */ var _Oauth2ProviderModal_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_Oauth2ProviderModal_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n/* harmony import */ var _Oauth2ProviderModal_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Oauth2ProviderModal.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=custom&index=0&blockType=lang-strings\");\n\n\n\n;\n\n\n/* normalize component */\n\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(\n  _Oauth2ProviderModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _Oauth2ProviderModal_vue_vue_type_template_id_42694335___WEBPACK_IMPORTED_MODULE_0__.render,\n  _Oauth2ProviderModal_vue_vue_type_template_id_42694335___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\n/* custom blocks */\n;\nif (typeof _Oauth2ProviderModal_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"] === 'function') (0,_Oauth2ProviderModal_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"])(component)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue\"\n/* harmony default export */ __webpack_exports__[\"default\"] = (component.exports);\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/pages/Oauth2Provider.vue":
/*!*********************************************************************!*\
  !*** ./client/component/totara_oauth2/src/pages/Oauth2Provider.vue ***!
  \*********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _Oauth2Provider_vue_vue_type_template_id_0ed067c6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Oauth2Provider.vue?vue&type=template&id=0ed067c6& */ \"./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=template&id=0ed067c6&\");\n/* harmony import */ var _Oauth2Provider_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Oauth2Provider.vue?vue&type=script&lang=js& */ \"./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _Oauth2Provider_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Oauth2Provider.vue?vue&type=style&index=0&lang=scss& */ \"./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=style&index=0&lang=scss&\");\n/* harmony import */ var _Oauth2Provider_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_Oauth2Provider_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n/* harmony import */ var _Oauth2Provider_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Oauth2Provider.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=custom&index=0&blockType=lang-strings\");\n\n\n\n;\n\n\n/* normalize component */\n\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(\n  _Oauth2Provider_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _Oauth2Provider_vue_vue_type_template_id_0ed067c6___WEBPACK_IMPORTED_MODULE_0__.render,\n  _Oauth2Provider_vue_vue_type_template_id_0ed067c6___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\n/* custom blocks */\n;\nif (typeof _Oauth2Provider_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"] === 'function') (0,_Oauth2Provider_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"])(component)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/totara_oauth2/src/pages/Oauth2Provider.vue\"\n/* harmony default export */ __webpack_exports__[\"default\"] = (component.exports);\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?");

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var tui_components_form_Form__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tui/components/form/Form */ \"tui/components/form/Form\");\n/* harmony import */ var tui_components_form_Form__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(tui_components_form_Form__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var tui_components_form_InputSizedText__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! tui/components/form/InputSizedText */ \"tui/components/form/InputSizedText\");\n/* harmony import */ var tui_components_form_InputSizedText__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(tui_components_form_InputSizedText__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var tui_config__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! tui/config */ \"tui/config\");\n/* harmony import */ var tui_config__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(tui_config__WEBPACK_IMPORTED_MODULE_2__);\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n\n\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  components: {\n    Form: (tui_components_form_Form__WEBPACK_IMPORTED_MODULE_0___default()),\n    InputSizedText: (tui_components_form_InputSizedText__WEBPACK_IMPORTED_MODULE_1___default())\n  },\n  methods: {\n    getOauthUrl: function getOauthUrl() {\n      return tui_config__WEBPACK_IMPORTED_MODULE_2__.config.wwwroot + '/totara/oauth2/token.php';\n    },\n    getXapiUrl: function getXapiUrl() {\n      return tui_config__WEBPACK_IMPORTED_MODULE_2__.config.wwwroot + '/totara/xapi/receiver.php';\n    }\n  }\n});\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var tui_components_dropdown_Dropdown__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tui/components/dropdown/Dropdown */ \"tui/components/dropdown/Dropdown\");\n/* harmony import */ var tui_components_dropdown_Dropdown__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(tui_components_dropdown_Dropdown__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var tui_components_buttons_MoreIcon__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! tui/components/buttons/MoreIcon */ \"tui/components/buttons/MoreIcon\");\n/* harmony import */ var tui_components_buttons_MoreIcon__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(tui_components_buttons_MoreIcon__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var tui_components_dropdown_DropdownItem__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! tui/components/dropdown/DropdownItem */ \"tui/components/dropdown/DropdownItem\");\n/* harmony import */ var tui_components_dropdown_DropdownItem__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(tui_components_dropdown_DropdownItem__WEBPACK_IMPORTED_MODULE_2__);\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n\n\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  components: {\n    Dropdown: (tui_components_dropdown_Dropdown__WEBPACK_IMPORTED_MODULE_0___default()),\n    MoreIcon: (tui_components_buttons_MoreIcon__WEBPACK_IMPORTED_MODULE_1___default()),\n    DropdownItem: (tui_components_dropdown_DropdownItem__WEBPACK_IMPORTED_MODULE_2___default())\n  },\n  props: {\n    providerName: {\n      type: String,\n      required: true\n    }\n  }\n});\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var tui_components_buttons_ButtonGroup__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tui/components/buttons/ButtonGroup */ \"tui/components/buttons/ButtonGroup\");\n/* harmony import */ var tui_components_buttons_ButtonGroup__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(tui_components_buttons_ButtonGroup__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! tui/components/buttons/Button */ \"tui/components/buttons/Button\");\n/* harmony import */ var tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var tui_components_form_FormRow__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! tui/components/form/FormRow */ \"tui/components/form/FormRow\");\n/* harmony import */ var tui_components_form_FormRow__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(tui_components_form_FormRow__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var tui_components_buttons_Cancel__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! tui/components/buttons/Cancel */ \"tui/components/buttons/Cancel\");\n/* harmony import */ var tui_components_buttons_Cancel__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(tui_components_buttons_Cancel__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var tui_components_form_Checkbox__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! tui/components/form/Checkbox */ \"tui/components/form/Checkbox\");\n/* harmony import */ var tui_components_form_Checkbox__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(tui_components_form_Checkbox__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var tui_components_uniform__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! tui/components/uniform */ \"tui/components/uniform\");\n/* harmony import */ var tui_components_uniform__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(tui_components_uniform__WEBPACK_IMPORTED_MODULE_5__);\n/* harmony import */ var tui_components_modal_Modal__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! tui/components/modal/Modal */ \"tui/components/modal/Modal\");\n/* harmony import */ var tui_components_modal_Modal__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(tui_components_modal_Modal__WEBPACK_IMPORTED_MODULE_6__);\n/* harmony import */ var tui_components_modal_ModalContent__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! tui/components/modal/ModalContent */ \"tui/components/modal/ModalContent\");\n/* harmony import */ var tui_components_modal_ModalContent__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(tui_components_modal_ModalContent__WEBPACK_IMPORTED_MODULE_7__);\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n\n\n\n\n\n\n\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  components: {\n    ButtonGroup: (tui_components_buttons_ButtonGroup__WEBPACK_IMPORTED_MODULE_0___default()),\n    Button: (tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_1___default()),\n    Checkbox: (tui_components_form_Checkbox__WEBPACK_IMPORTED_MODULE_4___default()),\n    Cancel: (tui_components_buttons_Cancel__WEBPACK_IMPORTED_MODULE_3___default()),\n    FormRow: (tui_components_form_FormRow__WEBPACK_IMPORTED_MODULE_2___default()),\n    FormText: tui_components_uniform__WEBPACK_IMPORTED_MODULE_5__.FormText,\n    Modal: (tui_components_modal_Modal__WEBPACK_IMPORTED_MODULE_6___default()),\n    ModalContent: (tui_components_modal_ModalContent__WEBPACK_IMPORTED_MODULE_7___default()),\n    Uniform: tui_components_uniform__WEBPACK_IMPORTED_MODULE_5__.Uniform,\n    FormTextarea: tui_components_uniform__WEBPACK_IMPORTED_MODULE_5__.FormTextarea\n  },\n  props: {\n    title: {\n      type: String,\n      required: true\n    },\n    showCloseButton: {\n      type: Boolean,\n      \"default\": true\n    },\n    isSaving: {\n      type: Boolean,\n      \"default\": false\n    }\n  },\n  data: function data() {\n    return {\n      initialValues: {\n        name: '',\n        xapi_write: 'XAPI_WRITE',\n        description: ''\n      },\n      formValues: null\n    };\n  },\n  methods: {\n    /**\n     *\n     * @param {String} field\n     * @param {Int} defaultRow\n     * @param {Int} maxRow\n     *\n     **/\n    setRows: function setRows(field, defaultRow, maxRow) {\n      var text = '';\n\n      if (this.formValues && field in this.formValues) {\n        text = this.formValues[field];\n      } else if (this.initialValues && field in this.initialValues) {\n        text = this.initialValues[field];\n      }\n\n      var row = (text.match(/\\n/g) || []).length + 1;\n\n      if (row < defaultRow) {\n        return defaultRow;\n      }\n\n      if (row > maxRow) {\n        return maxRow;\n      }\n\n      return row;\n    }\n  }\n});\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ \"./node_modules/@babel/runtime/helpers/esm/defineProperty.js\");\n/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ \"./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js\");\n/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ \"./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js\");\n/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/regenerator */ \"./node_modules/@babel/runtime/regenerator/index.js\");\n/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! tui/components/buttons/Button */ \"tui/components/buttons/Button\");\n/* harmony import */ var tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var tui_components_collapsible_Collapsible__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! tui/components/collapsible/Collapsible */ \"tui/components/collapsible/Collapsible\");\n/* harmony import */ var tui_components_collapsible_Collapsible__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(tui_components_collapsible_Collapsible__WEBPACK_IMPORTED_MODULE_5__);\n/* harmony import */ var tui_components_modal_ConfirmationModal__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! tui/components/modal/ConfirmationModal */ \"tui/components/modal/ConfirmationModal\");\n/* harmony import */ var tui_components_modal_ConfirmationModal__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(tui_components_modal_ConfirmationModal__WEBPACK_IMPORTED_MODULE_6__);\n/* harmony import */ var tui_components_form_Form__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! tui/components/form/Form */ \"tui/components/form/Form\");\n/* harmony import */ var tui_components_form_Form__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(tui_components_form_Form__WEBPACK_IMPORTED_MODULE_7__);\n/* harmony import */ var tui_components_form_FormRow__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! tui/components/form/FormRow */ \"tui/components/form/FormRow\");\n/* harmony import */ var tui_components_form_FormRow__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(tui_components_form_FormRow__WEBPACK_IMPORTED_MODULE_8__);\n/* harmony import */ var tui_components_layouts_LayoutOneColumn__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! tui/components/layouts/LayoutOneColumn */ \"tui/components/layouts/LayoutOneColumn\");\n/* harmony import */ var tui_components_layouts_LayoutOneColumn__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(tui_components_layouts_LayoutOneColumn__WEBPACK_IMPORTED_MODULE_9__);\n/* harmony import */ var tui_components_modal_ModalPresenter__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! tui/components/modal/ModalPresenter */ \"tui/components/modal/ModalPresenter\");\n/* harmony import */ var tui_components_modal_ModalPresenter__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(tui_components_modal_ModalPresenter__WEBPACK_IMPORTED_MODULE_10__);\n/* harmony import */ var totara_oauth2_components_Oauth2ProviderContent__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! totara_oauth2/components/Oauth2ProviderContent */ \"totara_oauth2/components/Oauth2ProviderContent\");\n/* harmony import */ var totara_oauth2_components_Oauth2ProviderContent__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(totara_oauth2_components_Oauth2ProviderContent__WEBPACK_IMPORTED_MODULE_11__);\n/* harmony import */ var totara_oauth2_components_modal_Oauth2ProviderModal__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! totara_oauth2/components/modal/Oauth2ProviderModal */ \"totara_oauth2/components/modal/Oauth2ProviderModal\");\n/* harmony import */ var totara_oauth2_components_modal_Oauth2ProviderModal__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(totara_oauth2_components_modal_Oauth2ProviderModal__WEBPACK_IMPORTED_MODULE_12__);\n/* harmony import */ var totara_oauth2_components_action_Oauth2ProviderAction__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! totara_oauth2/components/action/Oauth2ProviderAction */ \"totara_oauth2/components/action/Oauth2ProviderAction\");\n/* harmony import */ var totara_oauth2_components_action_Oauth2ProviderAction__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(totara_oauth2_components_action_Oauth2ProviderAction__WEBPACK_IMPORTED_MODULE_13__);\n/* harmony import */ var tui_components_form_InputGroup__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! tui/components/form/InputGroup */ \"tui/components/form/InputGroup\");\n/* harmony import */ var tui_components_form_InputGroup__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(tui_components_form_InputGroup__WEBPACK_IMPORTED_MODULE_14__);\n/* harmony import */ var tui_components_form_InputGroupInput__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! tui/components/form/InputGroupInput */ \"tui/components/form/InputGroupInput\");\n/* harmony import */ var tui_components_form_InputGroupInput__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(tui_components_form_InputGroupInput__WEBPACK_IMPORTED_MODULE_15__);\n/* harmony import */ var tui_components_form_InputGroupButton__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! tui/components/form/InputGroupButton */ \"tui/components/form/InputGroupButton\");\n/* harmony import */ var tui_components_form_InputGroupButton__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(tui_components_form_InputGroupButton__WEBPACK_IMPORTED_MODULE_16__);\n/* harmony import */ var tui_notifications__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! tui/notifications */ \"tui/notifications\");\n/* harmony import */ var tui_notifications__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(tui_notifications__WEBPACK_IMPORTED_MODULE_17__);\n/* harmony import */ var totara_oauth2_graphql_client_providers__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! totara_oauth2/graphql/client_providers */ \"./server/totara/oauth2/webapi/ajax/client_providers.graphql\");\n/* harmony import */ var totara_oauth2_graphql_create_provider__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! totara_oauth2/graphql/create_provider */ \"./server/totara/oauth2/webapi/ajax/create_provider.graphql\");\n/* harmony import */ var totara_oauth2_graphql_delete_provider__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! totara_oauth2/graphql/delete_provider */ \"./server/totara/oauth2/webapi/ajax/delete_provider.graphql\");\n\n\n\n\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n\n\n\n\n\n\n\n\n\n\n\n\n // GraphQL\n\n\n\n\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  components: {\n    Button: (tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_4___default()),\n    Collapsible: (tui_components_collapsible_Collapsible__WEBPACK_IMPORTED_MODULE_5___default()),\n    DeleteConfirmationModal: (tui_components_modal_ConfirmationModal__WEBPACK_IMPORTED_MODULE_6___default()),\n    FormRow: (tui_components_form_FormRow__WEBPACK_IMPORTED_MODULE_8___default()),\n    Form: (tui_components_form_Form__WEBPACK_IMPORTED_MODULE_7___default()),\n    Layout: (tui_components_layouts_LayoutOneColumn__WEBPACK_IMPORTED_MODULE_9___default()),\n    ModalPresenter: (tui_components_modal_ModalPresenter__WEBPACK_IMPORTED_MODULE_10___default()),\n    Oauth2ProviderModal: (totara_oauth2_components_modal_Oauth2ProviderModal__WEBPACK_IMPORTED_MODULE_12___default()),\n    Oauth2ProviderContent: (totara_oauth2_components_Oauth2ProviderContent__WEBPACK_IMPORTED_MODULE_11___default()),\n    Oauth2ProviderAction: (totara_oauth2_components_action_Oauth2ProviderAction__WEBPACK_IMPORTED_MODULE_13___default()),\n    InputGroup: (tui_components_form_InputGroup__WEBPACK_IMPORTED_MODULE_14___default()),\n    InputGroupInput: (tui_components_form_InputGroupInput__WEBPACK_IMPORTED_MODULE_15___default()),\n    InputGroupButton: (tui_components_form_InputGroupButton__WEBPACK_IMPORTED_MODULE_16___default())\n  },\n  data: function data() {\n    return {\n      providers: [],\n      modalOpen: false,\n      deleteModalOpen: false,\n      deleting: false,\n      isSaving: false,\n      expanded: {},\n      targetProvider: {},\n      showSecret: {}\n    };\n  },\n  computed: {\n    hasNoRecordError: function hasNoRecordError() {\n      return this.providers.length === 0;\n    }\n  },\n  apollo: {\n    providers: {\n      query: totara_oauth2_graphql_client_providers__WEBPACK_IMPORTED_MODULE_18__[\"default\"],\n      variables: function variables() {\n        return {\n          input: {}\n        };\n      },\n      update: function update(_ref) {\n        var items = _ref.providers.items;\n        return items;\n      }\n    }\n  },\n  created: function created() {\n    var _this = this;\n\n    this.providers.forEach(function (provider) {\n      return _this.expanded[provider.id] = false;\n    });\n  },\n  methods: {\n    /**\n     *\n     * @param {Object} formValue\n     */\n    createProvider: function createProvider(formValue) {\n      var _this2 = this;\n\n      return (0,_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__[\"default\"])( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3___default().mark(function _callee() {\n        var _yield$_this2$$apollo, provider;\n\n        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3___default().wrap(function _callee$(_context) {\n          while (1) {\n            switch (_context.prev = _context.next) {\n              case 0:\n                _this2.isSaving = true;\n                _context.prev = 1;\n                _context.next = 4;\n                return _this2.$apollo.mutate({\n                  mutation: totara_oauth2_graphql_create_provider__WEBPACK_IMPORTED_MODULE_19__[\"default\"],\n                  variables: {\n                    input: {\n                      name: formValue.name,\n                      description: formValue.description,\n                      scope_type: formValue.xapi_write\n                    }\n                  },\n                  update: function update(proxy, _ref3) {\n                    var provider = _ref3.data.provider;\n                    var variables = {\n                      input: {}\n                    };\n\n                    var _proxy$readQuery = proxy.readQuery({\n                      query: totara_oauth2_graphql_client_providers__WEBPACK_IMPORTED_MODULE_18__[\"default\"],\n                      variables: variables\n                    }),\n                        items = _proxy$readQuery.providers.items;\n\n                    var innerProviders = (0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(items);\n\n                    if (provider) {\n                      innerProviders.push(provider);\n                    }\n\n                    proxy.writeQuery({\n                      query: totara_oauth2_graphql_client_providers__WEBPACK_IMPORTED_MODULE_18__[\"default\"],\n                      variables: variables,\n                      data: {\n                        providers: {\n                          items: innerProviders.sort(function (p1, p2) {\n                            return p1.name.localeCompare(p2.name);\n                          })\n                        }\n                      }\n                    });\n                  }\n                });\n\n              case 4:\n                _yield$_this2$$apollo = _context.sent;\n                provider = _yield$_this2$$apollo.data.provider;\n\n                if (!provider) {\n                  _context.next = 12;\n                  break;\n                }\n\n                _this2.providers.forEach(function (p) {\n                  return _this2.expanded[p.id] = false;\n                });\n\n                _this2.modalOpen = false;\n                _this2.expanded[provider.id] = true;\n                _context.next = 12;\n                return (0,tui_notifications__WEBPACK_IMPORTED_MODULE_17__.notify)({\n                  message: _this2.$str('provider_added', 'totara_oauth2'),\n                  type: 'success'\n                });\n\n              case 12:\n                _context.prev = 12;\n                _this2.isSaving = false;\n                return _context.finish(12);\n\n              case 15:\n              case \"end\":\n                return _context.stop();\n            }\n          }\n        }, _callee, null, [[1,, 12, 15]]);\n      }))();\n    },\n\n    /**\n     * @param {Int} id\n     * @param {Boolean} value\n     */\n    handleCollapsibleChange: function handleCollapsibleChange(value, id) {\n      this.expanded = Object.assign({}, this.expanded, (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__[\"default\"])({}, id, value));\n    },\n\n    /**\n     *\n     * @param {Object} provider\n     */\n    openDeleteModal: function openDeleteModal(provider) {\n      this.targetProvider = provider;\n      this.deleteModalOpen = true;\n    },\n    deleteProvider: function deleteProvider() {\n      var _this3 = this;\n\n      return (0,_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__[\"default\"])( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3___default().mark(function _callee2() {\n        var _yield$_this3$$apollo, result;\n\n        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3___default().wrap(function _callee2$(_context2) {\n          while (1) {\n            switch (_context2.prev = _context2.next) {\n              case 0:\n                if (!(!_this3.deleteModalOpen || !_this3.targetProvider)) {\n                  _context2.next = 2;\n                  break;\n                }\n\n                return _context2.abrupt(\"return\");\n\n              case 2:\n                _context2.prev = 2;\n                _this3.deleting = true;\n                _context2.next = 6;\n                return _this3.$apollo.mutate({\n                  mutation: totara_oauth2_graphql_delete_provider__WEBPACK_IMPORTED_MODULE_20__[\"default\"],\n                  variables: {\n                    id: _this3.targetProvider.id\n                  },\n                  update: function update(proxy) {\n                    var variables = {\n                      input: {}\n                    };\n\n                    var _proxy$readQuery2 = proxy.readQuery({\n                      query: totara_oauth2_graphql_client_providers__WEBPACK_IMPORTED_MODULE_18__[\"default\"],\n                      variables: variables\n                    }),\n                        items = _proxy$readQuery2.providers.items;\n\n                    var innerProviders = (0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(items);\n\n                    proxy.writeQuery({\n                      query: totara_oauth2_graphql_client_providers__WEBPACK_IMPORTED_MODULE_18__[\"default\"],\n                      variables: variables,\n                      data: {\n                        providers: {\n                          items: innerProviders.filter(function (p) {\n                            return p.id !== _this3.targetProvider.id;\n                          })\n                        }\n                      }\n                    });\n                  }\n                });\n\n              case 6:\n                _yield$_this3$$apollo = _context2.sent;\n                result = _yield$_this3$$apollo.data.result;\n\n                if (result) {\n                  (0,tui_notifications__WEBPACK_IMPORTED_MODULE_17__.notify)({\n                    type: 'success',\n                    message: _this3.$str('delete_success', 'totara_oauth2')\n                  });\n                }\n\n              case 9:\n                _context2.prev = 9;\n                _this3.deleteModalOpen = false;\n                _this3.deleting = false;\n                return _context2.finish(9);\n\n              case 13:\n              case \"end\":\n                return _context2.stop();\n            }\n          }\n        }, _callee2, null, [[2,, 9, 13]]);\n      }))();\n    },\n\n    /**\n     * @param {number} id\n     */\n    toggleSecretVisibility: function toggleSecretVisibility(id) {\n      this.$set(this.showSecret, id, !this.showSecret[id]);\n    },\n\n    /**\n     * @param {number} id\n     */\n    showHideText: function showHideText(id) {\n      return this.showSecret[id] ? this.$str('hide', 'totara_oauth2') : this.$str('show', 'totara_oauth2');\n    },\n\n    /**\n     * @param {object} provider\n     */\n    showHideAriaLabel: function showHideAriaLabel(provider) {\n      return this.showSecret[provider.id] ? this.$str('hide_client_secret_aria', 'totara_oauth2', provider.name) : this.$str('show_client_secret_aria', 'totara_oauth2', provider.name);\n    }\n  }\n});\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js":
/*!********************************************************************!*\
  !*** ./node_modules/vue-loader/lib/runtime/componentNormalizer.js ***!
  \********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ normalizeComponent; }\n/* harmony export */ });\n/* globals __VUE_SSR_CONTEXT__ */\n\n// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).\n// This module is a runtime utility for cleaner component module output and will\n// be included in the final webpack user bundle.\n\nfunction normalizeComponent (\n  scriptExports,\n  render,\n  staticRenderFns,\n  functionalTemplate,\n  injectStyles,\n  scopeId,\n  moduleIdentifier, /* server only */\n  shadowMode /* vue-cli only */\n) {\n  // Vue.extend constructor export interop\n  var options = typeof scriptExports === 'function'\n    ? scriptExports.options\n    : scriptExports\n\n  // render functions\n  if (render) {\n    options.render = render\n    options.staticRenderFns = staticRenderFns\n    options._compiled = true\n  }\n\n  // functional template\n  if (functionalTemplate) {\n    options.functional = true\n  }\n\n  // scopedId\n  if (scopeId) {\n    options._scopeId = 'data-v-' + scopeId\n  }\n\n  var hook\n  if (moduleIdentifier) { // server build\n    hook = function (context) {\n      // 2.3 injection\n      context =\n        context || // cached call\n        (this.$vnode && this.$vnode.ssrContext) || // stateful\n        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional\n      // 2.2 with runInNewContext: true\n      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {\n        context = __VUE_SSR_CONTEXT__\n      }\n      // inject component styles\n      if (injectStyles) {\n        injectStyles.call(this, context)\n      }\n      // register component module identifier for async chunk inferrence\n      if (context && context._registeredComponents) {\n        context._registeredComponents.add(moduleIdentifier)\n      }\n    }\n    // used by ssr in case component is cached and beforeCreate\n    // never gets called\n    options._ssrRegister = hook\n  } else if (injectStyles) {\n    hook = shadowMode\n      ? function () {\n        injectStyles.call(\n          this,\n          (options.functional ? this.parent : this).$root.$options.shadowRoot\n        )\n      }\n      : injectStyles\n  }\n\n  if (hook) {\n    if (options.functional) {\n      // for template-only hot-reload because in that case the render fn doesn't\n      // go through the normalizer\n      options._injectStyles = hook\n      // register for functional component in vue file\n      var originalRender = options.render\n      options.render = function renderWithStyleInjection (h, context) {\n        hook.call(context)\n        return originalRender(h, context)\n      }\n    } else {\n      // inject component registration as beforeCreate hook\n      var existing = options.beforeCreate\n      options.beforeCreate = existing\n        ? [].concat(existing, hook)\n        : [hook]\n    }\n  }\n\n  return {\n    exports: scriptExports,\n    options: options\n  }\n}\n\n\n//# sourceURL=webpack:///./node_modules/vue-loader/lib/runtime/componentNormalizer.js?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!********************************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1552_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderContent_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Oauth2ProviderContent.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=custom&index=0&blockType=lang-strings\");\n /* harmony default export */ __webpack_exports__[\"default\"] = (_tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1552_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderContent_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!**************************************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \**************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1552_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderAction_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Oauth2ProviderAction.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=custom&index=0&blockType=lang-strings\");\n /* harmony default export */ __webpack_exports__[\"default\"] = (_tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1552_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderAction_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!************************************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1552_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderModal_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Oauth2ProviderModal.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=custom&index=0&blockType=lang-strings\");\n /* harmony default export */ __webpack_exports__[\"default\"] = (_tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1552_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderModal_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!********************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1552_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2Provider_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Oauth2Provider.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1552[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=custom&index=0&blockType=lang-strings\");\n /* harmony default export */ __webpack_exports__[\"default\"] = (_tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1552_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2Provider_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=template&id=037c9a63&":
/*!****************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=template&id=037c9a63& ***!
  \****************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderContent_vue_vue_type_template_id_037c9a63___WEBPACK_IMPORTED_MODULE_0__.render; },\n/* harmony export */   \"staticRenderFns\": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderContent_vue_vue_type_template_id_037c9a63___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderContent_vue_vue_type_template_id_037c9a63___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Oauth2ProviderContent.vue?vue&type=template&id=037c9a63& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=template&id=037c9a63&\");\n\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=template&id=3f76ccf1&":
/*!**********************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=template&id=3f76ccf1& ***!
  \**********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderAction_vue_vue_type_template_id_3f76ccf1___WEBPACK_IMPORTED_MODULE_0__.render; },\n/* harmony export */   \"staticRenderFns\": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderAction_vue_vue_type_template_id_3f76ccf1___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderAction_vue_vue_type_template_id_3f76ccf1___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Oauth2ProviderAction.vue?vue&type=template&id=3f76ccf1& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=template&id=3f76ccf1&\");\n\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=template&id=42694335&":
/*!********************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=template&id=42694335& ***!
  \********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderModal_vue_vue_type_template_id_42694335___WEBPACK_IMPORTED_MODULE_0__.render; },\n/* harmony export */   \"staticRenderFns\": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderModal_vue_vue_type_template_id_42694335___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2ProviderModal_vue_vue_type_template_id_42694335___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Oauth2ProviderModal.vue?vue&type=template&id=42694335& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=template&id=42694335&\");\n\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=template&id=0ed067c6&":
/*!****************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=template&id=0ed067c6& ***!
  \****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2Provider_vue_vue_type_template_id_0ed067c6___WEBPACK_IMPORTED_MODULE_0__.render; },\n/* harmony export */   \"staticRenderFns\": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2Provider_vue_vue_type_template_id_0ed067c6___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Oauth2Provider_vue_vue_type_template_id_0ed067c6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Oauth2Provider.vue?vue&type=template&id=0ed067c6& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=template&id=0ed067c6&\");\n\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_1547_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Oauth2ProviderContent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./Oauth2ProviderContent.vue?vue&type=script&lang=js& */ \"./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=script&lang=js&\");\n /* harmony default export */ __webpack_exports__[\"default\"] = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_1547_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Oauth2ProviderContent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_1547_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Oauth2ProviderAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./Oauth2ProviderAction.vue?vue&type=script&lang=js& */ \"./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=script&lang=js&\");\n /* harmony default export */ __webpack_exports__[\"default\"] = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_1547_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Oauth2ProviderAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_1547_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Oauth2ProviderModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./Oauth2ProviderModal.vue?vue&type=script&lang=js& */ \"./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=script&lang=js&\");\n /* harmony default export */ __webpack_exports__[\"default\"] = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_1547_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Oauth2ProviderModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_1547_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Oauth2Provider_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./Oauth2Provider.vue?vue&type=script&lang=js& */ \"./node_modules/babel-loader/lib/index.js??clonedRuleSet-1547[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=script&lang=js&\");\n /* harmony default export */ __webpack_exports__[\"default\"] = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_1547_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Oauth2Provider_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=style&index=0&lang=scss&":
/*!*******************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=style&index=0&lang=scss& ***!
  \*******************************************************************************************************************/
/***/ (function() {

eval("\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=style&index=0&lang=scss&":
/*!***********************************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=style&index=0&lang=scss& ***!
  \***********************************************************************************************************************/
/***/ (function() {

eval("\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?");

/***/ }),

/***/ "./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=style&index=0&lang=scss&":
/*!*******************************************************************************************************!*\
  !*** ./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=style&index=0&lang=scss& ***!
  \*******************************************************************************************************/
/***/ (function() {

eval("\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=template&id=037c9a63&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?vue&type=template&id=037c9a63& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* binding */ render; },\n/* harmony export */   \"staticRenderFns\": function() { return /* binding */ staticRenderFns; }\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('Form',{staticClass:\"tui-oauth2ProviderContent\"},[_c('InputSizedText',[_vm._v(\"\\n    \"+_vm._s(_vm.$str('oauth_url_title', 'totara_oauth2'))+\"\\n  \")]),_vm._v(\" \"),_c('InputSizedText',{domProps:{\"innerHTML\":_vm._s(_vm.$str('oauth_url_desc', 'totara_oauth2'))}}),_vm._v(\" \"),_c('InputSizedText',{staticClass:\"tui-oauth2ProviderContent__url\"},[_vm._v(\"\\n    \"+_vm._s(_vm.getOauthUrl())+\"\\n  \")]),_vm._v(\" \"),_c('InputSizedText',{domProps:{\"innerHTML\":_vm._s(_vm.$str('xapi_url_desc', 'totara_oauth2'))}}),_vm._v(\" \"),_c('InputSizedText',{staticClass:\"tui-oauth2ProviderContent__url\"},[_vm._v(\"\\n    \"+_vm._s(_vm.getXapiUrl())+\"\\n  \")])],1)}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/Oauth2ProviderContent.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=template&id=3f76ccf1&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?vue&type=template&id=3f76ccf1& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* binding */ render; },\n/* harmony export */   \"staticRenderFns\": function() { return /* binding */ staticRenderFns; }\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:\"tui-oauth2ProviderAction\"},[_c('Dropdown',{scopedSlots:_vm._u([{key:\"trigger\",fn:function(ref){\nvar toggle = ref.toggle;\nvar isOpen = ref.isOpen;\nreturn [_c('MoreIcon',{attrs:{\"aria-expanded\":isOpen ? 'true' : 'false',\"aria-label\":_vm.$str('actions_for', 'totara_oauth2', _vm.providerName),\"size\":300},on:{\"click\":toggle}})]}}])},[_vm._v(\" \"),_c('DropdownItem',{attrs:{\"title\":_vm.$str('delete_provider_name', 'totara_oauth2', _vm.providerName),\"aria-label\":_vm.$str('delete_provider_name', 'totara_oauth2', _vm.providerName)},on:{\"click\":function($event){return _vm.$emit('delete-provider')}}},[_vm._v(\"\\n      \"+_vm._s(_vm.$str('delete', 'core'))+\"\\n    \")])],1)],1)}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/action/Oauth2ProviderAction.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=template&id=42694335&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?vue&type=template&id=42694335& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* binding */ render; },\n/* harmony export */   \"staticRenderFns\": function() { return /* binding */ staticRenderFns; }\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('Modal',{staticClass:\"tui-oauth2ProviderForm\",attrs:{\"aria-labelledby\":_vm.$id('title')}},[_c('ModalContent',{attrs:{\"title\":_vm.title,\"title-id\":_vm.$id('title'),\"close-button\":_vm.showCloseButton},scopedSlots:_vm._u([{key:\"buttons\",fn:function(){return [_c('ButtonGroup',{staticClass:\"tui-oauth2ProviderForm__buttonGroup\"},[_c('Button',{attrs:{\"styleclass\":{ primary: true },\"text\":_vm.$str('add_provider', 'totara_oauth2'),\"aria-label\":_vm.$str('add_provider', 'totara_oauth2'),\"type\":\"submit\",\"disabled\":_vm.isSaving},on:{\"click\":function($event){return _vm.$refs.form.submit()}}}),_vm._v(\" \"),_c('Cancel',{attrs:{\"disabled\":_vm.isSaving},on:{\"click\":function($event){return _vm.$emit('request-close')}}})],1)]},proxy:true}])},[_c('Uniform',{ref:\"form\",attrs:{\"initial-values\":_vm.initialValues,\"input-width\":\"full\"},on:{\"change\":function($event){_vm.formValues = $event},\"submit\":function($event){return _vm.$emit('submit', $event)}}},[_c('FormRow',{staticClass:\"tui-oauth2ProviderForm__required\",attrs:{\"aria-hidden\":\"true\"}},[_c('span',{staticClass:\"tui-oauth2ProviderForm__requiredStar\"},[_vm._v(\"\\n          *\\n        \")]),_vm._v(\"\\n        \"+_vm._s(_vm.$str('required_fields', 'totara_oauth2'))+\"\\n      \")]),_vm._v(\" \"),_c('FormRow',{attrs:{\"label\":_vm.$str('name', 'core'),\"required\":\"\",\"vertical\":true},scopedSlots:_vm._u([{key:\"default\",fn:function(ref){\nvar id = ref.id;\nreturn [_c('FormText',{attrs:{\"id\":id,\"name\":\"name\",\"validations\":function (v) { return [v.required()]; },\"maxlength\":75}})]}}])}),_vm._v(\" \"),_c('FormRow',{attrs:{\"label\":_vm.$str('description', 'totara_oauth2'),\"vertical\":true},scopedSlots:_vm._u([{key:\"default\",fn:function(ref){\nvar id = ref.id;\nreturn [_c('FormTextarea',{attrs:{\"name\":\"description\",\"maxlength\":1024,\"aria-describedby\":_vm.$id('desc-desc'),\"rows\":_vm.setRows('description', 8, 25)}})]}}])}),_vm._v(\" \"),_c('FormRow',{attrs:{\"label\":_vm.$str('scopes', 'totara_oauth2'),\"vertical\":true}},[_c('Checkbox',{staticClass:\"tui-oauth2ProviderForm__checkBox\",attrs:{\"name\":\"xapi_write\",\"disabled\":\"\",\"checked\":\"\"}},[_vm._v(\"\\n          \"+_vm._s(_vm.$str('xapi_write', 'totara_oauth2'))+\"\\n        \")])],1),_vm._v(\" \"),_c('input',{directives:[{name:\"show\",rawName:\"v-show\",value:(false),expression:\"false\"}],attrs:{\"type\":\"submit\",\"disabled\":_vm.isSaving}})],1)],1)],1)}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/components/modal/Oauth2ProviderModal.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=template&id=0ed067c6&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?vue&type=template&id=0ed067c6& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* binding */ render; },\n/* harmony export */   \"staticRenderFns\": function() { return /* binding */ staticRenderFns; }\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('Layout',{staticClass:\"tui-oauth2ProviderPage\",attrs:{\"title\":_vm.$str('oauth2providerdetails', 'totara_oauth2'),\"loading\":_vm.$apollo.loading},scopedSlots:_vm._u([{key:\"header-buttons\",fn:function(){return [_c('Button',{attrs:{\"text\":_vm.$str('add_provider', 'totara_oauth2')},on:{\"click\":function($event){$event.preventDefault();_vm.modalOpen = true}}})]},proxy:true},{key:\"modals\",fn:function(){return [_c('ModalPresenter',{attrs:{\"open\":_vm.modalOpen},on:{\"request-close\":function($event){_vm.modalOpen = false}}},[_c('Oauth2ProviderModal',{attrs:{\"title\":_vm.$str('add_oauth2_provider', 'totara_oauth2'),\"is-saving\":_vm.isSaving},on:{\"submit\":_vm.createProvider}})],1),_vm._v(\" \"),_c('DeleteConfirmationModal',{attrs:{\"open\":_vm.deleteModalOpen,\"title\":_vm.$str('delete_modal_title', 'totara_oauth2'),\"confirm-button-text\":_vm.$str('continue', 'totara_oauth2'),\"loading\":_vm.deleting,\"close-button\":true},on:{\"confirm\":_vm.deleteProvider,\"cancel\":function($event){_vm.deleteModalOpen = false}}},[[_c('p',[_vm._v(_vm._s(_vm.$str('delete_confirm_title', 'totara_oauth2')))]),_vm._v(\" \"),_c('p',{staticClass:\"tui-oauth2ProviderPage__deleteBody\",domProps:{\"innerHTML\":_vm._s(\n            _vm.$str('delete_confirm_body', 'totara_oauth2', _vm.targetProvider.name)\n          )}})]],2)]},proxy:true},(!_vm.$apollo.loading)?{key:\"content\",fn:function(){return [(_vm.hasNoRecordError)?[_c('p',{staticClass:\"tui-oauth2ProviderPage__errorTitle\"},[_vm._v(\"\\n        \"+_vm._s(_vm.$str('no_record_found', 'totara_oauth2'))+\"\\n      \")])]:[_vm._l((_vm.providers),function(provider){return _c('Collapsible',{key:provider.id,staticClass:\"tui-oauth2ProviderPage__provider\",attrs:{\"label\":provider.name,\"value\":_vm.expanded[provider.id]},on:{\"input\":function($event){return _vm.handleCollapsibleChange($event, provider.id)}},scopedSlots:_vm._u([{key:\"collapsible-side-content\",fn:function(){return [_c('Oauth2ProviderAction',{attrs:{\"provider-name\":provider.name},on:{\"delete-provider\":function($event){return _vm.openDeleteModal(provider)}}})]},proxy:true}],null,true)},[_vm._v(\" \"),_c('Form',{staticClass:\"tui-oauth2ProviderPage__form\"},[(provider.description)?_c('FormRow',{staticClass:\"tui-oauth2ProviderPage__formDesc\",attrs:{\"vertical\":true},domProps:{\"innerHTML\":_vm._s(provider.description)}}):_vm._e(),_vm._v(\" \"),_c('FormRow',{class:{\n              'tui-oauth2ProviderPage__clientId': !provider.description,\n            },attrs:{\"label\":_vm.$str('client_id', 'totara_oauth2')}},[_c('span',{staticClass:\"tui-oauth2ProviderPage__monospaceFont\"},[_vm._v(\"\\n              \"+_vm._s(provider.client_id)+\"\\n            \")])]),_vm._v(\" \"),_c('FormRow',{attrs:{\"label\":_vm.$str('client_secret', 'totara_oauth2')},scopedSlots:_vm._u([{key:\"default\",fn:function(ref){\n            var id = ref.id;\n            var labelId = ref.labelId;\nreturn [_c('InputGroup',{attrs:{\"aria-labelledby\":labelId}},[[_c('InputGroupInput',{attrs:{\"id\":id,\"monospace\":true,\"readonly\":\"\",\"type\":_vm.showSecret[provider.id] ? 'text' : 'password',\"value\":provider.client_secret}}),_vm._v(\" \"),_c('InputGroupButton',{staticClass:\"tui-oauth2ProviderPage__inputGroupBtn\",attrs:{\"aria-controls\":id,\"aria-label\":_vm.showHideAriaLabel(provider),\"text\":_vm.showHideText(provider.id)},on:{\"click\":function($event){return _vm.toggleSecretVisibility(provider.id)}}})]],2)]}}],null,true)}),_vm._v(\" \"),_c('FormRow',{attrs:{\"label\":_vm.$str('scopes', 'totara_oauth2')}},[_vm._v(\"\\n            \"+_vm._s(provider.detail_scope)+\"\\n          \")])],1)],1)}),_vm._v(\" \"),_c('Oauth2ProviderContent')]]},proxy:true}:null],null,true)})}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/totara_oauth2/src/pages/Oauth2Provider.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "totara_oauth2/components/Oauth2ProviderContent":
/*!**********************************************************************************!*\
  !*** external "tui.require(\"totara_oauth2/components/Oauth2ProviderContent\")" ***!
  \**********************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("totara_oauth2/components/Oauth2ProviderContent");

/***/ }),

/***/ "totara_oauth2/components/action/Oauth2ProviderAction":
/*!****************************************************************************************!*\
  !*** external "tui.require(\"totara_oauth2/components/action/Oauth2ProviderAction\")" ***!
  \****************************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("totara_oauth2/components/action/Oauth2ProviderAction");

/***/ }),

/***/ "totara_oauth2/components/modal/Oauth2ProviderModal":
/*!**************************************************************************************!*\
  !*** external "tui.require(\"totara_oauth2/components/modal/Oauth2ProviderModal\")" ***!
  \**************************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("totara_oauth2/components/modal/Oauth2ProviderModal");

/***/ }),

/***/ "tui/components/buttons/ButtonGroup":
/*!**********************************************************************!*\
  !*** external "tui.require(\"tui/components/buttons/ButtonGroup\")" ***!
  \**********************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/buttons/ButtonGroup");

/***/ }),

/***/ "tui/components/buttons/Button":
/*!*****************************************************************!*\
  !*** external "tui.require(\"tui/components/buttons/Button\")" ***!
  \*****************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/buttons/Button");

/***/ }),

/***/ "tui/components/buttons/Cancel":
/*!*****************************************************************!*\
  !*** external "tui.require(\"tui/components/buttons/Cancel\")" ***!
  \*****************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/buttons/Cancel");

/***/ }),

/***/ "tui/components/buttons/MoreIcon":
/*!*******************************************************************!*\
  !*** external "tui.require(\"tui/components/buttons/MoreIcon\")" ***!
  \*******************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/buttons/MoreIcon");

/***/ }),

/***/ "tui/components/collapsible/Collapsible":
/*!**************************************************************************!*\
  !*** external "tui.require(\"tui/components/collapsible/Collapsible\")" ***!
  \**************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/collapsible/Collapsible");

/***/ }),

/***/ "tui/components/dropdown/DropdownItem":
/*!************************************************************************!*\
  !*** external "tui.require(\"tui/components/dropdown/DropdownItem\")" ***!
  \************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/dropdown/DropdownItem");

/***/ }),

/***/ "tui/components/dropdown/Dropdown":
/*!********************************************************************!*\
  !*** external "tui.require(\"tui/components/dropdown/Dropdown\")" ***!
  \********************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/dropdown/Dropdown");

/***/ }),

/***/ "tui/components/form/Checkbox":
/*!****************************************************************!*\
  !*** external "tui.require(\"tui/components/form/Checkbox\")" ***!
  \****************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/form/Checkbox");

/***/ }),

/***/ "tui/components/form/FormRow":
/*!***************************************************************!*\
  !*** external "tui.require(\"tui/components/form/FormRow\")" ***!
  \***************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/form/FormRow");

/***/ }),

/***/ "tui/components/form/Form":
/*!************************************************************!*\
  !*** external "tui.require(\"tui/components/form/Form\")" ***!
  \************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/form/Form");

/***/ }),

/***/ "tui/components/form/InputGroupButton":
/*!************************************************************************!*\
  !*** external "tui.require(\"tui/components/form/InputGroupButton\")" ***!
  \************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/form/InputGroupButton");

/***/ }),

/***/ "tui/components/form/InputGroupInput":
/*!***********************************************************************!*\
  !*** external "tui.require(\"tui/components/form/InputGroupInput\")" ***!
  \***********************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/form/InputGroupInput");

/***/ }),

/***/ "tui/components/form/InputGroup":
/*!******************************************************************!*\
  !*** external "tui.require(\"tui/components/form/InputGroup\")" ***!
  \******************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/form/InputGroup");

/***/ }),

/***/ "tui/components/form/InputSizedText":
/*!**********************************************************************!*\
  !*** external "tui.require(\"tui/components/form/InputSizedText\")" ***!
  \**********************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/form/InputSizedText");

/***/ }),

/***/ "tui/components/layouts/LayoutOneColumn":
/*!**************************************************************************!*\
  !*** external "tui.require(\"tui/components/layouts/LayoutOneColumn\")" ***!
  \**************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/layouts/LayoutOneColumn");

/***/ }),

/***/ "tui/components/modal/ConfirmationModal":
/*!**************************************************************************!*\
  !*** external "tui.require(\"tui/components/modal/ConfirmationModal\")" ***!
  \**************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/modal/ConfirmationModal");

/***/ }),

/***/ "tui/components/modal/ModalContent":
/*!*********************************************************************!*\
  !*** external "tui.require(\"tui/components/modal/ModalContent\")" ***!
  \*********************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/modal/ModalContent");

/***/ }),

/***/ "tui/components/modal/ModalPresenter":
/*!***********************************************************************!*\
  !*** external "tui.require(\"tui/components/modal/ModalPresenter\")" ***!
  \***********************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/modal/ModalPresenter");

/***/ }),

/***/ "tui/components/modal/Modal":
/*!**************************************************************!*\
  !*** external "tui.require(\"tui/components/modal/Modal\")" ***!
  \**************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/modal/Modal");

/***/ }),

/***/ "tui/components/uniform":
/*!**********************************************************!*\
  !*** external "tui.require(\"tui/components/uniform\")" ***!
  \**********************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/uniform");

/***/ }),

/***/ "tui/config":
/*!**********************************************!*\
  !*** external "tui.require(\"tui/config\")" ***!
  \**********************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/config");

/***/ }),

/***/ "tui/notifications":
/*!*****************************************************!*\
  !*** external "tui.require(\"tui/notifications\")" ***!
  \*****************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/notifications");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/regeneratorRuntime.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/regeneratorRuntime.js ***!
  \*******************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

eval("var _typeof = (__webpack_require__(/*! ./typeof.js */ \"./node_modules/@babel/runtime/helpers/typeof.js\")[\"default\"]);\n\nfunction _regeneratorRuntime() {\n  \"use strict\";\n  /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */\n\n  module.exports = _regeneratorRuntime = function _regeneratorRuntime() {\n    return exports;\n  }, module.exports.__esModule = true, module.exports[\"default\"] = module.exports;\n  var exports = {},\n      Op = Object.prototype,\n      hasOwn = Op.hasOwnProperty,\n      $Symbol = \"function\" == typeof Symbol ? Symbol : {},\n      iteratorSymbol = $Symbol.iterator || \"@@iterator\",\n      asyncIteratorSymbol = $Symbol.asyncIterator || \"@@asyncIterator\",\n      toStringTagSymbol = $Symbol.toStringTag || \"@@toStringTag\";\n\n  function define(obj, key, value) {\n    return Object.defineProperty(obj, key, {\n      value: value,\n      enumerable: !0,\n      configurable: !0,\n      writable: !0\n    }), obj[key];\n  }\n\n  try {\n    define({}, \"\");\n  } catch (err) {\n    define = function define(obj, key, value) {\n      return obj[key] = value;\n    };\n  }\n\n  function wrap(innerFn, outerFn, self, tryLocsList) {\n    var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator,\n        generator = Object.create(protoGenerator.prototype),\n        context = new Context(tryLocsList || []);\n    return generator._invoke = function (innerFn, self, context) {\n      var state = \"suspendedStart\";\n      return function (method, arg) {\n        if (\"executing\" === state) throw new Error(\"Generator is already running\");\n\n        if (\"completed\" === state) {\n          if (\"throw\" === method) throw arg;\n          return doneResult();\n        }\n\n        for (context.method = method, context.arg = arg;;) {\n          var delegate = context.delegate;\n\n          if (delegate) {\n            var delegateResult = maybeInvokeDelegate(delegate, context);\n\n            if (delegateResult) {\n              if (delegateResult === ContinueSentinel) continue;\n              return delegateResult;\n            }\n          }\n\n          if (\"next\" === context.method) context.sent = context._sent = context.arg;else if (\"throw\" === context.method) {\n            if (\"suspendedStart\" === state) throw state = \"completed\", context.arg;\n            context.dispatchException(context.arg);\n          } else \"return\" === context.method && context.abrupt(\"return\", context.arg);\n          state = \"executing\";\n          var record = tryCatch(innerFn, self, context);\n\n          if (\"normal\" === record.type) {\n            if (state = context.done ? \"completed\" : \"suspendedYield\", record.arg === ContinueSentinel) continue;\n            return {\n              value: record.arg,\n              done: context.done\n            };\n          }\n\n          \"throw\" === record.type && (state = \"completed\", context.method = \"throw\", context.arg = record.arg);\n        }\n      };\n    }(innerFn, self, context), generator;\n  }\n\n  function tryCatch(fn, obj, arg) {\n    try {\n      return {\n        type: \"normal\",\n        arg: fn.call(obj, arg)\n      };\n    } catch (err) {\n      return {\n        type: \"throw\",\n        arg: err\n      };\n    }\n  }\n\n  exports.wrap = wrap;\n  var ContinueSentinel = {};\n\n  function Generator() {}\n\n  function GeneratorFunction() {}\n\n  function GeneratorFunctionPrototype() {}\n\n  var IteratorPrototype = {};\n  define(IteratorPrototype, iteratorSymbol, function () {\n    return this;\n  });\n  var getProto = Object.getPrototypeOf,\n      NativeIteratorPrototype = getProto && getProto(getProto(values([])));\n  NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype);\n  var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype);\n\n  function defineIteratorMethods(prototype) {\n    [\"next\", \"throw\", \"return\"].forEach(function (method) {\n      define(prototype, method, function (arg) {\n        return this._invoke(method, arg);\n      });\n    });\n  }\n\n  function AsyncIterator(generator, PromiseImpl) {\n    function invoke(method, arg, resolve, reject) {\n      var record = tryCatch(generator[method], generator, arg);\n\n      if (\"throw\" !== record.type) {\n        var result = record.arg,\n            value = result.value;\n        return value && \"object\" == _typeof(value) && hasOwn.call(value, \"__await\") ? PromiseImpl.resolve(value.__await).then(function (value) {\n          invoke(\"next\", value, resolve, reject);\n        }, function (err) {\n          invoke(\"throw\", err, resolve, reject);\n        }) : PromiseImpl.resolve(value).then(function (unwrapped) {\n          result.value = unwrapped, resolve(result);\n        }, function (error) {\n          return invoke(\"throw\", error, resolve, reject);\n        });\n      }\n\n      reject(record.arg);\n    }\n\n    var previousPromise;\n\n    this._invoke = function (method, arg) {\n      function callInvokeWithMethodAndArg() {\n        return new PromiseImpl(function (resolve, reject) {\n          invoke(method, arg, resolve, reject);\n        });\n      }\n\n      return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg();\n    };\n  }\n\n  function maybeInvokeDelegate(delegate, context) {\n    var method = delegate.iterator[context.method];\n\n    if (undefined === method) {\n      if (context.delegate = null, \"throw\" === context.method) {\n        if (delegate.iterator[\"return\"] && (context.method = \"return\", context.arg = undefined, maybeInvokeDelegate(delegate, context), \"throw\" === context.method)) return ContinueSentinel;\n        context.method = \"throw\", context.arg = new TypeError(\"The iterator does not provide a 'throw' method\");\n      }\n\n      return ContinueSentinel;\n    }\n\n    var record = tryCatch(method, delegate.iterator, context.arg);\n    if (\"throw\" === record.type) return context.method = \"throw\", context.arg = record.arg, context.delegate = null, ContinueSentinel;\n    var info = record.arg;\n    return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, \"return\" !== context.method && (context.method = \"next\", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = \"throw\", context.arg = new TypeError(\"iterator result is not an object\"), context.delegate = null, ContinueSentinel);\n  }\n\n  function pushTryEntry(locs) {\n    var entry = {\n      tryLoc: locs[0]\n    };\n    1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry);\n  }\n\n  function resetTryEntry(entry) {\n    var record = entry.completion || {};\n    record.type = \"normal\", delete record.arg, entry.completion = record;\n  }\n\n  function Context(tryLocsList) {\n    this.tryEntries = [{\n      tryLoc: \"root\"\n    }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0);\n  }\n\n  function values(iterable) {\n    if (iterable) {\n      var iteratorMethod = iterable[iteratorSymbol];\n      if (iteratorMethod) return iteratorMethod.call(iterable);\n      if (\"function\" == typeof iterable.next) return iterable;\n\n      if (!isNaN(iterable.length)) {\n        var i = -1,\n            next = function next() {\n          for (; ++i < iterable.length;) {\n            if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next;\n          }\n\n          return next.value = undefined, next.done = !0, next;\n        };\n\n        return next.next = next;\n      }\n    }\n\n    return {\n      next: doneResult\n    };\n  }\n\n  function doneResult() {\n    return {\n      value: undefined,\n      done: !0\n    };\n  }\n\n  return GeneratorFunction.prototype = GeneratorFunctionPrototype, define(Gp, \"constructor\", GeneratorFunctionPrototype), define(GeneratorFunctionPrototype, \"constructor\", GeneratorFunction), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, \"GeneratorFunction\"), exports.isGeneratorFunction = function (genFun) {\n    var ctor = \"function\" == typeof genFun && genFun.constructor;\n    return !!ctor && (ctor === GeneratorFunction || \"GeneratorFunction\" === (ctor.displayName || ctor.name));\n  }, exports.mark = function (genFun) {\n    return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, \"GeneratorFunction\")), genFun.prototype = Object.create(Gp), genFun;\n  }, exports.awrap = function (arg) {\n    return {\n      __await: arg\n    };\n  }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () {\n    return this;\n  }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) {\n    void 0 === PromiseImpl && (PromiseImpl = Promise);\n    var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl);\n    return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) {\n      return result.done ? result.value : iter.next();\n    });\n  }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, \"Generator\"), define(Gp, iteratorSymbol, function () {\n    return this;\n  }), define(Gp, \"toString\", function () {\n    return \"[object Generator]\";\n  }), exports.keys = function (object) {\n    var keys = [];\n\n    for (var key in object) {\n      keys.push(key);\n    }\n\n    return keys.reverse(), function next() {\n      for (; keys.length;) {\n        var key = keys.pop();\n        if (key in object) return next.value = key, next.done = !1, next;\n      }\n\n      return next.done = !0, next;\n    };\n  }, exports.values = values, Context.prototype = {\n    constructor: Context,\n    reset: function reset(skipTempReset) {\n      if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = \"next\", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) {\n        \"t\" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined);\n      }\n    },\n    stop: function stop() {\n      this.done = !0;\n      var rootRecord = this.tryEntries[0].completion;\n      if (\"throw\" === rootRecord.type) throw rootRecord.arg;\n      return this.rval;\n    },\n    dispatchException: function dispatchException(exception) {\n      if (this.done) throw exception;\n      var context = this;\n\n      function handle(loc, caught) {\n        return record.type = \"throw\", record.arg = exception, context.next = loc, caught && (context.method = \"next\", context.arg = undefined), !!caught;\n      }\n\n      for (var i = this.tryEntries.length - 1; i >= 0; --i) {\n        var entry = this.tryEntries[i],\n            record = entry.completion;\n        if (\"root\" === entry.tryLoc) return handle(\"end\");\n\n        if (entry.tryLoc <= this.prev) {\n          var hasCatch = hasOwn.call(entry, \"catchLoc\"),\n              hasFinally = hasOwn.call(entry, \"finallyLoc\");\n\n          if (hasCatch && hasFinally) {\n            if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0);\n            if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc);\n          } else if (hasCatch) {\n            if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0);\n          } else {\n            if (!hasFinally) throw new Error(\"try statement without catch or finally\");\n            if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc);\n          }\n        }\n      }\n    },\n    abrupt: function abrupt(type, arg) {\n      for (var i = this.tryEntries.length - 1; i >= 0; --i) {\n        var entry = this.tryEntries[i];\n\n        if (entry.tryLoc <= this.prev && hasOwn.call(entry, \"finallyLoc\") && this.prev < entry.finallyLoc) {\n          var finallyEntry = entry;\n          break;\n        }\n      }\n\n      finallyEntry && (\"break\" === type || \"continue\" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null);\n      var record = finallyEntry ? finallyEntry.completion : {};\n      return record.type = type, record.arg = arg, finallyEntry ? (this.method = \"next\", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record);\n    },\n    complete: function complete(record, afterLoc) {\n      if (\"throw\" === record.type) throw record.arg;\n      return \"break\" === record.type || \"continue\" === record.type ? this.next = record.arg : \"return\" === record.type ? (this.rval = this.arg = record.arg, this.method = \"return\", this.next = \"end\") : \"normal\" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel;\n    },\n    finish: function finish(finallyLoc) {\n      for (var i = this.tryEntries.length - 1; i >= 0; --i) {\n        var entry = this.tryEntries[i];\n        if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel;\n      }\n    },\n    \"catch\": function _catch(tryLoc) {\n      for (var i = this.tryEntries.length - 1; i >= 0; --i) {\n        var entry = this.tryEntries[i];\n\n        if (entry.tryLoc === tryLoc) {\n          var record = entry.completion;\n\n          if (\"throw\" === record.type) {\n            var thrown = record.arg;\n            resetTryEntry(entry);\n          }\n\n          return thrown;\n        }\n      }\n\n      throw new Error(\"illegal catch attempt\");\n    },\n    delegateYield: function delegateYield(iterable, resultName, nextLoc) {\n      return this.delegate = {\n        iterator: values(iterable),\n        resultName: resultName,\n        nextLoc: nextLoc\n      }, \"next\" === this.method && (this.arg = undefined), ContinueSentinel;\n    }\n  }, exports;\n}\n\nmodule.exports = _regeneratorRuntime, module.exports.__esModule = true, module.exports[\"default\"] = module.exports;\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/helpers/regeneratorRuntime.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/typeof.js":
/*!*******************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/typeof.js ***!
  \*******************************************************/
/***/ (function(module) {

eval("function _typeof(obj) {\n  \"@babel/helpers - typeof\";\n\n  return (module.exports = _typeof = \"function\" == typeof Symbol && \"symbol\" == typeof Symbol.iterator ? function (obj) {\n    return typeof obj;\n  } : function (obj) {\n    return obj && \"function\" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? \"symbol\" : typeof obj;\n  }, module.exports.__esModule = true, module.exports[\"default\"] = module.exports), _typeof(obj);\n}\n\nmodule.exports = _typeof, module.exports.__esModule = true, module.exports[\"default\"] = module.exports;\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/helpers/typeof.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/regenerator/index.js":
/*!**********************************************************!*\
  !*** ./node_modules/@babel/runtime/regenerator/index.js ***!
  \**********************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

eval("// TODO(Babel 8): Remove this file.\n\nvar runtime = __webpack_require__(/*! ../helpers/regeneratorRuntime */ \"./node_modules/@babel/runtime/helpers/regeneratorRuntime.js\")();\nmodule.exports = runtime;\n\n// Copied from https://github.com/facebook/regenerator/blob/main/packages/runtime/runtime.js#L736=\ntry {\n  regeneratorRuntime = runtime;\n} catch (accidentalStrictMode) {\n  if (typeof globalThis === \"object\") {\n    globalThis.regeneratorRuntime = runtime;\n  } else {\n    Function(\"r\", \"regeneratorRuntime = r\")(runtime);\n  }\n}\n\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/regenerator/index.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js ***!
  \*********************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ _arrayLikeToArray; }\n/* harmony export */ });\nfunction _arrayLikeToArray(arr, len) {\n  if (len == null || len > arr.length) len = arr.length;\n\n  for (var i = 0, arr2 = new Array(len); i < len; i++) {\n    arr2[i] = arr[i];\n  }\n\n  return arr2;\n}\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js ***!
  \**********************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ _arrayWithoutHoles; }\n/* harmony export */ });\n/* harmony import */ var _arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayLikeToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js\");\n\nfunction _arrayWithoutHoles(arr) {\n  if (Array.isArray(arr)) return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(arr);\n}\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js ***!
  \*********************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ _asyncToGenerator; }\n/* harmony export */ });\nfunction asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {\n  try {\n    var info = gen[key](arg);\n    var value = info.value;\n  } catch (error) {\n    reject(error);\n    return;\n  }\n\n  if (info.done) {\n    resolve(value);\n  } else {\n    Promise.resolve(value).then(_next, _throw);\n  }\n}\n\nfunction _asyncToGenerator(fn) {\n  return function () {\n    var self = this,\n        args = arguments;\n    return new Promise(function (resolve, reject) {\n      var gen = fn.apply(self, args);\n\n      function _next(value) {\n        asyncGeneratorStep(gen, resolve, reject, _next, _throw, \"next\", value);\n      }\n\n      function _throw(err) {\n        asyncGeneratorStep(gen, resolve, reject, _next, _throw, \"throw\", err);\n      }\n\n      _next(undefined);\n    });\n  };\n}\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/defineProperty.js ***!
  \*******************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ _defineProperty; }\n/* harmony export */ });\nfunction _defineProperty(obj, key, value) {\n  if (key in obj) {\n    Object.defineProperty(obj, key, {\n      value: value,\n      enumerable: true,\n      configurable: true,\n      writable: true\n    });\n  } else {\n    obj[key] = value;\n  }\n\n  return obj;\n}\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/helpers/esm/defineProperty.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/iterableToArray.js":
/*!********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/iterableToArray.js ***!
  \********************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ _iterableToArray; }\n/* harmony export */ });\nfunction _iterableToArray(iter) {\n  if (typeof Symbol !== \"undefined\" && iter[Symbol.iterator] != null || iter[\"@@iterator\"] != null) return Array.from(iter);\n}\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/helpers/esm/iterableToArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js ***!
  \**********************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ _nonIterableSpread; }\n/* harmony export */ });\nfunction _nonIterableSpread() {\n  throw new TypeError(\"Invalid attempt to spread non-iterable instance.\\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.\");\n}\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js ***!
  \**********************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ _toConsumableArray; }\n/* harmony export */ });\n/* harmony import */ var _arrayWithoutHoles_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayWithoutHoles.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js\");\n/* harmony import */ var _iterableToArray_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./iterableToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/iterableToArray.js\");\n/* harmony import */ var _unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./unsupportedIterableToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js\");\n/* harmony import */ var _nonIterableSpread_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./nonIterableSpread.js */ \"./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js\");\n\n\n\n\nfunction _toConsumableArray(arr) {\n  return (0,_arrayWithoutHoles_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(arr) || (0,_iterableToArray_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(arr) || (0,_unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(arr) || (0,_nonIterableSpread_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])();\n}\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js ***!
  \*******************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ _unsupportedIterableToArray; }\n/* harmony export */ });\n/* harmony import */ var _arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayLikeToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js\");\n\nfunction _unsupportedIterableToArray(o, minLen) {\n  if (!o) return;\n  if (typeof o === \"string\") return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(o, minLen);\n  var n = Object.prototype.toString.call(o).slice(8, -1);\n  if (n === \"Object\" && o.constructor) n = o.constructor.name;\n  if (n === \"Map\" || n === \"Set\") return Array.from(o);\n  if (n === \"Arguments\" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(o, minLen);\n}\n\n//# sourceURL=webpack:///./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./client/component/totara_oauth2/src/tui.json");
/******/ 	
/******/ })()
;