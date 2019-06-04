<?php
namespace OCA\files_irods\iRodsApi;
use OCA\files_irods\iRodsApi\iRodsSession;
use OCA\files_irods\iRodsApi\Path;
use OCA\files_irods\iRodsApi\File;
use OCA\files_irods\iRodsApi\iRodsPath;
use OCA\files_irods\iRodsApi\Collection;

class FilteredHomeCollection extends Path
{
    protected $state = null;
    private $userGroup = null;
    private $homeDirPattern = null;
    private $glue = " - ";

    public function __construct(iRodsSession $session, $userGroup, $path, $state)
    {
        parent::__construct($session, "");
        $this->state = $state;
        $this->userGroup = $userGroup;
        $this->homeDirPattern = $path;
        
    }

    public function getState()
    {
        return $this->state;
    }

    public function getChildren()
    {
        $files = [];
        foreach($this->session->getUsersOfGroup($this->userGroup) as $user)
        {
            $irodspath = sprintf($this->homeDirPattern, $user);
            $coll = new FilteredCollection($this->session, $irodspath, $this->state);
            foreach($coll->getChildren() as $child)
            {
                $files[] = $user.$this->glue.$child;
            }
        }
        return $files;
    }

    public function resolve($path, $root=null)
    {
        if($path == "")
        {
            return $this;
        }
        if(!$root)
        {
            $root = $this;
        }
        $ret = $this;
        list($user, $restpath) = explode($this->glue, $path,  2);
        $irodspath = sprintf($this->homeDirPattern, $user);
        if($restpath)
        {
            $irodspath.= "/".$restpath;
        }
        try
        {
            $conn = $this->session->open();
            if($conn->dirExists($irodspath))
            {
                $ret = new Collection($this->session, $irodspath, $root);
            }
            else if($conn->fileExists ($irodspath))
            {
                $ret = new File($this->session, $irodspath, $root);
            }
            else
            {
                $ret = false;
            }
        }
        catch(Exception $ex)
        {
            $ret = false;
        }
        finally
        {
            $this->session->close($conn);
        }
        return $ret;
    }

    public function stat()
    {
        return [ 'mtime' => time()-10,
                 'size' => false,
                 'atime' => time()-10];
    }

    public function filetype()
    {
        return "dir";
    }

    public function isReadable()
    {
        return true;
    }

    protected function getIrodsPath()
    {
        return false;
    }

    private function _resolveIrodsPath($path)
    {
        list($user, $userPath) =  explode("/", $path, 2);
        $iRodsPath = sprintf($this->homeDirPattern, $user);
        if($userPath)
        {
            $iRodsPath.= "/".$userPath;
        }
        return $iRodsPath;
    }

}
