The external API uses OAuth 2.0 via the client credentials grant type
to authenticate requests. This involves three steps as outlined below.

### 1. Register a client

To register a client, log in to your Totara site and navigate to 'API Clients'.

Click the **Add client** button and provide information to describe the purpose of your client.

Make a note of the client id and secret for step 2.

### 2. Request a token

To programmatically request a token, call the OAuth 2.0 token endpoint
as follows, passing the client_id and client_secret obtained during
step 1:

```sh
curl -X POST 'https://YOUR-SITE-URL/totara/oauth2/token.php' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=client_credentials&client_id=CLIENT_ID_HERE&client_secret=CLIENT_SECRET_HERE'
```

The response will be:

```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "YOUR-API-BEARER-TOKEN"
}
```

### 3. Submit a request with a valid token

Copy the value from the ```"access_token"``` property in the response into the 'Authorization: Bearer' header of your request.

