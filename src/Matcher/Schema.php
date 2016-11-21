<?php

namespace Matcher;

class Schema
{
    const ERR_COLLECTION_DEFINITION = 'COLLECTION_DEFINITION';
    const ERR_KEY_NOT_FOUND         = 'KEY_NOT_FOUND';
    const ERR_TYPE_UNKNOWN          = 'TYPE_UNKNOWN';
    const ERR_TYPE_MISMATCH         = 'TYPE_MISMATCH';

    private $errors = [];
    private $schema = [];
    private $types  = [
        'integer',
        'double',
        'boolean',
        'string',
    ];

    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    public function match(array $data): bool
    {
        if (empty($this->schema)) {
            return true;
        }

        $this->compareSchemaWithData($this->schema, $data);

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function compareSchemaWithData(array $schema, array $data, string $parent = '')
    {
        if (isset($schema['*'])) {
            $path = empty($parent) ? '*' : $parent . '.*';

            if (count($schema) > 1) {
                $this->errors[$path][static::ERR_COLLECTION_DEFINITION] = 'Definition of the collection must be the only definition of the level.';

                return;
            }

            foreach ($data as $item) {
                $this->compareSchemaWithData($schema['*'], $item, $path);
            }

            return;
        }

        foreach ($schema as $field => $type) {
            $original = $field;

            if (strpos($field, '?') === 0) {
                $field = substr($field, 1);

                if (!array_key_exists($field, $data)) {
                    continue;
                }
            }

            $path = empty($parent) ? $field : $parent . '.' . $field;

            if (!array_key_exists($field, $data)) {
                $this->errors[$path][static::ERR_KEY_NOT_FOUND] = 'The key defined in the schema is not found in the data.';

                continue;
            }

            if (is_array($schema[$original])) {
                if (!is_array($data[$field])) {
                    $this->errors[$path][static::ERR_TYPE_MISMATCH] = 'The value must be an array as defined in the schema.';

                    continue;
                }

                $this->compareSchemaWithData($schema[$original], $data[$field], $path);

                continue;
            }

            if (strpos($type, '?') === 0) {
                $type = substr($type, 1);

                if ($data[$field] === null) {
                    continue;
                }

            }

            if (strpos($type, ' => ') !== false) {
                $this->compareCompositeArrayTypes($data[$field], $type, $path);

                continue;
            }

            if (!in_array($type, $this->types)) {
                $this->errors[$path][static::ERR_TYPE_UNKNOWN] = "Unknown value type: ${type}.";

                continue;
            }

            if (gettype($data[$field]) !== $type) {
                $this->errors[$path][static::ERR_TYPE_MISMATCH] = "The value must be of type '${type}'.";
            }
        }
    }

    private function compareCompositeArrayTypes($data, string $type, string $path)
    {
        if (!preg_match('#^[a-z]+ => [a-z]+$#', $type)) {
            $this->errors[$path][static::ERR_TYPE_UNKNOWN] = "Unknown value type: ${type}.";

            return;
        }

        list($expectedKeyType, $expectedValueType) = explode(' => ', $type);

        if (!in_array($expectedKeyType, $this->types) || !in_array($expectedValueType, $this->types)) {
            $this->errors[$path][static::ERR_TYPE_UNKNOWN] = 'The key and value types are unknown: ${expectedKeyType} => ${expectedValueType}';

            return;
        }

        if (!is_array($data)) {
            $this->errors[$path][static::ERR_TYPE_MISMATCH] = "The value must be an array of type '${type}'.";

            return;
        }

        foreach ($data as $key => $value) {
            $actualKeyType   = gettype($key);
            $actualValueType = gettype($value);

            if ($actualKeyType !== $expectedKeyType || $actualValueType !== $expectedValueType) {
                $this->errors[$path][static::ERR_TYPE_MISMATCH] = "The кey and value must be of type '${expectedKeyType} => ${expectedValueType}'.";

                continue;
            }

        }
    }
}
