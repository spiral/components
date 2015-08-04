<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Database\Drivers\Postgres\Builders;

use Spiral\Database\Builders\InsertQuery as BaseInsertQuery;
use Spiral\Database\Drivers\Postgres\PostgresDriver;
use Spiral\Database\Entities\QueryCompiler;
use Spiral\Database\Exceptions\BuilderException;
use Spiral\Debug\Traits\LoggerTrait;

/**
 * Postgres driver requires little bit different method to handle last insert id.
 */
class InsertQuery extends BaseInsertQuery
{
    /**
     * Debug messages.
     */
    use LoggerTrait;

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(QueryCompiler $compiler = null)
    {
        $driver = $this->database->driver();
        if (!$driver instanceof PostgresDriver)
        {
            throw new BuilderException("Postgres InsertQuery can be used only with Postgres driver.");
        }

        if ($primary = $driver->getPrimary($this->database->getPrefix() . $this->table))
        {
            $this->logger()->debug(
                "Primary key '{sequence}' automatically resolved for table '{table}'.", [
                'table'    => $this->table,
                'sequence' => $primary
            ]);
        }

        $compiler = !empty($compiler) ? $compiler : $this->compiler;

        return $compiler->insert($this->table, $this->columns, $this->rowsets, $primary);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        return (int)$this->database->statement(
            $this->sqlStatement(),
            $this->getParameters()
        )->fetchColumn();
    }
}