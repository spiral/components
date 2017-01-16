<?php
/**
 * components
 *
 * @author    Wolfy-J
 */
namespace Spiral\ORM;

use Spiral\Models\EntityInterface;
use Spiral\ORM\Exceptions\MapException;

/**
 * Entity cache provides ability to access already retrieved entities from memory instead of
 * calling database. Attention, cache WILL BE isolated in a selection scope in order to prevent
 * data collision, ie:
 *
 * $user1 => $users->findOne();
 * $user2 => $user->findOne();
 *
 * assert($user1 !== $user2);
 */
final class EntityMap
{
    /**
     * @var EntityInterface[]
     */
    private $entities = [];

    /**
     * Maximum entity cache size. Null is unlimited.
     *
     * @var int|null
     */
    private $maxSize = null;

    /**
     * @param int|null $maxSize Set to null to make cache size unlimited.
     */
    public function __construct(int $maxSize = null)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * Add Record to entity cache. Primary key value will be used as
     * identifier.
     *
     * Attention, existed entity will be replaced!
     *
     * @param RecordInterface $entity
     * @param bool            $ignoreLimit Cache overflow will be ignored.
     *
     * @return RecordInterface Returns given entity.
     *
     * @throws MapException When cache size exceeded.
     */
    public function remember(
        RecordInterface $entity,
        $ignoreLimit = true
    ): RecordInterface {
        if (!$ignoreLimit && count($this->entities) > $this->maxSize) {
            throw new MapException('Entity cache size exceeded');
        }

        if (empty($entity->primaryKey())) {
            throw new MapException("Unable to store non identified entity " . get_class($entity));
        }

        $cacheID = get_class($entity) . ':' . $entity->primaryKey();

        return $this->entities[$cacheID] = $entity;
    }

    /**
     * Remove entity record from entity cache. Primary key value will be used as identifier.
     *
     * @param string $class
     * @param string $identity
     */
    public function forget(string $class, string $identity)
    {
        unset($this->entities["{$class}:{$identity}"]);
    }

    /**
     * Check if desired entity was already cached.
     *
     * @param string $class
     * @param string $identity
     *
     * @return bool
     */
    public function has(string $class, string $identity): bool
    {
        return isset($this->entities["{$class}:{$identity}"]);
    }

    /**
     * Fetch entity from cache.
     *
     * @param string $class
     * @param string $identity
     *
     * @return null|mixed
     */
    public function get(string $class, string $identity)
    {
        if (empty($this->entities["{$class}:{$identity}"])) {
            return null;
        }

        return $this->entities[$class . ':' . $identity];
    }

    /**
     * Flush content of entity cache.
     */
    public function flush()
    {
        $this->entities = [];
    }

    /**
     * Destructing.
     */
    public function __destruct()
    {
        $this->flush();
    }
}