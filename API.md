This is the documentation support for the REST API feature.

Route | Method | Description
-------- | --------------- | ----------
/**authors** | GET | Search for available authors.
/**posts**/`:post_id`/**authors** | GET |  Get all authors from a post
/**posts**/`:post_id`/**authors** | PUT |  Appends a new author to a post
/**posts**/`:post_id`/**authors**/`:author_id` | DELETE |  Removes an author from a post
/**guests** | GET |  Lists of filters all the currently available guests 
/**guests** | POST |  Creates a new guest
/**guests**/`:guest_id` | PUT |  Updates a new guest
/**guests**/`:guest_id` | DELETE |  Deletes a guest

## Authors 
This routes allows searching for authors.

### `GET`

**URL** : `http://<domain>/wp-json/coauthors/v1/authors`

**Requires Authentication**: :lock:

#### Parameters

Name | Type | Required | Description
------------ | ------------- | ------------- | -------------
`q` | String | :white_check_mark:  | Query parameter

#### Response
```
{
  "authors": [
    {
      "id": 3,
      "display_name": "Dummy",
      "user_email": "dummy@gmail.com",
      "user_nicename": "dummy1"
    },
    {
      "id": 4,
      "display_name": "dummy2",
      "user_email": "dummy2@gmail.com",
      "user_nicename": "dummy2"
    }
  ]
}
```


## Posts

Add, appends or remove a group of authors to an existing post.

### `GET`

**URL** : `http://<domain>/wp-json/coauthors/v1/posts/<post_id>/authors`

**Requires Authentication**: :lock: 

#### Parameters

Name | Type | Required | Description
------------ | ------------- | ------------- | -------------
`post_id` | Int |  | The post unique id.

#### Response
```
{
  "authors": [
    {
      "id": 3,
      "display_name": "Dummy",
      "user_nicename": "dummy1"
    },
    {
      "id": 4,
      "display_name": "dummy2",
      "user_nicename": "dummy2"
    }
  ]
}
```

### `PUT`

**URL** : `http://<domain>/wp-json/coauthors/v1/posts/<post_id>/authors`

**Requires Authentication**: :lock: 

#### Parameters

Name | Type | Required | Description
------------ | ------------- | ------------- | -------------
`coauthors` | Array | :white_check_mark:  | An array of authors usernames.

#### Response
```
[
    {
      "id": 3,
      "display_name": "Dummy",
      "user_nicename": "dummy1"
    }
]
```

### `DELETE`

**URL** : `http://<domain>/wp-json/coauthors/v1/posts/<post_id>/authors/<coauthor_id>`

**Requires Authentication**: :lock: 

#### Parameters

Name | Type | Required | Description
------------ | ------------- | ------------- | -------------
`post_id` | Int | :white_check_mark:  | Post id
`author_id` | Int | :white_check_mark:  | Author id to delete from post

#### Response
```
[
    {
      "id": 3,
      "display_name": "Dummy",
      "user_nicename": "dummy1"
    }
]
```

## Guests

Creates, updates or removes a guest author.

### `GET`

**URL** : `http://<domain>/wp-json/coauthors/v1/guests`

**Requires Authentication**: :lock: 

Name | Type | Required | Description
------------ | ------------- | ------------- | -------------
`q` | String |  :white_check_mark: | If filled, narrows the search by the `user_nicename`

### `POST`

**URL** : `http://<domain>/wp-json/coauthors/v1/guests`

**Requires Authentication**: :lock: 

#### Parameters

Name | Type | Required | Description
------------ | ------------- | ------------- | -------------
`display_name` | String | :white_check_mark:  | Display name
`user_login` | String  | :white_check_mark:  | User login, must be unique to guests.
`user_email` | String  | :white_check_mark:  | User email
`first_name` | String  | | First name
`last_name` | String  | | Last name
`linked_account` | String  | | Links the guest account to an existing account
`website` | String  | | User website
`aim` | String  | | AIM
`yahooim` | String  | | Yahoo IM
`jabber` | String  | | Jabber
`Description` | String  | | A text Description

#### Response
```
[
  {
    "id": "172",
    "display_name": "foo",
    "first_name": "Foo",
    "last_name": "Bar",
    "user_email": "foobar@gmail.com",
    "linked_account": "",
    "website": "http://www.foobar.org/",
    "aim": "testaim",
    "yahooim": "testyaho2",
    "jabber": "testjabber",
    "description": "Some simple description.",
    "user_nicename": "foobar",
  }
]
```
### `GET`

**URL** : `http://<domain>/wp-json/coauthors/v1/guests/<guest_id>`

**Requires Authentication**: :lock: 

#### Parameters

Name | Type | Required | Description
------------ | ------------- | ------------- | -------------
`ID` | Integer | :white_check_mark:  | Guest Id

#### Response
```
[
  {
    "id": "172",
    "display_name": "foo",
    "first_name": "Foo",
    "last_name": "Bar",
    "user_email": "foobar@gmail.com",
    "linked_account": "",
    "website": "http://www.foobar.org/",
    "aim": "testaim",
    "yahooim": "testyaho2",
    "jabber": "testjabber",
    "description": "Some simple description.",
    "user_nicename": "foobar",
  }
]
```

### `PUT`

**URL** : `http://<domain>/wp-json/coauthors/v1/guests/<guest_id>`

**Requires Authentication**: :lock: 

#### Parameters

Name | Type | Required | Description
------------ | ------------- | ------------- | -------------
`display_name` | String |   | Display name
`user_email` | String  | | User email
`first_name` | String  | | First name
`last_name` | String  | | Last name
`linked_account` | String  | | Links the guest account to an existing account
`website` | String  | | User website
`aim` | String  | | AIM
`yahooim` | String  | | Yahoo IM
`jabber` | String  | | Jabber
`Description` | String  | | A text Description

#### Response
```
[
  {
    "id": "172",
    "display_name": "foo",
    "first_name": "John",
    "last_name": "Bar",
    "user_email": "foobar@gmail.com",
    "linked_account": "",
    "website": "http://www.foobar.org/",
    "aim": "testaim",
    "yahooim": "testyaho2",
    "jabber": "testjabber",
    "description": "New description.",
    "user_nicename": "foobar",
  }
]
```

### `DELETE`

**URL** : `http://<domain>/wp-json/coauthors/v1/guests/<guest_id>`

**Requires Authentication**: :lock: 

#### Parameters

Name | Type | Required | Description
------------ | ------------- | ------------- | -------------
`ID` | Integer | :white_check_mark:  | Guest Id

#### Response
```
[
  {
    "id": "172",
    "display_name": "foo",
    "first_name": "John",
    "last_name": "Bar",
    "user_email": "foobar@gmail.com",
    "linked_account": "",
    "website": "http://www.foobar.org/",
    "aim": "testaim",
    "yahooim": "testyaho2",
    "jabber": "testjabber",
    "description": "New description.",
    "user_nicename": "foobar",
  }
]
```
