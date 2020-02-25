<?php
/**
 * Root
 * A Path class describing the Root mount point
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\iRodsApi;
use OCA\files_irods\iRodsApi\iRodsSession;
use OCA\files_irods\iRodsApi\Path;

/**
 * Virtual Root Folder
 */
class Root extends Path
{
    private $subpaths;
    private $mergedSubpaths;

    public function __construct(iRodsSession $session, Array $subpaths)
    {
        $path = array_key_exists("", $subpaths) ? $subpaths[""]->getPath() : "";
        parent::__construct($session, $path);

        $this->mergedSubpath = null;
        if(array_key_exists("", $subpaths))
        {
            $this->mergedSubpath = $subpaths[""];
            unset($subpaths[""]);
        }
        $this->subpaths = $subpaths;
    }

    public function getRootCollection()
    {
        return $this;
    }

    public function stat()
    {
        return [ 'mtime' => time()-10,
                 'size' => false,
                 'atime' => time()-10];
    }

    public function getChildren()
    {
        $ret = array_keys($this->subpaths);
        if($this->mergedSubpath)
        {
            $ret = array_merge($ret, $this->mergedSubpath->getChildren());
        }
        return $ret;
    }

    public function filetype()
    {
        return "dir";
    }

    public function resolve($path, $root=null)
    {
        if($path == "" || $path == ".")
        {
            return $this;
        }
        else
        {
            $chunks = explode("/", $path, 2);
            if(array_key_exists($chunks[0], $this->subpaths))
            {
                if(count($chunks) < 2)
                {
                    $chunks[] = "";
                }
                return $this->subpaths[$chunks[0]]->resolve($chunks[1]);
            }
            else if($this->mergedSubpath)
            {
                \OC::$server->getLogger()->debug("resolve mergedSubpath $path $root ".get_class($this->mergedSubpath));

                return $this->mergedSubpath->resolve($path, $root);
            }
        }
    }

    public function isReadable()
    {
        return true;
    }

    public function isUpdatable()
    {
        if($this->mergedSubpath)
        {
            return $this->mergedSubpath->isUpdatable();
        }
        else
        {
            return false;
        }
    }

    public function isCreatable()
    {
        if($this->mergedSubpath)
        {
            return $this->mergedSubpath->isCreatable();
        }
        else
        {
            return false;
        }
    }

    public function mkdir($name)
    {
        if($this->mergedSubpath)
        {
            return $this->mergedSubpath->mkdir($name);
        }
        else
        {
            return false;
        }

    }

    protected function getIrodsPath()
    {
        if($this->mergedSubpath)
        {
            return $this->mergedSubpath->getIrodsPath();
        }
        else
        {
            return false;
        }
    }
};
