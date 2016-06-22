<?php

namespace Shrink0r\Configr;

use Shrink0r\Configr\Property\PropertyInterface;
use Shrink0r\Configr\SchemaInterface;

interface FactoryInterface
{
    /**
     * Creates a new schema instance from the given name and definition.
     *
     * @param string $name
     * @param mixed[] $definition
     * @param PropertyInterface $parent
     *
     * @return SchemaInterface
     */
    public function createSchema($name, array $definition, PropertyInterface $parent = null);

    /**
     * Creates an array of properties from the given map of property definitions.
     *
     * @param mixed[] $definitions
     * @param SchemaInterface $schema
     * @param PropertyInterface $parent
     *
     * @return PropertyInterface[] An array of properties where the property names are used as coresponding keys.
     */
    public function createProperties(array $definitions, SchemaInterface $schema, PropertyInterface $parent = null);

    /**
     * Creates a property from the given definition.
     *
     * @param mixed[] $definitions
     * @param SchemaInterface $schema
     * @param PropertyInterface $parent
     *
     * @return PropertyInterface
     */
    public function createProperty(array $definition, SchemaInterface $schema, PropertyInterface $parent = null);
}