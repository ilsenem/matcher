# Matcher\Schema

Simple schema matcher for arrays.

* [Installation](#installation)
* Usage
    * [Type matching](#type-matching)
    * [Nullable values](#type-matching)
    * [Skip keys](#skip-keys)
    * [Composite arrays](#composite-arrays)
    * [Array nesting and collections](#array-nesting-and-collections)
* [Errors](#errors)

## Installation

Install as [Composer](http://getcomposer.org) dependency:

```bash
composer require ilsenem/matcher
```

In your code:

```php
use Matcher\Schema;

// $schema = [];
// $data   = [];

$matcher = new Schema($schema);

if (!$matcher->match($data)) {
    print_r($matcher->getErrors());
}
```

## Usage

### Type matching

Supported types for values are:

* boolean
* integer
* double
* string

```php
$schema = [
    'id'     => 'integer',
    'email'  => 'string',
    'active' => 'boolean',
    'rating' => 'double',
];

$matcher = new Matcher\Schema($schema);

$matcher->match([
    'id'     => 1,
    'email'  => 'some@domain.zone',
    'active' => true,
    'rating' => .5,
]);
```

### Nullable values

Add `?` before type declaration to mark value as nullable.

```php
$schema = [
    'id'       => 'integer',
    'email'    => 'string',
    'nickname' => '?string',
];

$matcher = new Schema($schema);

$matcher->match([
    'id'       => 7,
    'email'    => 'leeroy.jenkins@wow.lol',
    'nickname' => null,
]);
```

### Skip keys

Add `?` before key to skip matching if key is not present in data.

```php
$schema = [
    'id'        => 'integer',
    'email'     => 'string',
    '?optional' => 'boolean'
];

$matcher = new Schema($schema);

$matcher->match([
    'id'    => 18,
    'email' => 'tired.to.fake@emails.zone',
]);
```

### Composite arrays

If an array have no strict schema but follow typings for key and value, you could
set composite `key => value` type for it:

```php
$schema = [
    'rules' => 'string => boolean',
];

$matcher = new Schema($schema);

$matcher->match([
    'rules' => [
        'admin.cp'    => true,
        'admin.users' => true,
    ],
]);
```

### Array nesting and collections

You could nest schema one into another to match complex structures and match array
collections:

```php

$schema = [
    '*' => [ // Many users in collection
        'id'     => 'integer',
        'email'  => 'string',
        'active' => 'boolean',
        'tokens' => [ // Nest more rules
            'activation'    => '?string',
            'authorization' => '?string',
        ],
        'role' => [
            'id'        => 'integer',
            'title'     => 'string',
            'superuser' => 'boolean',
            '?rules'    => 'string => boolean',
        ],
        '?orders' => [
            '*' => [
                'id'       => 'integer',
                'quantity' => 'integer',
                'price'    => 'double',
            ],
        ],
    ],
];

$matcher = new Schema($schema);

$matcher->match([
    [
        'id'     => 1,
        'email'  => 'admin@domain.zone',
        'active' => true,
        'tokens' => [
            'activation'    => null,
            'authorization' => '0329a06b62cd16b33eb6792be8c60b158d89a2ee3a876fce9a881ebb488c0914',
        ],
        'role' => [
            'id'    => 1,
            'title' => 'Administrator',
        ],
    ],
    [
        'id'     => 2,
        'email'  => 'moderator@domain.zone',
        'active' => true,
        'tokens' => [
            'activation'    => null,
            'authorization' => null,
        ],
        'role' => [
            'id'    => 2,
            'title' => 'Moderator',
            'rules' => [
                'admin.cp'     => false,
                'moderator.cp' => true,
            ],
        ]
    ],
    [
        'id'     => 87,
        'email'  => 'customer@domain.zone',
        'active' => true,
        'tokens' => [
            'activation'    => null,
            'authorization' => null,
        ],
        'role' => [
            'id'    => 3,
            'title' => 'Customer',
            'rules' => [
                'admin.cp'     => false,
                'moderator.cp' => false,
            ],
        ],
        'orders' => [
            [
                'id'       => 873,
                'quantity' => 7,
                'price'    => 18.99,
            ],
            [
                'id'       => 1314,
                'quantity' => 19,
                'price'    => 1.97,
            ]
        ],
    ]
]);
```

## Errors

With `$matcher->getErrors()` after matching you could get array of errors:

```php
[
    'path.to.*.array.key' => [
        'TYPE_OF_ERROR' => 'Human readable description.',
    ],
    // ...
]
```

Types of errors:

* `Schema::ERR_COLLECTION_DEFINITION` - Definition of the collection must be the only definition of the level.
* `Schema::ERR_KEY_NOT_FOUND` - The key defined in the schema is not found in the data.
* `Schema::ERR_TYPE_UNKNOWN` - Unknown type given in schema.
* `Schema::ERR_TYPE_MISMATCH` - Type mismatch for any type of declaration.

## License

MIT.
