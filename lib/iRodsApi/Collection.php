<?php
namespace OCA\files_irods\iRodsApi;
use OCA\files_irods\iRodsApi\iRodsSession;
use OCA\files_irods\iRodsApi\Path;
use OCA\files_irods\iRodsApi\File;
use OCA\files_irods\iRodsApi\iRodsPath;

class Collection extends Path
{
    use iRodsPath;

    
    public function __construct(iRodsSession $session, $path, $root=null)
    {
        parent::__construct($session, $path);
        $this->rootCollection = $root;
    }

    /**
     * relative path to root collection
     */
    public function relativePath()
    {
        if(!$this->rootCollection)
        {
            return false;
        }
        $rootpath = $this->rootCollection->getPath();
        if(substr($this->path, 0, strlen($rootpath)) == $rootpath)
        {
            return trim(substr($this->path, strlen($rootpath)),"/");
        }
        else
        {
            return false;
        }
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
        return "dir";
    }

    public function getMeta()
    {
        $ret = false;
        try
        {
            $account = $this->session->getAccount();
            $p = new \ProdsDir($account, $this->path);
            $ret = $p->getMeta();
        }
        catch(Exception $ex)
        {
        }
        finally
        {
        }
        return $ret;
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

    public function resolveCollection($path)
    {
        if($path == "")
        {
            return $this;
        }
        else
        {
            return new Collection($this->session, $this->path."/".$path);
        }
    }

    public function resolveFile($path)
    {
        if($path == "")
        {
            return false;
        }
        else
        {
            return new File($this->session, $this->path."/".$path, $this->rootCollection);
        }
    }

    public function mkdir()
    {
        $ret = false;
        try
        {
            $conn = $this->session->open();
            $conn->mkdir($this->path, true);
            $ret = $conn->dirExists($this->path);
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
        }
        finally
        {
            $this->session->close($conn);
        }
        return $ret;
    }

    public function canEditMetaData()
    {
        $roles = $this->session->getRoles();
        if(array_key_exists("researcher", $roles) && $this->rootCollection)
        {

            if($this->rootCollection->getState() == "NEW" ||
               $this->rootCollection->getState() == "REVISED")
            {
                return true;
            }
        }
        else
        {
            return false;
        }
    }

    public function canSubmit()
    {
        $roles = $this->session->getRoles();
        if(array_key_exists("researcher", $roles) && $this->rootCollection)
        {
            if($this->rootCollection->getState() == "NEW" ||
               $this->rootCollection->getState() == "REVISED")
            {
                $path = $this->relativePath();
                error_log($path);
                if($path)
                {
                    return count(explode("/",$path))==1;
                }
                else
                {
                    return false;
                }
            }
        }
        else
        {
            return false;
        }
    }

    public function canApprove()
    {
        return false;
    }

    public function canReject()
    {
        return false;
    }

    public function rename($path2)
    {
        $ret = false;
        $target = $this->session->resolveCollection($path2);
        if($target === false)
        {
            error_log("ERR");
            return false;
        }
        try
        {
            $conn = $this->session->open();
            error_log(" $this->path -> ".$target->getPath());
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
        $acl = [];
        $que_result_coll = $connLocal->genQuery(
            array("COL_COLL_INHERITANCE", "COL_COLL_NAME", "COL_COLL_OWNER_NAME", "COL_COLL_ID"),
            array(new \RODSQueryCondition("COL_COLL_NAME", $this->path)));
        $que_result_users = $connLocal->genQuery(
            array("COL_DATA_ACCESS_NAME", "COL_DATA_ACCESS_USER_ID"),
            array(new \RODSQueryCondition("COL_DATA_ACCESS_DATA_ID", $que_result_coll['COL_COLL_ID'][0])));
        
        for($i=0; $i<sizeof($que_result_users["COL_DATA_ACCESS_USER_ID"]);$i++) {
            $que_result_user_info = $connLocal->genQuery(
                array("COL_USER_NAME", "COL_USER_ZONE"),
                array(new \RODSQueryCondition("COL_USER_ID", $que_result_users["COL_DATA_ACCESS_USER_ID"][$i])));
            if($que_result_user_info['COL_USER_NAME'][0] == $this->session->params['user'] &&
               $que_result_user_info['COL_USER_ZONE'][0] == $this->session->params['zone'])
            {
                $acl[] = ($que_result_users['COL_DATA_ACCESS_NAME'][$i] == "read object") ? "read" : $que_result_users['COL_DATA_ACCESS_NAME'][$i];
            }
        }
        if($this->session->params['user'] == "rods")
        {
            $acl[] = "read";
        }
        return $acl;
    }

}