# Totara Mobile plugin

This plugin is provided to support the standard Totara Mobile App. 

It is disabled by default. To enable and configure it, go to __Plugins > Mobile > Mobile settings__. 

## Testing or troubleshooting using the Mobile Device Emulator

An HTML + JavaScript endpoint is included for developers that emulates key behaviour of the Totara Mobile App. It is disabled by default.

1. Add `$CFG->mobile_device_emulator = true;` to your config.php
2. Enable the mobile plugin
2. Point your desktop web browser at /totara/mobile/device_emulator.php
3. Log in using any valid username and password.
4. You can submit GraphQL persisted queries using the GraphQL browser
5. Any pluginfile.php links are handled automatically; click to emulate downloading the file
6. Use a `totara_mobile_create_webview` query (see sample below) to open a URL in the webview window
7. Note that if you see a __Mobile access error__ click the 'Log out' link at the bottom of the page, and resubmit the `totara_mobile_create_webview` query

####Some sample queries
Basic information about the current user:
```
{
"operationName" : "totara_mobile_me",
"variables" : {}
}
```
List of current learning items:
```
{
"operationName" : "totara_mobile_current_learning",
"variables" : {}
}
```
Detailed information about a course:
```
{
"operationName" : "totara_mobile_course",
"variables" : {"courseid": 2}
}
```
Create a webview of a URL (opens in emulated webview browser):
```
{
"operationName" : "totara_mobile_create_webview",
"variables" : {"url" : "/course/view.php?id=2"}
}
```
Remove the current device registration
```
{
"operationName" : "totara_mobile_delete_device",
"variables" : {}
}
```

## Behind the scenes

If you are unable to use the device emulator or want to submit queries and work with endpoints directly, see the sections below.

### How to register a new user device from app?

1. Use regular browser to log in as site admin and enable the mobile plugin.
2. Then the Mobile App should open the site login page in new WebView with __TOTARA_MOBILE_DEVICE_REGISTRATION__ HTTP request header that contains App name and version.
3. After successful log in user is taken to _/totara/mobile/device_request.php_ page which contains a secret setup code in data attribute of the success message.
4. Ideally the App should then reload the page in WebView to log user out automatically.
5. Then the App should make a simple Curl POST request to the _/totara/mobile/device_register.php_ script with json encoded object in request body, for example {"setupsecret" : "1DFPnot6fVV99dvfhjrOf2JDtiM6Om"}.
6. The returned data structure contains API key and GraphQL endpoint URL. App is expected to store the API key in some kind of secure storage.

NOTE: if you do not have a Mobile App, you can emulate WebView by adding following temporarily to your config.php
```
$_SERVER['HTTP_X_TOTARA_MOBILE_DEVICE_REGISTRATION'] = 'Regular browser hack v1';
``` 

### Manual execution of persisted GraphQL queries

Create PhpStorm HTTP scratch with following text, change POST URL and API-KEY header value to match your site and device registration:  

```
POST http://localhost:8080/totara/mobile/api.php
Accept: application/json
X-API-KEY: vzW4My45Z41i8KKXGy53RxCYZz5LFHkbk8BT1Jbrk56qrSx9BE

{
"operationName" : "totara_mobile_me",
"variables" : {}
}

```

### Manually initiate WebView with user session for embedding in Mobile App

1. Execute _totara_mobile_create_webview_ persisted GraphQL mutation to obtain a secret header token, you need to specify requested URL as parameter.
2. Create a new WebView with __/totara/mobile/device_webview.php__ URL in mobile app with HTTP request header __TOTARA_MOBILE_WEBVIEW_SECRET__ containing previously obtained secret.
3. New user session is automatically created without visiting login page and WebView is redirected to previously specified URL.
4. When WebView is not necessary any more Mobile App should execute _totara_mobile_delete_webview_ persisted GraphQL mutation.

NOTE: again you can fake WebView in regular browser by adding following to your config.php
```
$_SERVER['HTTP_X_TOTARA_MOBILE_WEBVIEW_SECRET'] = '8yWXYXrCgG9QGCZkz0YyBxhxFXajXN';
```

Examples

```
POST http://localhost:8080/totara/mobile/api.php
Accept: application/json
X-API-KEY: vzW4My45Z41i8KKXGy53RxCYZz5LFHkbk8BT1Jbrk56qrSx9BE

{
"operationName" : "totara_mobile_create_webview",
"variables" : {"url" : "/course/view.php?id=5"}
}
```

```
POST http://localhost:8080/totara/mobile/api.php
Accept: application/json
X-API-KEY: vzW4My45Z41i8KKXGy53RxCYZz5LFHkbk8BT1Jbrk56qrSx9BE

{
"operationName" : "totara_mobile_delete_webview",
"variables" : {"secret" : "8yWXYXrCgG9QGCZkz0YyBxhxFXajXN"}
}
```

### Manually unregistering device

1. Execute _totara_mobile_delete_device_ persisted GraphQL mutation.

```
POST http://localhost:8080/totara/mobile/api.php
Accept: application/json
X-API-KEY: vzW4My45Z41i8KKXGy53RxCYZz5LFHkbk8BT1Jbrk56qrSx9BE

{
"operationName" : "totara_mobile_delete_device",
"variables" : {}
}
```