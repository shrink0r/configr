<?php

namespace Shrink0r\PhpSchema;

use Shrink0r\PhpSchema\Property\PropertyInterface;

/**
 * Default implementation of the SchemaInterface.
 */
class Schema implements SchemaInterface
{
    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var PropertyInterface $parent
     */
    protected $parent;

    /**
     * @var PropertyInterface[] $properties
     */
    protected $properties = [];

    /**
     * @var SchemaInterface[]
     */
    protected $customTypes = [];

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @param string $name The name of the schema.
     * @param mixed[] $schema Must contain keys for 'type', 'properties' and 'customTypes'.
     * @param FactoryInterface $factory Will be used to create objects while processing the given schema.
     * @param PropertyInterface $parent If created below a prop (assoc, etc.) this will hold that property.
     */
    public function __construct($name, array $schema, FactoryInterface $factory, PropertyInterface $parent = null)
    {
        $this->name = $name;
        $this->parent = $parent;
        $this->factory = $factory;
        $this->type = $schema['type'];

        list($customTypes, $properties) = $this->verifySchema($schema);
        foreach ($customTypes as $typeName => $definition) {
            $this->customTypes[$typeName] = $this->factory->createSchema($typeName, $definition, $parent);
        }
        $this->properties = $this->factory->createProperties($properties, $this, $parent);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $data)
    {
        $mergeErrors = function (array $errors, ResultInterface $result) {
            if ($result instanceof Error) {
                return array_merge($errors, $result->unwrap());
            }
            return $errors;
        };

        $validationResults = [ $this->validateMappedValues($data), $this->validateAnyValues($data) ];
        $errors = array_reduce($validationResults, $mergeErrors, []);

        return empty($errors) ? Ok::unit() : Error::unit($errors);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomTypes()
    {
        return $this->customTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}}
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Ensures that the given schema has valid values. Will yield defaults where available.
     *
     * @param mixed[] $schema
     *
     * @return mixed[] Returns the given schema plus defaults where applicable.
     */
    protected function verifySchema(array $schema)
    {
        $customTypes = isset($schema['customTypes']) ? $schema['customTypes'] : [];
        if (!is_array($customTypes)) {
            throw new Exception("Given value for key 'customTypes' is not an array.");
        }

        $properties = isset($schema['properties']) ? $schema['properties'] : null;
        if (!is_array($properties)) {
            throw new Exception("Missing valid value for 'properties' key within given schema.");
        }

        return [ $customTypes, $properties ];
    }

    /**
     * Validates the values of all explicitly defined schema properties.
     *
     * @param array $data
     *
     * @return ResultInterface
     */
    protected function validateMappedValues(array $data)
    {
        $errors = [];

        foreach (array_diff_key($this->properties, [ ':any_name:' => 1 ]) as $key => $property) {
            $result = $this->selectValue($property, $data);
            if ($result instanceof Ok) {
                $value = $result->unwrap();
                if ($value === null) {
                    continue;
                }
                $result = $property->validate($value);
            }
            if ($result instanceof Error) {
                $errors[$key] = $result->unwrap();
            }
        }

        return empty($errors) ? Ok::unit() : Error::unit($errors);
    }

    /**
     * Returns the property's corresponding value from the given data array.
     *
     * @param PropertyInterface $property
     * @param array $data
     *
     * @return ResultInterface If the value does not exist an error is returned; otherwise Ok is returned.
     */
    protected function selectValue(PropertyInterface $property, array $data)
    {
        $errors = [];
        $key = $property->getName();
        $value = array_key_exists($key, $data) ? $data[$key] : null;

        if ($value === null && $property->isRequired()) {
            if (!array_key_exists($key, $data)) {
                $errors[] = Error::MISSING_KEY;
            }
            $errors[] = Error::MISSING_VALUE;
        }

        return empty($errors) ? Ok::unit($value) : Error::unit($errors);
    }

    /**
     * If the schema has a property named ':any_name:', this method will validate all keys,
     * that have not been explicitly addressed by the schema.
     *
     * @param mixed[] $data
     *
     * @return ResultInterface
     */
    protected function validateAnyValues(array $data)
    {
        $errors = [];

        foreach (array_diff_key($data, $this->properties) as $key => $value) {
            if (isset($this->properties[':any_name:'])) {
                if ($value === null) {
                    if ($this->properties[':any_name:']->isRequired()) {
                        $errors[$key] = [ Error::MISSING_VALUE ];
                    }
                    continue;
                }
                $result = $this->properties[':any_name:']->validate($value);
                if ($result instanceof Error) {
                    $errors[$key] = $result->unwrap();
                }
            } else {
                $errors[$key] = [ Error::UNEXPECTED_KEY ];
            }
        }

        return empty($errors) ? Ok::unit() : Error::unit($errors);
    }
}
