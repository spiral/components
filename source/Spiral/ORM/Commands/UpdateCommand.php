<?php
/**
 * components
 *
 * @author    Wolfy-J
 */
namespace Spiral\ORM\Commands;

use Spiral\Database\Entities\Table;
use Spiral\ORM\Commands\Traits\ContextTrait;
use Spiral\ORM\ContextualCommandInterface;

/**
 * Update data CAN be modified by parent commands using context.
 *
 * This is conditional command, it would not be executed when no fields are given!
 */
class UpdateCommand extends TableCommand implements ContextualCommandInterface
{
    use ContextTrait;

    /**
     * Where conditions (short where format).
     *
     * @var array
     */
    private $where = [];

    /**
     * Columns to be updated.
     *
     * @var array
     */
    private $values = [];

    /**
     * UpdateCommand constructor.
     *
     * @param Table $table
     * @param array $where
     * @param array $values
     */
    public function __construct(Table $table, array $where, array $values = [])
    {
        parent::__construct($table);
        $this->where = $where;
        $this->values = $values;
    }

    /**
     * @param array $where
     */
    public function setWhere(array $where)
    {
        $this->where = $where;
    }

    /**
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * Inserting data into associated table.
     */
    public function execute()
    {
        $values = $this->context + $this->values;
        if (!empty($values)) {
            $this->table->update($values, $this->where)->run();
        }

        parent::execute();
    }
}