<?php

namespace Spy\TimelineBundle\Driver\ODM;

use Doctrine\ODM\MongoDB\Query\Builder;
use Spy\Timeline\ResultBuilder\Pager\PagerInterface;

class Pager implements PagerInterface, \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var integer
     */
    protected $lastPage;

    /**
     * @var integer
     */
    protected $page;

    /**
     * @var integer
     */
    protected $nbResults;

    /**
     * {@inheritdoc}
     */
    public function paginate($target, $page = 1, $limit = 10)
    {
        if (!$target instanceof Builder) {
            throw new \Exception('Not supported yet');
        }

        $clone = clone $target;
        if ($limit) {
            $skip = $limit * ($page - 1);

            $target
                ->skip($skip)
                ->limit($limit);
        }

        $this->items     = $target->getQuery()->execute()->toArray();
        $this->page      = $page;
        $this->nbResults = $clone->getQuery()->count();
        $this->lastPage  = intval(ceil($this->nbResults / $limit));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): int
    {
        return $this->lastPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * {@inheritdoc}
     */
    public function haveToPaginate(): bool
    {
        return $this->getLastPage() > 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults(): int
    {
        return $this->nbResults;
    }

    /**
     * @param array $items items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return integer
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * @param  mixed   $offset
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }
}
