<?php

namespace Fulcrum;

class Collection implements \Iterator
{
    protected $collection = array();
    private $isSeek = false;
    private $totalCount = null;
    private $pageItems = 0;
    private $currentPage = 1;

    /**
     * Constructor method
     *
     * @param array $initArray
     */
    public function __construct($initArray = array())
    {
        if (is_array($initArray)) {
            $this->collection = $initArray;
        }
    }

    /**
     * Add object to the collection
     *
     * @param object The object
     * @param boolean Add object to beginning of array or not
     */
    public function addObject($value, $blnAddToBeginning = false)
    {
        if ($blnAddToBeginning) {
            array_unshift($this->collection, $value);
        } else {
            array_push($this->collection, $value);
        }
    }

    /**
     * Get the current item from the collection.
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * Place the pointer one item forward and return the item.
     */
    public function next()
    {
        return next($this->collection);
    }

    /**
     * Get the current position of the pointer.
     */
    public function key()
    {
        return key($this->collection);
    }

    /**
     * Test if the requested item is valid.
     */
    public function valid()
    {
        if ($this->pageItems > 0) {
            if ($this->key() + 1 > $this->getLastIndex()) {
                return false;
            } else {
                return $this->current() !== false;
            }
        } else {
            return $this->current() !== false;
        }
    }

    /**
     * Reset the internal pointer of the collection to the first item.
     */
    public function rewind()
    {
        if (!$this->isSeek) {
            reset($this->collection);
        }

        return $this;
    }

    /**
     * Get the item count.
     */
    public function count()
    {
        $intReturn = (!is_null($this->totalCount)) ? $this->totalCount : count($this->collection);

        return $intReturn;
    }

    /**
     * Check if the pointer is at the first record.
     */
    public function isFirst()
    {
        return key($this->collection) == 0;
    }

    /**
     * Check if the pointer is at the last record.
     */
    public function isLast()
    {
        return key($this->collection) == (count($this->collection) - 1);
    }

    /**
     * Advance internal pointer to a specific index
     *
     * @param integer $intPosition
     */
    public function seek($intPosition)
    {
        if (is_numeric($intPosition) && $intPosition < count($this->collection)) {
            reset($this->collection);
            while ($intPosition > key($this->collection)) {
                next($this->collection);
            }
        }

        $this->isSeek = true;
    }

    /**
     * Order the collection on a given key [asc]ending or [desc]ending
     *
     * @param string $strSubject
     * @param string $strOrder
     */
    public function orderBy($strSubject, $strOrder = "asc", $strType = null)
    {
        for ($i = 0; $i < count($this->collection); $i++) {
            for ($j = 0; $j < count($this->collection) - $i - 1; $j++) {
                switch (strtolower($strType)) {
                    case "datetime":
                        $varPre = strtotime($this->collection[$j + 1]->$strSubject());
                        $varPost = strtotime($this->collection[$j]->$strSubject());

                        break;
                    default:
                        $varPre = $this->collection[$j + 1]->$strSubject();
                        $varPost = $this->collection[$j]->$strSubject();
                }

                if (strtolower($strOrder) == "asc") {
                    if ($varPre < $varPost) {
                        $objTemp = $this->collection[$j];
                        $this->collection[$j] = $this->collection[$j + 1];
                        $this->collection[$j + 1] = $objTemp;
                    }
                } else {
                    if ($varPre > $varPost) {
                        $objTemp = $this->collection[$j];
                        $this->collection[$j] = $this->collection[$j + 1];
                        $this->collection[$j + 1] = $objTemp;
                    }
                }
            }
        }
    }

    /**
     * Set the total count of all items. This is handy for lazy loaded obejcts.
     *
     * @param integer $intCount
     */
    public function setTotalCount($intCount)
    {
        $this->totalCount = $intCount;
    }

    /**
     * Set the number of items per page.
     *
     * @param integer $intValue
     */
    public function setItemsPerPage($intValue, $intCurrentPage)
    {
        $this->pageItems = $intValue;

        $this->setCurrentPage($intCurrentPage);
        $this->seek($this->getFirstIndex() - 1);
    }

    /**
     * Set the current page.
     *
     * @param integer $intValue
     */
    public function setCurrentPage($intValue)
    {
        $this->currentPage = $intValue;
    }

    /**
     * Get the current page number.
     *
     * @return integer
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Check if we are in pagination mode.
     *
     * @return boolean
     */
    public function haveToPaginate()
    {
        return $this->isSeek;
    }

    /**
     * Get the number of the first item in the current page.
     */
    public function getFirstIndex()
    {
        return ($this->getCurrentPage() * $this->pageItems) - ($this->pageItems - 1);
    }

    /**
     * Get the number of the last item in the current page.
     *
     * @return integer
     */
    public function getLastIndex()
    {
        $intReturn = ($this->getCurrentPage() * $this->pageItems);
        if ($intReturn > count($this->collection)) {
            $intReturn = count($this->collection);
        }

        return $intReturn;
    }

    /**
     * Get the number of the last page.
     */
    public function getLastPage()
    {
        return ceil(count($this->collection) / $this->pageItems);
    }
}
