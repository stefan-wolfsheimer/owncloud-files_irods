<?php
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

    public function isLocked()
    {
        if($this->root_coll)
        {
            return $this->root_coll->isLocked();
        }
        else
        {
            return true;
        }
    }

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

    public function resolveCollection($path)
    {
        return false;
    }

    public function resolveFile($path)
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
        return sprintf("irods://%s:%s@%s:%d/%s",
                       $this->session->params['user'],
                       $this->session->params['password'],
                       $this->session->params['hostname'],
                       $this->session->params['port'],
                       $this->path);
    }

    protected function getStats($conn)
    {
        return $conn->getFileStats($this->path);
    }


    function rename($path2)
    {
        $ret = false;
        $target = $this->session->resolveFile($path2);
        if($target === false)
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
        $users = [];
        $parent = dirname($this->path);
        $filename = basename($this->path);
        $cond = array(new \RODSQueryCondition("COL_DATA_NAME", $filename),
                      new \RODSQueryCondition("COL_COLL_NAME", $parent));
        $que_result = $connLocal->genQuery(
            array("COL_USER_NAME", "COL_USER_ZONE", "COL_DATA_ACCESS_NAME"),
            $cond, array());
        if ($que_result === false) return false;
        $acl = [];
        for ($i = 0; $i < sizeof($que_result['COL_USER_NAME']); $i++)
        {
            if($que_result['COL_USER_NAME'][$i] == $this->session->params['user'] &&
               $que_result['COL_USER_ZONE'][$i] == $this->session->params['zone'])
            {
                $acl[] = ($que_result['COL_DATA_ACCESS_NAME'][$i] == "read object") ? "read" : $que_result['COL_DATA_ACCESS_NAME'][$i];
            }
        }
        if($this->irods_params['user'] == "rods")
        {
            $acl[] = "read";
        }
        return $acl;
    }

    protected function getIrodsPath()
    {
        $account = $this->session->getAccount();
        return  new \ProdsFile($account, $this->path);
    }
}
