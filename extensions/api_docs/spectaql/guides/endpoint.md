All requests to the external API are made through a single endpoint:

```text
https://YOUR-SITE-URL/api/graphql.php
```

Requests  are made via the HTTP POST method with a JSON-encoded body:

```sh
$ curl 'https://YOUR-SITE-URL/api/graphql.php' \
    -X POST \
    -H 'Authorization: Bearer YOUR-API-BEARER-TOKEN' \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json' \
    --data-binary '{ "query": "query { totara_webapi_status { status } }", "variables": "{}" }'
```

See the <a href="#introduction-item-1">Authentication</a> section below for how to obtain a valid Bearer token.

The expected response for this request would be the following JSON object:

```json
{
  "data": {
    "totara_webapi_status": {
      "status": "ok"
    }
  }
}
```
