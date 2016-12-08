<?php
/**
 * components
 *
 * @author    Wolfy-J
 */
namespace Spiral\Database\Drivers\MySQL\Schemas;

use Spiral\Database\Schemas\Prototypes\AbstractReference;

class ReferenceSchema extends AbstractReference
{
    /**
     * @param string $table
     * @param array  $schema
     *
     * @return ReferenceSchema
     */
    public static function createInstance(string $table, array $schema): self
    {
        $reference = new self($table, $schema['CONSTRAINT_NAME']);

        $reference->column = $schema['COLUMN_NAME'];

        $reference->foreignTable = $schema['REFERENCED_TABLE_NAME'];
        $reference->foreignKey = $schema['REFERENCED_COLUMN_NAME'];

        $reference->deleteRule = $schema['DELETE_RULE'];
        $reference->updateRule = $schema['UPDATE_RULE'];

        return $reference;
    }
}