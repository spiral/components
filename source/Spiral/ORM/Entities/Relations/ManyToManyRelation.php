<?php
/**
 * components
 *
 * @author    Wolfy-J
 */
namespace Spiral\ORM\Entities\Relations;

use Spiral\Database\Exceptions\QueryException;
use Spiral\ORM\CommandInterface;
use Spiral\ORM\Commands\NullCommand;
use Spiral\ORM\ContextualCommandInterface;
use Spiral\ORM\Entities\RecordIterator;
use Spiral\ORM\Entities\Relations\Traits\MatchTrait;
use Spiral\ORM\Entities\Relations\Traits\PartialTrait;
use Spiral\ORM\Exceptions\RelationException;
use Spiral\ORM\Exceptions\SelectorException;
use Spiral\ORM\Record;
use Spiral\ORM\RecordInterface;
use Spiral\ORM\RelationInterface;

class ManyToManyRelation extends AbstractRelation implements \IteratorAggregate, \Countable
{
    use MatchTrait, PartialTrait;

    /**
     * @var \SplObjectStorage
     */
    private $pivotData;

    /**
     * Linked records.
     *
     * @var RecordInterface[]
     */
    private $linked = [];

    /**
     * Record which pivot data was updated, record must still present in linked array.
     *
     * @var array
     */
    private $updated = [];

    /**
     * Records scheduled to be de-associated.
     *
     * @var RecordInterface[]
     */
    private $unlinked = [];

    /**
     * {@inheritdoc}
     */
    public function hasRelated(): bool
    {
        return !empty($this->linked);
    }

    /**
     * {@inheritdoc}
     */
    public function withContext(
        RecordInterface $parent,
        bool $loaded = false,
        array $data = null
    ): RelationInterface {
        /**
         * @var self $relation
         */
        $relation = parent::withContext($parent, $loaded, $data);
        $relation->pivotData = new \SplObjectStorage();

        return $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function setRelated($value)
    {
        if (is_null($value)) {
            $value = [];
        }

        if (!is_array($value)) {
            throw new RelationException("HasMany relation can only be set with array of entities");
        }

        //Sync values without forcing it (no autoloading), i.e. clear CURRENT associations
        //  $this->sync($value, [], false);
    }

    /**
     * @return $this
     */
    public function getRelated()
    {
        return $this;
    }

    /**
     * Iterate over linked instances, will force pre-loading unless partial.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->loadData(true)->linked);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->loadData(true)->linked);
    }

    /**
     * Get all unlinked records.
     *
     * @return \ArrayIterator
     */
    public function getUnlinked()
    {
        return new \ArrayIterator($this->unlinked);
    }

    /**
     * Get pivot data associated with specific instance.
     *
     * @param RecordInterface $record
     *
     * @return array
     *
     * @throws RelationException
     */
    public function getPivot(RecordInterface $record): array
    {
        if (!$this->pivotData->offsetExists($record)) {
            throw new RelationException("Unable to get pivot data for non linked object");
        }

        return $this->pivotData->offsetGet($record);
    }

    /**
     * Link record with parent entity.
     *
     * @param RecordInterface $record
     * @param array           $pivotData
     *
     * @return self
     */
    public function link(RecordInterface $record, array $pivotData = []): self
    {
        if (in_array($record, $this->linked)) {
            //Merging pivot data
            $this->pivotData->offsetSet($record, $pivotData + $this->getPivot($record));

            if (in_array($record, $this->updated)) {
                //Indicating that record pivot data has been changed
                $this->updated[] = $record;
            }

            return $this;
        }

        //New association
        $this->linked[] = $record;
        $this->pivotData->offsetSet($record, $pivotData);

        return $this;
    }

    public function unlink($query)
    {
        $query = $this->matchOne($query);

    }

    /**
     * Check if given query points to linked entity.
     *
     * Example:
     * echo $post->tags->has(1);
     * echo $post->tags->has(['name'=>'tag a']);
     *
     * @param array|RecordInterface|mixed $query Fields, entity or PK.
     *
     * @return bool
     */
    public function has($query)
    {
        return !empty($this->matchOne($query));
    }

    /**
     * Fine one entity for a given query or return null. Method will autoload data.
     *
     * Example: ->matchOne(['value' => 'something', ...]);
     *
     * @param array|RecordInterface|mixed $query Fields, entity or PK.
     *
     * @return RecordInterface|null
     */
    public function matchOne($query)
    {
        foreach ($this->loadData(true)->linked as $instance) {
            if ($this->match($instance, $query)) {
                return $instance;
            }
        }

        return null;
    }

    /**
     * Return only instances matched given query, performed in memory! Only simple conditions are
     * allowed. Not "find" due trademark violation. Method will autoload data.
     *
     * Example: ->matchMultiple(['value' => 'something', ...]);
     *
     * @param array|RecordInterface|mixed $query Fields, entity or PK.
     *
     * @return \ArrayIterator
     */
    public function matchMultiple($query)
    {
        $result = [];
        foreach ($this->loadData()->linked as $instance) {
            if ($this->match($instance, $query)) {
                $result[] = $instance;
            }
        }

        return new \ArrayIterator($result);
    }

    /**
     * {@inheritdoc}
     */
    public function queueCommands(ContextualCommandInterface $command): CommandInterface
    {
        return new NullCommand();
    }

    /**
     * Load related records from database.
     *
     * @param bool $autoload
     *
     * @return self
     *
     * @throws SelectorException
     * @throws QueryException (needs wrapping)
     */
    protected function loadData(bool $autoload = true): self
    {
        if ($this->loaded) {
            return $this;
        }

        $this->loaded = true;

        if (empty($this->data) || !is_array($this->data)) {
            if ($this->autoload && $autoload) {
                //Only for non partial selections
                $this->data = $this->loadRelated();
            } else {
                $this->data = [];
            }
        }

        return $this->initInstances();
    }

    /**
     * Fetch data from database. Lazy load.
     *
     * @return array
     */
    protected function loadRelated(): array
    {
        $innerKey = $this->key(Record::INNER_KEY);

        //todo: load

        return [];
    }

    /**
     * Init relations and populate pivot map.
     *
     * @return ManyToManyRelation
     */
    private function initInstances(): self
    {
        if (is_array($this->data) && !empty($this->data)) {
            //Iterates and instantiate records
            $iterator = new RecordIterator($this->data, $this->class, $this->orm);

            foreach ($iterator as $pivotData => $item) {
                if (in_array($item, $this->linked)) {
                    //Skip duplicates (if any?)
                    continue;
                }

                $this->pivotData->attach($item, $pivotData);
                $this->linked[] = $item;
            }
        }

        //Memory free
        $this->data = [];

        return $this;
    }
}