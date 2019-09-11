<?php
namespace OCA\files_irods\Storage;
use Icewind\Streams\IteratorDirectory;
use \OCP\Files\Storage\StorageAdapter;
use \OCA\files_irods\iRodsApi\iRodsSession;
use \OCA\files_irods\iRodsApi\File;
use \OCA\files_irods\iRodsApi\Collection;
use \OCA\files_irods\iRodsApi\iRodsStreamHandler;
use \OCA\files_irods\iRodsApi\iRodsPath;

/**
 * Implements the OwnCloud StorageAdapter interface
 */
class iRods extends StorageAdapter
{
    public function __construct($params)
    {
        $this->irodsSession = new iRodsSession($params);
        
    }

    /**
	 * Get the identifier for the storage.
	 *
	 * @return string "irods::{USER}#{ZONE}@{HOSTNAME}"
	 */

    public function getId()
    {
        return
            "irods::"
            .$this->irodsSession->params['user']."#"
            .$this->irodsSession->params['zone']."@"
            .$this->irodsSession->params['hostname'];
    }

    /**
	 * See http://php.net/manual/en/function.mkdir.php
     * 
     * @todo unit test or functional test
     * @toto test if function supports recursive mkdir
     * @todo throw exection if storage is not avaialble
     *
	 * @param string $path
	 * @return bool true on success, false otherwise
	 * @throws StorageNotAvailableException if the storage is temporarily not available
	 */
    public function mkdir($path)
    {
        $child = basename($path);
        $path = dirname($path);
        $collection = $this->irodsSession->resolve($path);
        if($collection && $collection instanceof Collection)
        {
            return $collection->mkdir($child);
        }
        else
        {
            return false;
        }
    }

    /**
	 * see http://php.net/manual/en/function.rmdir.php
	 *
     * @todo throw exection if storage is not avaialble
     *
	 * @param string $path
	 * @return bool true on success, false otherwise
	 * @throws StorageNotAvailableException if the storage is temporarily not available
	 */
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

    /**
	 * see http://php.net/manual/en/function.opendir.php
	 *
     * @todo throw exection if storage is not avaialble
     *
	 * @param string $path
	 * @return resource|false
	 * @throws StorageNotAvailableException if the storage is temporarily not available
	 */
    public function opendir($path)
    {
        $irodsPath = $this->irodsSession->resolve($path);
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

    /**
	 * see http://php.net/manual/en/function.stat.php
	 * only the following keys are required in the result: size and mtime
	 *
     * @todo throw exection if storage is not avaialble
     *
	 * @param string $path
	 * @return array|false
	 * @throws StorageNotAvailableException if the storage is temporarily not available
	 * @since 10.0
	 */
    public function stat($path)
    {
        $irodsPath = $this->irodsSession->resolve($path);
        return $irodsPath->stat();
    }

    /**
	 * see http://php.net/manual/en/function.filetype.php
	 *
	 * @param string $path
	 * @return string|false
     * @todo throw exection if storage is not avaialble
	 * @throws StorageNotAvailableException if the storage is temporarily not available
	 * @since 10.0
	 */
    public function filetype($path)
    {
        $irodsPath = $this->irodsSession->resolve($path);
        if($irodsPath === false)
        {
            return false;
        }
        else
        {
            return $irodsPath->filetype();
        }
    }

	/**
	 * see http://php.net/manual/en/function.file_exists.php
	 *
	 * @param string $path
	 * @return bool
     * @todo throw exection if storage is not avaialble
	 * @throws StorageNotAvailableException if the storage is temporarily not available
	 * @since 10.0
	 */
    public function file_exists($path)
    {
        return $this->filetype($path) !== false;
    }

    /**
	 * see http://php.net/manual/en/function.unlink.php
	 *
	 * @param string $path
	 * @return bool true on success, false otherwise
     * @todo throw exection if storage is not avaialble
	 * @throws StorageNotAvailableException if the storage is temporarily not available
	 * @since 10.0
	 */
    public function unlink($path)
    {
        $ret = false;
        $irodsPath = $this->irodsSession->resolve($path);
        if($irodsPath instanceof File)
        {
            return $irodsPath->unlink();
        }
        else
        {
            return false;
        }
    }

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource|false
	 * @throws StorageNotAvailableException if the storage is temporarily not available
	 * @since 10.0
	 */
    public function fopen($path, $mode)
    {
        $file = $this->irodsSession->getNewFile($path);
        if(!$file)
        {
            return false;
        }
        iRodsStreamHandler::register_proto();
        switch ($mode) {
            case 'r':
            case 'rb':
                return fopen($file->url(), "r");
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
                return fopen($file->url(), "w");
		}
		return false;
    }

    /**
	 * see http://php.net/manual/en/function.touch.php
	 * If the backend does not support the operation, false should be returned
	 *
	 * @param string $path
	 * @param int $mtime
	 * @return bool true on success, false otherwise
	 * @throws StorageNotAvailableException if the storage is temporarily not available
	 * @since 10.0
	 */
    public function touch($path, $mtime = null)
    {
        return false;
    }

    ///////////////////////////////////////////////////////////////////////////////////
    //
    // End of interface implementation
    //
    ///////////////////////////////////////////////////////////////////////////////////
    /**
     * Rename an iRods object $path1 to $path2
	 * @return bool true on success, false otherwise
	 * @throws StorageNotAvailableException if the storage is temporarily not available
     */
    public function rename($path1, $path2)
    {
        $file1 = $this->irodsSession->resolve($path1);
        $file2 = $this->irodsSession->getNewFile($path2);
        if($file1 instanceof File && $file2 instanceof File)
        {
            return $file1->rename($file2);
        }
        else
        {
            return false;
        }
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
        $irodsPath = $this->irodsSession->resolve($path);
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
        $irodsPath = $this->irodsSession->resolve($path);
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
        $irodsPath = $this->irodsSession->resolve($path);
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
        $irodsPath = $this->irodsSession->resolve($path);
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
