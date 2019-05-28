<?php
namespace OCA\files_irods\iRodsApi;

trait iRodsPath {
    protected $rootCollection = null;

    public function stat()
    {
        $ret = false;
        try
        {
            $conn = $this->session->open();
            $stat = $this->getStats($conn);
            $ret =  [
                'mtime' => $stat->mtime,
                'ctime' => $stat->ctime,
                'size' => (property_exists($stat, 'size') ? $stat->size : 0),
                'atime' => time()];
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

    public function acl()
    {
        $acl = false;
        try
        {
            $conn = $this->session->open();
            $acl = $this->getAcl($conn);
        }
        catch(Exception $ex)
        {
        }
        finally
        {
            $this->session->close($conn);
        }
        if($acl === false)
        {
            return false;
        }
        $ret = false;
        foreach($acl as $item)
        {
            if($item == "own")
            {
                return "own";
            }
            else if(!$ret)
            {
                $ret = $item;
            }
            else if($ret == "read" && $item == "write")
            {
                $ret = "write";
            }
        }
        return $ret;
    }

    public function isLocked()
    {
        if($this->rootCollection)
        {
            return $this->rootCollection->isLocked();
        }
        else
        {
            return true;
        }
    }


    public function isReadable()
    {
        $acl = $this->acl();
        return $acl == "read" || $acl == "write" || $acl == "own";
    }

    public function isUpdatable()
    {
        if($this->isLocked())
        {
            return false;
        }
        else
        {
            $acl = $this->acl();
            return $acl == "write" || $acl == "own";
        }
    }

    public function isCreatable()
    {
        if($this->isLocked())
        {
            return false;
        }
        else
        {
            $acl = $this->acl();
            return $acl == "write" || $acl == "own";
        }
    }

    public function isDeletable()
    {
        if($this->isLocked())
        {
            return false;
        }
        else
        {
            $acl = $this->acl();
            return $acl == "write" || $acl == "own";
        }
    }

    abstract public function getMeta();
    abstract public function rename($path2);
    abstract protected function getStats();
    abstract protected function exists($conn);
    abstract protected function getAcl($conn);
}
