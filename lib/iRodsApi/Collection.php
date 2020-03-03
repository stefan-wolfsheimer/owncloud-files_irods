<?php
/**
 * iRodsSession object
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\iRodsApi;
use OCP\Files\StorageNotAvailableException;
use OCA\files_irods\iRodsApi\iRodsSession;
use OCA\files_irods\iRodsApi\Path;
use OCA\files_irods\iRodsApi\File;
use OCA\files_irods\iRodsApi\iRodsPath;


class Collection extends Path
{
    use iRodsPath;
    protected $deleted = false;

    public function __construct(iRodsSession $session, $path, $root=null)
    {
        parent::__construct($session, $path);
        $this->rootCollection = $root;
    }

    public function getChildren()
    {

        $files = [];
        $account = $this->session->getAccount();
        $dir = new \ProdsDir($account, $this->path);
        if($dir !== false)
        {
            try
            {
                foreach($dir->getAllChildren() as $child)
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

    public function filetype()
    {
        if($this->deleted)
        {
            return false;
        }
        return "dir";
    }

    public function resolve($path, $root=null)
    {
        if($root == null)
        {
            $root = $this;
        }
        if($path == "")
        {
            return $this;
        }
        else
        {
            $ret = false;
            $path = $this->path."/".$path;
            \OC::$server->getLogger()->debug("Collection path $path");
            try
            {
                $conn = $this->session->open();
                if($conn->dirExists ($path))
                {
                    $ret = new Collection($this->session, $path, $root);
                }
                else if($conn->fileExists ($path))
                {
                    $ret = new File($this->session, $path, $root);
                }
            }
            catch(Exception $ex)
            {
            }
            finally
            {
                $this->session->close($conn);
            }
            return $ret;
        }
    }

    public function mkdir($name)
    {
        $ret = false;
        try
        {
            $path = $this->path."/".$name;
            $conn = $this->session->open();
            $conn->mkdir($path, true);
            $ret = $conn->dirExists($path);
        }
        catch(Exception $ex)
        {
        }
        finally
        {
            $this->session->close($conn);
        }
        return $ret;
    }

    public function rmdir()
    {
        $ret = false;
        try
        {
            $conn = $this->session->open();
            $conn->rmdir($this->path, true);
            $ret = !$conn->dirExists($this->path);
        }
        catch(Exception $ex)
        {
            error_log($ex->getMessage());
            $ret = false;
        }
        finally
        {
            $this->session->close($conn);
        }
        if($ret)
        {
            $this->deleted = true;
        }
        return $ret;
    }

    public function rename($target)
    {
        $ret = false;
        if(!($target instanceof Collection))
        {
            return false;
        }
        try
        {
            $conn = $this->session->open();
            $conn->rename($this->path,
                          $target->getPath(),
                          1);
            $ret = !$this->exists($conn) && $target->exists($conn);
        }
        catch(Exception $ex)
        {
        }
        finally
        {
            $this->session->close($conn);
        }
        return $ret;
    }

    protected function getStats($conn)
    {
        return $conn->getDirStats($this->path);
    }

    protected function exists($conn)
    {
        return $conn->dirExists($this->path);
    }

    protected function getAcl($connLocal)
    {
        $que_result_coll = $connLocal->genQuery(
            array("COL_COLL_OWNER_NAME", "COL_COLL_OWNER_ZONE", "COL_COLL_ID"),
            array(new \RODSQueryCondition("COL_COLL_NAME", $this->path)));
        if(!$que_result_coll)
        {
            return null;
        }
        // get user ids and group ids
        $user_groups = $connLocal->genQuery(
            array("COL_USER_ID", "COL_USER_NAME", "COL_USER_GROUP_ID", "COL_USER_ZONE"),
            array(new \RODSQueryCondition("COL_USER_NAME", $this->session->params['user']),
                  new \RODSQueryCondition("COL_USER_ZONE", $this->session->params['zone'])));
        $user_ids = $user_groups["COL_USER_GROUP_ID"];

        $que_result_access = $connLocal->genQuery(
            array("COL_DATA_ACCESS_NAME", "COL_DATA_ACCESS_USER_ID"),
            array(new \RODSQueryCondition("COL_DATA_ACCESS_DATA_ID", $que_result_coll['COL_COLL_ID'][0])));
        $acl = [];
        for($i=0; $i<sizeof($que_result_access["COL_DATA_ACCESS_USER_ID"]);$i++)
        {
            if(in_array($que_result_access["COL_DATA_ACCESS_USER_ID"][$i], $user_ids))
            {
                $acc_name = $que_result_access['COL_DATA_ACCESS_NAME'][$i];
                if($acc_name == "modify object") $acc_name = "write";
                elseif($acc_name == "read object") $acc_name = "read";
                $acl[] = $acc_name;
            }
        }
        if(!in_array("own", $acl) &&
           $que_result_coll['COL_COLL_OWNER_ZONE'][0] == $this->session->params['zone'] &&
           $que_result_coll['COL_COLL_OWNER_NAME'][0] == $this->session->params['user'])
        {
            $acl[] = "own";
        }
        return $acl;
    }

    protected function getIrodsPath()
    {
        $account = $this->session->getAccount();
        return  new \ProdsDir($account, $this->path);
    }
}
