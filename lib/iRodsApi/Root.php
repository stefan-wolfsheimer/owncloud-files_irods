<?php
namespace OCA\files_irods\iRodsApi;
use OCA\files_irods\iRodsApi\iRodsSession;
use OCA\files_irods\iRodsApi\Path;

/**
 * Virtual Root Folder
 */
class Root extends Path
{
    private $subpaths;

    public function __construct(iRodsSession $session, Array $subpaths)
    {
        parent::__construct($session, "");
        $this->subpaths = $subpaths;
    }

    public function stat()
    {
        return [ 'mtime' => time()-10,
                 'size' => false,
                 'atime' => time()-10];
    }

    public function getChildren()
    {
        return array_keys($this->subpaths);
    }

    public function getChildCollectionMapping()
    {
        return $this->subpaths;
    }

    public function filetype()
    {
        return "dir";
    }

    public function resolve($path, $root=null)
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
        else
        {
            return $this;
        }
    }

    public function isReadable()
    {
        return true;
    }

    protected function getIrodsPath()
    {
        return false;
    }
};
