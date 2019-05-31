<?php
namespace OCA\files_irods\Storage;
use Icewind\Streams\IteratorDirectory;
use \OCP\Files\Storage\StorageAdapter;
use \OCA\files_irods\iRodsApi\iRodsSession;
use \OCA\files_irods\iRodsApi\File;
use \OCA\files_irods\iRodsApi\Collection;
use \OCA\files_irods\iRodsApi\iRodsStreamHandler;
use \OCA\files_irods\iRodsApi\iRodsPath;

class iRods extends StorageAdapter
{
    public function __construct($params)
    {
        $this->irodsSession = new iRodsSession($params);
        
    }

    public function getId()
    {
        return
            "irods::"
            .$this->irodsSession->params['user']."#"
            .$this->irodsSession->params['zone']."@"
            .$this->irodsSession->params['hostname'];
    }

    public function mkdir($path)
    {
        $collection = $this->irodsSession->resolveCollection($path);
        if($collection)
        {
            return $collection->mkdir();
        }
        else
        {
            return false;
        }
    }

    public function rmdir($path)
    {
        $collection = $this->irodsSession->resolveCollection($path);
        if($collection)
        {
            return $collection->rmdir();
        }
        else
        {
            return false;
        }
    }

    public function opendir($path)
    {
        $irodsPath = $this->irodsSession->resolvePath($path);
        if($irodsPath)
        {
            $files = $irodsPath->getChildren();
            return IteratorDirectory::wrap($files);
        }
        else
        {
            return false;
        }
    }

    public function stat($path)
    {
        $irodsPath = $this->irodsSession->resolvePath($path);
        return $irodsPath->stat();
    }

    public function filetype($path)
    {
        $irodsPath = $this->irodsSession->resolvePath($path);
        if($irodsPath === false)
        {
            return false;
        }
        else
        {
            return $irodsPath->filetype();
        }
    }

    public function file_exists($path)
    {
        return $this->filetype($path) !== false;
    }

    public function unlink($path)
    {
        $ret = false;
        $irodsPath = $this->irodsSession->resolvePath($path);
        if($irodsPath instanceof File)
        {
            return $irodsPath->unlink();
        }
        else
        {
            return false;
        }
    }

    public function rename($path1, $path2)
    {
        $ret = false;
        $irodsPath = $this->irodsSession->resolvePath($path1);
        if($irodsPath instanceof File || $irodsPath instanceof Collection)
        {
            return $irodsPath->rename($path2);
        }
        else
        {
            return false;
        }
    }

    public function fopen($path, $mode)
    {
        iRodsStreamHandler::register_proto();
        $irodsPath = $this->irodsSession->resolveFile($path);
        if(!($irodsPath instanceof File))
        {
            return false;
        }
        switch ($mode) {
            case 'r':
            case 'rb':
                return fopen($irodsPath->url(), "r");
            case 'w':
			case 'wb':
			case 'a':
			case 'ab':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
                return fopen($irodsPath->url(), "w");
		}
		return false;
    }

    public function touch($path, $mtime = null)
    {
        return false;
    }

    private function checkRole($path, $func)
    {
        list($irods_rel_path, $irods_base) = $this->irodsSession->getIrodsRoot($path);
        if($irods_rel_path === false)
        {
            // something went wrong
            return false;
        }
        if(!$irods_base)
        {
            // at the root of the irods virtual folders
            return true;
        }
        else
        {
            // inside irods
            $irods_path = $irods_base."/".$irods_rel_path;
            try
            {
                $conn = $this->irodsSession->open();
                $role = $this->irodsSession->getRole($conn, $irods_path);
            }
            catch(Exception $ex)
            {
            }
            finally
            {
                $this->irodsSession->close($conn);
            }
            return $func($role);
            return ($role == 'own' || $role == 'write' || $role == 'read');
        }
    }

    public function isReadable($path)
    {
        $irodsPath = $this->irodsSession->resolvePath($path);
        if($irodsPath === false)
        {
            return false;
        }
        else
        {
            return $irodsPath->isReadable();
        }
    }

    public function isUpdatable($path)
    {
        $irodsPath = $this->irodsSession->resolvePath($path);
        if($irodsPath === false)
        {
            return false;
        }
        else
        {
            return $irodsPath->isUpdatable();
        }
    }

    public function isCreatable($path)
    {
        $irodsPath = $this->irodsSession->resolvePath($path);
        if($irodsPath === false)
        {
            return false;
        }
        else
        {
            return $irodsPath->isCreatable();
        }
    }

    public function isDeletable($path)
    {
        $irodsPath = $this->irodsSession->resolvePath($path);
        if($irodsPath === false)
        {
            return false;
        }
        else
        {
            return $irodsPath->isDeletable();
        }
    }
}
