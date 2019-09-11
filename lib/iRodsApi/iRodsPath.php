<?php
namespace OCA\files_irods\iRodsApi;

trait iRodsPath
{
    protected $rootCollection = null;

    public function getRootCollection()
    {
        return $this->rootCollection;
    }

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

    public function pathRelativeToRoot()
    {
        $prefix = $this->rootCollection->getPath();
        if(substr($this->path, 0, strlen($prefix)) == $prefix)
        {
            return substr($this->path, strlen($prefix));
        }
        else
        {
            return false;
        }
    }

    /** @todo move to business logic layer */
    public function getState()
    {
        $ret = false;
        if($this->rootCollection)
        {
            $relpath = $this->pathRelativeToRoot();
            list($first, $rest) = explode("/",$relpath,2);
            $path = $this->rootCollection->getPath().$first;
            try
            {
                $conn = $this->session->open();
                if($conn->dirExists ($path))
                {
                    $p = new Collection($this->session, $path);
                }
                else if($conn->fileExists ($path))
                {
                    $p = new File($this->session, $path);
                }
                else
                {
                    throw new \Exception("invalid path $path");
                }
                $irodsPath = $p->getIrodsPath();
                foreach($irodsPath->getMeta() as $alu)
                {
                    if($alu->name == "IBRIDGES_STATE")
                    {
                        $ret = $alu->value;
                        break;
                    }
                }
            }
            catch(\Exception $ex)
            {
            }
            finally
            {
                $this->session->close($conn);
            }
            return $ret;
        }
        else
        {
            return "NEW";
        }
    }

    public function isRootContainer()
    {
        $ret = false;
        if($this->rootCollection)
        {
            $relpath = $this->pathRelativeToRoot();
            list($first, $rest) = explode("/",$relpath,2);
            return ($rest == "");
        }
        else
        {
            return false;
        }
    }

    public function isReadable()
    {
        $acl = $this->acl();
        return $acl == "read" || $acl == "write" || $acl == "own";
    }

    /** @todo keep basic functionality but move
        business logic to different layer */
    public function isUpdatable()
    {
        $state = $this->getState();
        if($state != "NEW" && $state != "REVISED")
        {
            return false;
        }
        else
        {
            if($state == "REVISED" && $this->isRootContainer())
            {
                return false;
            }
            $acl = $this->acl();
            return $acl == "write" || $acl == "own";
        }
    }

    /** @todo keep basic functionality but move
        business logic to different layer */
    public function isCreatable()
    {
        $state = $this->getState();
        if($state != "NEW" && $state != "REVISED")
        {
            return false;
        }
        else
        {
            if($state == "REVISED" && $this->isRootContainer())
            {
                return false;
            }
            $acl = $this->acl();
            return $acl == "write" || $acl == "own";
        }
    }

    /** @todo keep basic functionality but move
        business logic to different layer */
    public function isDeletable()
    {
        $state = $this->getState();
        if($state != "NEW" && $state != "REVISED")
        {
            return false;
        }
        else
        {
            if($state == "REVISED" && !$this->isRootContainer())
            {
                return false;
            }
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
