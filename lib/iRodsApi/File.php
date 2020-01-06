<?php
/**
 * iRodsSession object
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\iRodsApi;
use OCA\files_irods\iRodsApi\iRodsSession;
use OCA\files_irods\iRodsApi\Path;
use OCA\files_irods\iRodsApi\iRodsPath;

class File extends Path
{
    use iRodsPath;


    public function __construct(iRodsSession $session, $path, $root)
    {
        parent::__construct($session, $path);
        $this->rootCollection = $root;
    }

    /**
     * relative path to root collection
     * @todo implement this function
     */
    //public function relativePath()
        
    public function getChildren()
    {
        return [];
    }

    public function filetype()
    {
        return "file";
    }

    public function resolve($path, $root=null)
    {
        if($path == "")
        {
            return $this;
        }
        else
        {
            return false;
        }
    }

    public function unlink()
    {
        $ret = false;
        try
        {
            $conn = $this->session->open();
            if($conn->fileExists($this->path))
            {
                $conn->fileUnlink($this->path);
            }
            $ret = !$conn->fileExists($this->path);
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

    public function url()
    {
        $ret = sprintf("irods://%s:%s@%s:%d/%s/%s",
                       urlencode($this->session->params['user']."#".
                                 $this->session->params['zone']."#".
                                 $this->session->params['auth_mode']),
                       urlencode($this->session->params['password']),
                       urlencode($this->session->params['hostname']),
                       urlencode($this->session->params['port']),
                       urlencode($this->session->params['resc']),
                       urlencode($this->path));
        return $ret;
    }

    protected function getStats($conn)
    {
        return $conn->getFileStats($this->path);
    }


    function rename($target)
    {
        $ret = false;
        if(!($target instanceof File))
        {
            return false;
        }
        try
        {
            $conn = $this->session->open();
            $conn->rename($this->path,
                          $target->getPath(),
                          0);
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

    protected function exists($conn)
    {
        return $conn->fileExists ($this->path);
    }

    protected function getAcl($connLocal)
    {
        $parent = dirname($this->path);
        $filename = basename($this->path);
        $que_result_obj = null;
        $que_result_obj = $connLocal->genQuery(
            array("COL_D_DATA_ID", "COL_D_OWNER_NAME", "COL_D_OWNER_ZONE"),
            array(new \RODSQueryCondition("COL_DATA_NAME", $filename),
                  new \RODSQueryCondition("COL_COLL_NAME", $parent)),
            array());
        if(!$que_result_obj)
        {
            return null;
        }
        $user_groups = $connLocal->genQuery(
            array("COL_USER_ID", "COL_USER_NAME", "COL_USER_GROUP_ID", "COL_USER_ZONE"),
            array(new \RODSQueryCondition("COL_USER_NAME", $this->session->params['user']),
                  new \RODSQueryCondition("COL_USER_ZONE", $this->session->params['zone'])));
        $user_ids = $user_groups["COL_USER_GROUP_ID"];

        $que_result_access = $connLocal->genQuery(
            array("COL_DATA_ACCESS_NAME", "COL_DATA_ACCESS_USER_ID"),
            array(new \RODSQueryCondition("COL_DATA_ACCESS_DATA_ID", $que_result_obj['COL_D_DATA_ID'][0])));
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
           $que_result_obj['COL_D_DATA_OWNER_ZONE'][0] == $this->session->params['zone'] &&
           $que_result_obj['COL_D_DATA_OWNER_NAME'][0] == $this->session->params['user'])
        {
            $acl[] = "own";
        }
        return $acl;
    }

    protected function getIrodsPath()
    {
        $account = $this->session->getAccount();
        return  new \ProdsFile($account, $this->path);
    }
}
