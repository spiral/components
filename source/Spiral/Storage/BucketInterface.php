<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Storage;

use Psr\Http\Message\StreamInterface;
use Spiral\Files\Streams\StreamableInterface;
use Spiral\Storage\Exceptions\BucketException;
use Spiral\Storage\Exceptions\ServerException;
use Spiral\Storage\Exceptions\StorageException;

/**
 * Abstraction level between remote storage and local filesystem. Provides set of generic file
 * operations.
 */
interface BucketInterface
{
    /**
     * Bucker name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Associated storage server instance.
     *
     * @return ServerInterface
     *
     * @throws StorageException
     */
    public function getServer();

    /**
     * Get server specific bucket option or return default value.
     *
     * @param string $name
     *
     * @param null   $default
     *
     * @return mixed
     */
    public function getOption($name, $default = null);

    /**
     * Get bucket prefix.
     *
     * @return string
     */
    public function getPrefix(): string;

    /**
     * Check if address be found in bucket namespace defined by bucket prefix.
     *
     * @param string $address
     *
     * @return bool|int Should return matched address length.
     */
    public function hasAddress(string $address);

    /**
     * Build object address using object name and bucket prefix. While using URL like prefixes
     * address can appear valid URI which can be used directly at frontend.
     *
     * @param string $name
     *
     * @return string
     */
    public function buildAddress(string $name): string;

    /**
     * Check if given name points to valid and existed location in bucket server.
     *
     * @param string $name
     *
     * @return bool
     *
     * @throws ServerException
     * @throws BucketException
     */
    public function exists($name);

    /**
     * Get object size or return false if object not found.
     *
     * @param string $name
     *
     * @return int|bool
     *
     * @throws ServerException
     * @throws BucketException
     */
    public function size(string $name);

    /**
     * Put given content under given name in associated bucket server. Must replace already existed
     * object.
     *
     * @param string                                     $name
     * @param string|StreamInterface|StreamableInterface $source
     *
     * @return ObjectInterface
     *
     * @throws ServerException
     * @throws BucketException
     */
    public function put(string $name, $source): ObjectInterface;

    /**
     * Must return filename which is valid in associated FilesInterface instance. Must trow an
     * exception if object does not exists. Filename can be temporary and should not be used
     * between sessions.
     *
     * @param string $name
     *
     * @return string
     *
     * @throws ServerException
     * @throws BucketException
     */
    public function allocateFilename(string $name): string;

    /**
     * Return PSR7 stream associated with bucket object content or trow and exception.
     *
     * @param string $name Storage object name.
     *
     * @return StreamInterface
     *
     * @throws ServerException
     * @throws BucketException
     */
    public function allocateStream(string $name): StreamInterface;

    /**
     * Delete bucket object if it exists.
     *
     * @param string $name Storage object name.
     *
     * @throws ServerException
     * @throws BucketException
     */
    public function delete(string $name);

    /**
     * Rename storage object without changing it's bucket. Must return new address on success.
     *
     * @param string $oldName
     * @param string $newName
     *
     * @return string
     *
     * @throws StorageException
     * @throws ServerException
     * @throws BucketException
     */
    public function rename(string $oldName, string $newName): string;

    /**
     * Copy storage object to another bucket. Method must return address which points to
     * new storage object.
     *
     * @param BucketInterface $destination
     * @param string          $name
     *
     * @return string
     *
     * @throws ServerException
     * @throws BucketException
     */
    public function copy(BucketInterface $destination, string $name): string;

    /**
     * Move storage object data to another bucket. Method must return new object address on success.
     *
     * @todo Add ability to specify new name, not only destination.
     * @param BucketInterface $destination
     * @param string          $name
     *
     * @return string
     *
     * @throws ServerException
     * @throws BucketException
     */
    public function replace(BucketInterface $destination, string $name): string;
}