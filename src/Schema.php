<?php

namespace Shrink0r\Configr;

use Shrink0r\Configr\Property\PropertyInterface;

/**
 * Default implementation of the SchemaInterface.
 */
class Schema implements SchemaInterface
{
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
     * @param string $key The name of the schema.
     * @param mixed[] $schema Must contain keys for 'type', 'properties' and 'customTypes'.
     * @param Factory $factory Will be used to create objects while processing the given schema.
     * @param PropertyInterface $parent If created below a prop (assoc, etc.) this will hold that property.
     */
    public function __construct($key, array $schema, FactoryInterface $factory, PropertyInterface $parent = null)
    {
        $this->type = $schema['type'];
        $this->parent = $parent;
        $this->factory = $factory;

        list($customTypes, $properties) = $this->validateSchema($schema);
        foreach ($customTypes as $key => $definition) {
            $this->customTypes[$key] = $this->factory->createSchema($key, $definition, $parent);
        }
        $this->properties = $this->factory->createProperties($properties, $this, $parent);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $data)
    {
        $errors = [];
        foreach ($this->properties as $property) {
            $key = $property->getName();
            if ($key === ':any_name:') {
                continue;
            }
            if (!array_key_exists($key, $data) && $property->isRequired()) {
                $errors[$key] = [ Error::MISSING_KEY ];
                continue;
            }
            $value = isset($data[$key]) ? $data[$key] : null;
            if (is_null($value)) {
                if ($property->isRequired()) {
                    $errors[$key] = [ Error::MISSING_VALUE ];
                }
                continue;
            }
            $result = $property->validate($value);
            if ($result instanceof Error) {
                $errors[$key] = $result->unwrap();
            }
        }
        if (isset($this->properties[':any_name:'])) {
            foreach (array_diff_key($data, $this->properties) as $key => $value) {
                $result = $this->properties[':any_name:']->validate($value);
                if ($result instanceof Error) {
                    $errors[$key] = $result->unwrap();
                }
            }
        }

        return empty($errors) ? Ok::unit() : Error::unit($errors);
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
     * Ensures that the given schema has valid values and yields defaults where available.
     *
     * @param mixed[] $schema
     *
     * @return mixed[] Returns the given schema plus defaults where applicable.
     */
    protected function validateSchema(array $schema)
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
}
