<?php
/**
 * An iRODS collection with filter on metadata
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\iRodsApi;
use OCA\files_irods\iRodsApi\iRodsSession;
use OCA\files_irods\iRodsApi\Path;
use OCA\files_irods\iRodsApi\File;
use OCA\files_irods\iRodsApi\iRodsPath;
use OCA\files_irods\iRodsApi\Collection;

class FilteredCollection extends Collection
{
    protected $predicate = null;

    /**
     * @param IRodsSession $session
     * @param string $path iRODS path
     * @param array $predicate a map of metadata keys to values
     */
    public function __construct(iRodsSession $session, $path, $predicate)
    {
        parent::__construct($session, $path, $this);
        $this->predicate = $predicate;
    }

    /**
     * Return the predicate mapping
     */
    public function getPredicate()
    {
        return $this->predicate;
    }

    /**
     * Get all children that match the predicate
     * @return array of string
     */
    public function getChildren()
    {
        $files = [];
        $account = $this->session->getAccount();
        $dir = new \ProdsDir($account, $this->path);
        $filter = array();
        foreach($this->predicate as $k=>$v)
        {
            $filter[] = new \RODSMeta($k, $v, null, null, "=");
        }
        $terms = array("descendantOnly" => true,
                       "recursive" => false,
                       "metadata" => $filter);
        $fileterms = array("metadata" => $filter);
        if($dir !== false)
        {
            try
            {
                foreach($dir->findFiles($fileterms,
                                        $total_num_rows,
                                        $startingInx,
                                        $maxresults) as $child)
                {
                    $files[] = $child->getName();
                }

                $maxresults = -1;
                $total_num_rows = -1;
                foreach($dir->findDirs($terms,
                                       $total_num_rows,
                                       $startingInx,
                                       $maxresults) as $child)
                {
                    $files[] = $child->getName();
                }
            }
            catch(Exception $e)
            {
                $files = false;
            }
            finally
            {
            }
        }
        return $files;
    }

    public function isCreatable()
    {
        return false;
    }

    public function isDeletable()
    {
        return false;
    }

}
