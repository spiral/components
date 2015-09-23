<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright �2009-2015
 */
namespace Spiral\ORM\Entities\Relations;

use Spiral\ORM\RecordEntity;

/**
 * Represents simple HAS_MANY relation with pre-defined WHERE query for generated selector.
 *
 * You have to pre-populate WHERE conditional field in associated records manually, create() method
 * not filling them.
 */
class HasMany extends HasOne
{
    /**
     * Relation type, required to fetch record class from relation definition.
     */
    const RELATION_TYPE = RecordEntity::HAS_MANY;

    /**
     * Indication that relation represent multiple records (HAS_MANY relations).
     */
    const MULTIPLE = true;

    /**
     * {@inheritdoc}
     */
    protected function createSelector()
    {
        $selector = parent::createSelector();

        if (isset($this->definition[RecordEntity::WHERE])) {
            $selector->where($this->mountAlias(
                $selector->getPrimaryAlias(),
                $this->definition[RecordEntity::WHERE]
            ));
        }

        return $selector;
    }
}