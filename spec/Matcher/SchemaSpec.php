<?php

namespace Spec\Matcher;

use Matcher\Schema;

describe('Matcher\Shema', function () {
    it('should match with empty schema', function () {
        $data = [
            'some' => 'data',
        ];

        $schema  = [];
        $matcher = new Schema($schema);

        expect($matcher->match($data))->toBeTruthy();
    });

    it('should match types', function () {
        $types = [
            'integer' => 1,
            'boolean' => true,
            'string'  => 'string',
            'double'  => .5,
        ];

        $schema = [
            'integer' => 'integer',
            'boolean' => 'boolean',
            'string'  => 'string',
            'double'  => 'double',
        ];

        $matcher = new Schema($schema);

        expect($matcher->match($types))->toBeTruthy();
    });

    it('should skip optional fields', function () {
        $book = [
            'title'  => 'Some Book',
            'author' => 'Mr. Anonymous',
        ];

        $schema = [
            'title'  => 'string',
            'author' => 'string',
            '?isbn'  => 'string',
        ];

        $matcher = new Schema($schema);

        expect($matcher->match($book))->toBeTruthy();
    });

    it('should accept nullable fields', function () {
        $user = [
            'id'       => 1,
            'email'    => 'some@domain.zone',
            'nickname' => null,
        ];

        $schema = [
            'id'       => 'integer',
            'email'    => 'string',
            'nickname' => '?string',
        ];

        $matcher = new Schema($schema);

        expect($matcher->match($user))->toBeTruthy();
    });

    it('should match collections', function () {
        $users = [
            [
                'id'    => 1,
                'email' => 'some@domain.zone',
            ],
            [
                'id'    => 2,
                'email' => 'another@domain.zone',
            ],
        ];

        $schema = [
            '*' => [
                'id'    => 'integer',
                'email' => 'string',
            ],
        ];

        $matcher = new Schema($schema);

        expect($matcher->match($users))->toBeTruthy();
    });

    it('should match arrays by "type => type"', function () {
        $users = [
            [
                'id'    => 1,
                'email' => 'some@domain.zone',
                'rules' => [
                    'admin.cp'           => true,
                    'admin.users.delete' => false,
                ],
            ],
            [
                'id'    => 2,
                'email' => 'another@domain.zone',
                'rules' => [
                    'admin.cp' => false,
                ],
            ],
        ];

        $schema = [
            '*' => [
                'id'    => 'integer',
                'email' => 'string',
                'rules' => 'string => boolean',
            ],
        ];

        $matcher = new Schema($schema);

        expect($matcher->match($users))->toBeTruthy();
    });

    it('should match recursively', function () {
        $customers = [
            [
                'id'      => 1,
                'name'    => 'Mr. Anderson',
                'nickame' => 'Neo',
                'email'   => 'some@domain.zone',
                'role'    => [
                    'id'    => 3,
                    'title' => 'Customer',
                    'rules' => [
                        'admin.cp'           => false,
                        'admin.users.delete' => false,
                    ],
                ],
                'orders'  => [
                    [
                        'id'       => 387,
                        'price'    => 187.90,
                        'quantity' => 2,
                    ],
                    [
                        'id'       => 1692,
                        'price'    => 10.40,
                        'quantity' => 1,
                    ],
                    [
                        'id'       => 12,
                        'price'    => 1130.00,
                        'quantity' => 1,
                    ],
                ],
            ],
            [
                'id'     => 2,
                'name'   => 'Mr. Smith',
                'email'  => null,
                'role'   => [
                    'id'    => 3,
                    'title' => 'Customer',
                    'rules' => [
                        'admin.cp'           => false,
                        'admin.users.delete' => true,
                    ],
                ],
                'orders' => [],
            ],
        ];

        $schema = [
            '*' => [
                'id'        => 'integer',
                'name'      => 'string',
                '?nickname' => 'string',
                'email'     => '?string',
                'role'      => [
                    'id'    => 'integer',
                    'title' => 'string',
                    'rules' => 'string => boolean',
                ],
                'orders'    => [
                    '*' => [
                        'id'       => 'integer',
                        'price'    => 'double',
                        'quantity' => 'integer',
                    ],
                ],
            ],
        ];

        $matcher = new Schema($schema);

        expect($matcher->match($customers))->toBeTruthy();
    });

    it('should fail on wrong collection definition', function () {
        $matcher = new Schema([
            '*'   => ['foo' => 'integer'],
            'bar' => 'integer',
        ]);

        expect($matcher->match([]))->toBeFalsy();

        $errors = $matcher->getErrors();

        expect($errors)->toContainKey('*');
        expect($errors['*'])->toContainKey(Schema::ERR_COLLECTION_DEFINITION);
    });

    it('should fail on missed key', function () {
        $matcher = new Schema([
            'required' => 'string',
        ]);

        expect($matcher->match([]))->toBeFalsy();

        $errors = $matcher->getErrors();

        expect($errors)->toContainKey('required');
        expect($errors['required'])->toContainKey(Schema::ERR_KEY_NOT_FOUND);
    });

    it('should fail on wrong type', function () {
        $matcher = new Schema([
            'array' => 'wrong => types',
            'key'   => 'whatisthis',
        ]);

        expect($matcher->match([
            'array' => ['test' => 'me'],
            'key'   => '',
        ]))->toBeFalsy();

        $errors = $matcher->getErrors();

        expect($errors)->toContainKey('array');
        expect($errors['array'])->toContainKey(Schema::ERR_TYPE_UNKNOWN);
        expect($errors)->toContainKey('key');
        expect($errors['key'])->toContainKey(Schema::ERR_TYPE_UNKNOWN);
    });

    it('should fail on not match array in data defined in schema', function () {
        $matcher = new Schema([
            'meta' => [
                'pages' => 'integer',
                'page'  => 'integer',
            ],
        ]);

        expect($matcher->match([
            'meta' => null,
        ]))->toBeFalsy();

        $errors = $matcher->getErrors();

        expect($errors)->toContainKey('meta');
        expect($errors['meta'])->toContainKey(Schema::ERR_TYPE_MISMATCH);
    });

    it('should fail on wrong composite array type', function () {
        $matcher = new Schema([
            'rules' => ' => 123',
        ]);

        expect($matcher->match([
            'rules' => [
                'can' => false,
            ],
        ]))->toBeFalsy();

        $errors = $matcher->getErrors();

        expect($errors)->toContainKey('rules');
        expect($errors['rules'])->toContainKey(Schema::ERR_TYPE_UNKNOWN);
    });

    it('should fail on unknown types in composite array type', function () {
        $matcher = new Schema([
            'rules' => 'some => thing',
        ]);

        expect($matcher->match([
            'rules' => [
                'can' => false,
            ],
        ]))->toBeFalsy();

        $errors = $matcher->getErrors();

        expect($errors)->toContainKey('rules');
        expect($errors['rules'])->toContainKey(Schema::ERR_TYPE_UNKNOWN);
    });

    it('should fail if data have wrong type for composite array type defined in schema', function () {
        $matcher = new Schema([
            'rules' => 'string => boolean',
        ]);

        expect($matcher->match([
            'rules' => null,
        ]))->toBeFalsy();

        $errors = $matcher->getErrors();

        expect($errors)->toContainKey('rules');
        expect($errors['rules'])->toContainKey(Schema::ERR_TYPE_MISMATCH);
    });

    it('should fail if data have wrong types for composite array defined in schema', function () {
        $matcher = new Schema([
            'rules' => 'string => boolean',
        ]);

        expect($matcher->match([
            'rules' => [
                1 => 'test',
            ],
        ]))->toBeFalsy();

        $errors = $matcher->getErrors();

        expect($errors)->toContainKey('rules');
        expect($errors['rules'])->toContainKey(Schema::ERR_TYPE_MISMATCH);
    });
});
