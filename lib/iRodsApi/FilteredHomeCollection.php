<?php
/**
 * Aggregated list of home collections of a group of users.
 *
 * Example: 
 * Let user1 and user2 be in group users
 * $coll = new FilterHomeCollection($session, "users", "/tempZone/home/{USER}/Folder", array("STATE"=>"VALUE"))
 * Aggregates all direct subfolders of all users' home collections that have key-value pair "STATE" => "VALUE".
 *
 * Consider the following directory structure
 * /tempZone/home/user1/abc  (STATE=>VALUE)
 * /tempZone/home/user1/def
 * /tempZone/home/user2/ghi  (STATE=>VALUE)
 * /tempZone/home/user2/jkl  (STATE=>NONE)
 *
 * This will result in the following result
 * $coll->getChildren()
 * ["user 1 - abc", "user2 - ghi"]
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

class FilteredHomeCollection extends Path
{
    protected $predicate = null;
    private $userGroup = null;
    private $homeDirPattern = null;
    private $glue = " - ";

    /**
     * @param IRodsSession $session
     * @param string $userGroup show all users' home folder of this group
     * @param string $path iRODS path
     * @param array $predicate a map of metadata keys to values
     */
    public function __construct(iRodsSession $session, $userGroup, $path, $predicate=null)
    {
        parent::__construct($session, "");
        $this->predicate = $predicate;
        $this->userGroup = $userGroup;
        $this->homeDirPattern = $path;
    }

    public function getPredicate()
    {
        return $this->predicate;
    }

    public function getChildren()
    {
        $files = [];
        foreach($this->session->getUsersOfGroup($this->userGroup) as $user)
        {
            $irodspath = sprintf($this->homeDirPattern, $user);
            $coll = new FilteredCollection($this->session,
                                           $irodspath,
                                           $this->predicate);
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
