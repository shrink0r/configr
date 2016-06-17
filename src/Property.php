<?php

namespace Shrink0r\Configr;

class Property implements PropertyInterface
{
    protected $scheme;

    protected $name;

    protected $required;

    protected $parent;

    public function __construct(Scheme $scheme, $name, array $definition, PropertyInterface $parent = null)
    {
        $this->name = $name;
        $this->parent = $parent;
        $this->scheme = $scheme;
        $this->required = isset($definition['required']) ? $definition['required'] : true;
    }

    public function validate(array $config, array $handledKeys = [])
    {

        $propName = $this->getName();
        $errors = [];

        if ($propName === ':any_name:') {
            foreach ($config as $key => $value) {
                $curErrors = $this->validateValue($value);
                if (!empty($curErrors)) {
                    $errors[$key] = $curErrors;
                }
            }
        } else {
            $value = isset($config[$propName]) ? $config[$propName] : null;
            if (null === $value && $this->isRequired()) {
                $errors[] = "Missing required key '$propName'";
            } else if ($value !== null) {
                $errors = $this->validateValue($value);
            }
        }

        return $errors;
    }

    protected function validateValue($value)
    {
        $errors = [];

        return $errors;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function hasParent()
    {
        return $this->parent instanceof PropertyInterface;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function getScheme()
    {
        return $this->scheme;
    }
}
