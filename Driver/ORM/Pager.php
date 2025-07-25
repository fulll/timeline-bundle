<?php

namespace Spy\TimelineBundle\Driver\ORM;

use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
        if (!$target instanceof DoctrineQueryBuilder) {
            throw new \Exception('Not supported yet');
        }

        if ($limit) {
            $offset = ($page - 1) * (int) $limit;

            $target
                ->setFirstResult($offset)
                ->setMaxResults($limit)
            ;
        }

        $paginator       = new Paginator($target, true);
        $this->page      = $page;
        $this->items     = (array) $paginator->getIterator();
        $this->nbResults = count($paginator);
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
    public function getIterator(): \ArrayIterator
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
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }
}
