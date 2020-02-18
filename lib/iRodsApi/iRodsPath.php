<?php
/**
 * iRodsSession object
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\iRodsApi;

trait iRodsPath
{
    protected $rootCollection = null;
    protected $acls = null;
    protected $stats = null;

    public function getRootCollection()
    {
        return $this->rootCollection;
    }

    public function stat()
    {
        if($this->stats != null)
        {
            return $this->stats;
        }
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
        $this->stats = $ret;
        return $ret;
    }

    /**
     * @return acl of Collection or File
     */
    public function acl()
    {
        if($this->acls !== null)
        {
            return $this->acls;
        }
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
                $ret = "own";
                break;
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
        $this->acls = $ret;
        return $ret;
    }

    /**
     * @return string path relative to root collection
     */
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

    public function isUpdatable()
    {
        $acl = $this->acl();
        return $acl == "write" || $acl == "own";
    }

    public function isCreatable()
    {
        $acl = $this->acl();
        return $acl == "own";
    }

    public function isDeletable()
    {
        $acl = $this->acl();
        return $acl == "own";
    }

    abstract public function getMeta();
    abstract public function rename($path2);
    abstract protected function getStats();
    abstract protected function exists($conn);
    abstract protected function getAcl($conn);
}
