<?php
/**
 * components
 *
 * @author    Wolfy-J
 */
namespace Spiral\ODM;

use MongoDB\BSON\ObjectID;
use Spiral\Models\ActiveEntityInterface;
use Spiral\ODM\Events\DocumentEvent;

/**
 * DocumentEntity with added ActiveRecord methods and ability to connect to associated source.
 * Document also provides an ability to specify aggregations using it's schema:
 *
 * const SCHEMA = [
 *     ...,
 *     'outer' => [
 *          self::ONE => Outer::class, [ //Reference to outer document using internal
 *              '_id' => 'self::outerID' //outerID value
 *          ]
 *      ],
 *     'many' => [
 *          self::MANY => Outer::class, [ //Reference to many outer document using
 *              'innerID' => 'self::_id'  //document primary key
 *          ]
 *     ]
 * ];
 *
 * Note: self::{name} construction will be replaced with document value in resulted query, even
 * in case of arrays ;) You can also use dot notation to get value from nested document.
 *
 * Attention, document will be linked to default database and named collection by default, use
 * properties database and collection to define your own custom database and collection.
 *
 * You can use property "index" to declare needed document indexes:
 *
 * Set of indexes to be created for associated collection. Use self::INDEX_OPTIONS or "@options"
 * for additional parameters.
 *
 * Example:
 * const INDEXES = [
 *      ['email' => 1, '@options' => ['unique' => true]],
 *      ['name' => 1]
 * ];
 *
 * @link http://php.net/manual/en/mongocollection.ensureindex.php
 *
 * Configuration properties:
 * - database
 * - collection
 * - schema
 * - indexes
 * - defaults
 * - secured (* by default)
 * - fillable
 */
abstract class Document extends DocumentEntity implements ActiveEntityInterface
{
    /**
     * Associated collection and database names, by default will be resolved based on a class name.
     */
    const DATABASE   = null;
    const COLLECTION = null;

    /**
     * When set to true publicFields() method (and jsonSerialize) will replace '_id' property with
     * 'id'.
     */
    const HIDE_UNDERSCORE_ID = true;

    /**
     * Set of indexes to be created for associated collection. Use "@options" for additional
     * index options.
     *
     * Example:
     * const INDEXES = [
     *      ['email' => 1, '@options' => ['unique' => true]],
     *      ['name' => 1]
     * ];
     *
     * @link http://php.net/manual/en/mongocollection.ensureindex.php
     * @var array
     */
    const INDEXES = [];

    /**
     * Documents must ALWAYS have _id field.
     */
    const SCHEMA = [
        '_id' => ObjectID::class
    ];

    /**
     * _id is always nullable.
     */
    const DEFAULTS = [
        '_id' => null
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct($fields = [], array $schema = [], ODMInterface $odm = null)
    {
        parent::__construct($fields, $schema, $odm);

        if (!$this->isLoaded()) {
            //Automatically force solidState for newly created documents
            $this->solidState(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isLoaded(): bool
    {
        return !is_null($this->primaryKey());
    }

    /**
     * {@inheritdoc}
     */
    public function primaryKey()
    {
        return $this->getField('_id', null, false);
    }

    /**
     * {@inheritdoc}
     *
     * Check model setting HIDE_UNDERSCORE_ID in order to enable/disable automatic conversion of
     * '_id' to 'id'.
     */
    public function publicFields(): array
    {
        if (static::HIDE_UNDERSCORE_ID) {
            $public = parent::publicFields();
            unset($public['_id']);

            //Replacing '_id' property with 'id'
            return ['id' => (string)$this->primaryKey()] + $public;
        }

        return parent::publicFields();
    }

    /**
     * {@inheritdoc}
     *
     * @event create(DocumentEvent)
     * @event created(DocumentEvent)
     * @event update(DocumentEvent)
     * @event updated(DocumentEvent)
     */
    public function save(): int
    {
        if (!$this->isLoaded()) {
            $this->dispatch('create', new DocumentEvent($this));

            //Performing creation
            $result = $this->odm->collection(static::class)->insertOne($this->fetchValue(false));
            $this->setField('_id', $result->getInsertedId());
            $this->flushUpdates();
            //Done with creation

            $this->dispatch('created', new DocumentEvent($this));

            return self::CREATED;
        }

        if ($this->isSolid() || $this->hasUpdates()) {
            $this->dispatch('update', new DocumentEvent($this));

            //Performing an update
            $this->odm->collection(static::class)->updateOne(
                ['_id' => $this->primaryKey()],
                $this->buildAtomics()
            );
            $this->flushUpdates();
            //Done with update

            $this->dispatch('updated', new DocumentEvent($this));

            return self::UPDATED;
        }

        return self::UNCHANGED;
    }

    /**
     * {@inheritdoc}
     *
     * @event delete(DocumentEvent)
     * @event deleted(DocumentEvent)
     */
    public function delete()
    {
        if (!$this->isLoaded()) {
            //Nothing to do, do we need an exception here?
            return;
        }

        $this->dispatch('delete', new DocumentEvent($this));

        //Performing deletion
        $this->odm->collection(static::class)->deleteOne(['_id' => $this->primaryKey()]);
        $this->setField('_id', null, false);
        //Done with deletion

        $this->dispatch('deleted', new DocumentEvent($this));
    }
}