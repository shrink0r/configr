<?php

namespace Shrink0r\PhpSchema;

/**
 * A schema validates that an array adheres to a concrete structure definition.
 */
interface SchemaInterface
{
    /**
     * Verify that the given data is structured according to the scheme.
     *
     * @param mixed[] $data
     *
     * @return ResultInterface Returns Ok on success; otherwise Error.
     */
    public function validate(array $data);

    /**
     * Returns the schema's name.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the schema type. Atm only 'assoc' is supported.
     *
     * @return string
     */
    public function getType();

    /**
     * Returns the custom-types that have been defined for the schema.
     *
     * @return SchemaInterface[]
     */
    public function getCustomTypes();

    /**
     * Returns the schema's properties.
     *
     * @return Property\PropertyInterface[]
     */
    public function getProperties();

    /**
     * Returns the factory, that is used by the schema.
     *
     * @return FactoryInterface
     */
    public function getFactory();
}
