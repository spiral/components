<?php
/**
 * components
 *
 * @author    Wolfy-J
 */
namespace Spiral\ORM\Entities\Nodes;

use Spiral\ORM\Entities\Nodes\Traits\DuplicateTrait;
use Spiral\ORM\Exceptions\LoaderException;

class RelationNode extends AbstractNode
{
    use DuplicateTrait;

    /**
     * @var string
     */
    protected $localKey;

    /**
     * @param array       $columns
     * @param string      $localKey  Inner relation key (for example user_id)
     * @param string|null $parentKey Outer (parent) relation key (for example id = parent.id)
     * @param array       $primaryKeys
     */
    public function __construct(
        array $columns = [],
        string $localKey,
        string $parentKey,
        array $primaryKeys = []
    ) {
        parent::__construct($columns, $parentKey);
        $this->localKey = $localKey;

        //Using primary keys (if any) to de-duplicate results
        $this->duplicateCriteria = $primaryKeys;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerData(string $container, array &$data)
    {
        if (empty($this->parent)) {
            throw new LoaderException("Unable to register data tree, parent is missing");
        }

        //Mounting parsed data into parent under defined container
        $this->parent->mount(
            $container,
            $this->referenceKey,
            $data[$this->localKey],
            $data
        );
    }
}