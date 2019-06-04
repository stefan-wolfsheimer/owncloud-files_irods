<?php
namespace OCA\files_irods\iRodsApi;
use OCA\files_irods\iRodsApi\iRodsSession;
use OCA\files_irods\iRodsApi\Path;
use OCA\files_irods\iRodsApi\File;
use OCA\files_irods\iRodsApi\iRodsPath;
use OCA\files_irods\iRodsApi\Collection;

class FilteredCollection extends Collection
{
    protected $state = null;

    public function __construct(iRodsSession $session, $path, $state)
    {
        parent::__construct($session, $path, $this);
        $this->state = $state;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getChildren()
    {
        $files = [];
        $account = $this->session->getAccount();
        $dir = new \ProdsDir($account, $this->path);
        $terms = array("descendantOnly" => true,
                       "recursive" => false,
                       "metadata" => array(new \RODSMeta("IBRIDGES_STATE", $this->state,
                                                         null, null, "=")));
        $fileterms = array("metadata" => array(new \RODSMeta("IBRIDGES_STATE", $this->state,
                                                             null, null, "=")));

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
}
