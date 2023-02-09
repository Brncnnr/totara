A simple GraphQL request might look like this:

```graphql
query my_query {
  totara_webapi_status {
    status
  }
}
```

This request consists of the following.

### The query keyword

This indicates this is a read-only request. 'mutation' is used for requests which write data. The keyword is optional, but when provided the specific actions within the request must match the keyword given.

### Request name (my_query)

Optional string to allow the request author to describe the purpose of the request. It is not used by the server.

### Query or mutation name (totara_webapi_status)

Within the outer curly braces you must specify at least one query or mutation to execute (typically just one but requesting multiple is allowed).

The available queries and mutations are specified in the reference documentation.

### Response structure

In GraphQL it is up to the query author to specify the data they want to receive in the response.

The curly braces and field names within them specify the data structure. They must match the structure of the type that is returned by that query or mutation.

Types can be complex (contain properties that represent other types), so additional curly braces are used to obtain properties of subresources in one request. For example, to get a list of course names and the name of each course's category:

```graphql
query get_courses {
  get_courses {
    fullname
    category {
      name
    }
  }
}
```

### Arguments and variables

Some queries and mutations have optional or required arguments which require additional data to be passed with the request.

User-provided data is kept separate from the body of the request as variables. Variables are passed as a separate JSON object with the query. To make use of variables, the structure of the query changes slightly:

#### Query

```graphql
query get_course($courseid: core_id!) {
  core_course(courseid: $courseid) {
    fullname
  }
}
```

#### Variables

```json
{
  "courseid": 2
}
```

In this example the ‘core_course’ query has an argument 'courseid'. It must be of the type 'core_id' and it is required (the ! suffix is used when a field must be provided). Available arguments are listed in the reference documentation for a query or mutation. Although values can also be hardcoded in the query, it is good practice to use variables to support argument validation and query reuse.

Variables are represented by strings starting with the dollar sign ($). Any $variable specified within the body of the request must be defined in the round brackets after the outer query name. When defining a variable you must specify its type. Variables will be validated according to their type before query execution. Complex types (types with properties made of other types) are allowed but must be defined in the schema.

The variables object that is passed with the request must have keys that match the variable names and give values that are compatible with the specified type for that variable.

### Field arguments

Like queries and mutations, fields can support arguments. For example, here the argument on the timestamp field determines how the field is formatted in the response:

```graphql
query test {
 totara_webapi_status {
   status
   timestamp(format: DATETIMELONG)
  }
}
```

### Aliases

You can prefix a query, mutation or field with a different name and the server will return it as the key you specify. This can be used to return data to match a certain structure, or to differentiate if you are requesting the same field multiple times.

```graphql
query test {
  my_query_name: totara_webapi_status {
    status
    long_year: timestamp(format: DATETIMELONG)
    short_year: timestamp(format: DATETIMESHORT)
  }
}
```

would return:

```json
{
  "data": {
    "my_query_name": {
      "status": "ok",
      "long_year": "27/05/2022, 10:51",
      "short_year": "27/05/22, 10:51"
    }
  }
}
```

For more information on the GraphQL language see this [Introduction to GraphQL](https://graphql.org/learn/).
