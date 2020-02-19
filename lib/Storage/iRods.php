<?php
/**
 * Implementation of OwnCloud StorageAdapter
 * see \OCP\Files\Storage
 * see https://github.com/owncloud/core/blob/v10.0.10/lib/public/Files/Storage/StorageAdapter.php
 * see https://doc.owncloud.org/server/10.2/developer_manual/app/advanced/custom-storage-backend.html
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\Storage;
use Icewind\Streams\IteratorDirectory;
use \OCP\Files\Storage\StorageAdapter;
use \OCA\files_irods\iRodsApi\iRodsSession;
use \OCA\files_irods\iRodsApi\File;
use \OCA\files_irods\iRodsApi\Collection;
use \OCA\files_irods\iRodsApi\iRodsStreamHandler;

class iRods extends StorageAdapter
{
    public function __construct($params)
    {
        // possiable params:
        // "hostname":        string
        // "port":            string
        // "common_password": string
        // "using_pam":       1/0
        // "user":            string
        // "password":        string
        if(array_key_exists("common_password", $params)  && $params['common_password'] !== '')
        {
            $params["password"] = $params["common_password"];
            $params["user"] = \OC::$server->getUserSession()->getLoginName();
        }
        else
        {
            if(!array_key_exists("user", $params) || $params['user'] == '')
            {
                $params["user"] = \OC::$server->getUserSession()->getLoginName();
            }
        }
        $config = \OC::$server->getConfig();
        $mount_point_json = $config->getAppValue("files_irods", "irods_mount_points");
        if(!$mount_point_json)
        {
            throw new \Exception('empty mount point configuration');
        }
        if(!$mount_point_json)
        {
            $params['mount_points'] = array();
        }
        else
        {
            $params['mount_points'] = json_decode($mount_point_json, true);
        }
        $this->irodsSession = new iRodsSession($params);
        $this->logger = \OC::$server->getLogger();
        $this->irodsPaths = array();
        $this->deleted = array();
    }

    private function resolve($path)
    {
        if(array_key_exists ($path, $this->irodsPaths))
        {
            $ret = $this->irodsPaths[$path];
            $this->logger->debug("get irodsPath from cache $path -- ".$ret->getPath());
            return $ret;
        }
        else
        {
            $ret = $this->irodsSession->resolve($path);
            if(!$ret)
            {
                $this->logger->error('cannot resolve '.$path);
                return false;
            }
            $this->irodsPaths[$path] = $ret;
            $this->logger->debug("resolve irods path $path -- ".$ret->getPath());
            return $ret;
        }
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
        $collection = $this->resolve($path);
        $ret = false;
        if($collection && method_exists($collection,  "mkdir"))
        {
            $ret = $collection->mkdir($child);
        }
        $this->logger->debug("mkdir $path ".($ret ? "FAILED":"OK"));
        return $ret;
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
        $this->logger->debug("rmdir $path");
        $collection = $this->resolve($path);
        $ret = false;
        if($collection instanceof Collection)
        {
            $ret = $collection->rmdir();
        }
        $this->logger->debug("rmdir $path ".(!$ret ? "FAILED":"OK"));
        return $ret;
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
        $irodsPath = $this->resolve($path);
        if($irodsPath)
        {
            $files = $irodsPath->getChildren();
            $this->logger->debug("opendir $path");
            return IteratorDirectory::wrap($files);
        }
        else
        {
            $this->logger->debug("opendir $path FAILED");
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
        $irodsPath = $this->resolve($path);
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
        $irodsPath = $this->resolve($path);
        if($irodsPath === false)
        {
            $this->logger->debug("filetype $path FAILED ");
            return false;
        }
        else
        {
            $ft = $irodsPath->filetype();
            $this->logger->debug("filetype $path $ft");
            return $ft;
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
        $ret = $this->filetype($path) !== false;
        $this->logger->debug("file_exists $path $ret");
        return $ret;
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
        $irodsPath = $this->resolve($path);
        if($irodsPath instanceof File)
        {
            $ret = $irodsPath->unlink();
        }
        $this->logger->debug("unlink $path ".($ret ? "FAILED":"OK"));
        return $ret;
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
            $this->logger->debug("fopen $path $mode FAILED");
            return false;
        }
        iRodsStreamHandler::register_proto();
        switch ($mode) {
            case 'r':
            case 'rb':
                $url = $file->url();
                $this->logger->debug("fopen $path rb $url");
                return fopen($url, "r");
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
                $url = $file->url();
                $this->logger->debug("fopen $path w $url");
                return fopen($url, "w");
		}
        $this->logger->debug("fopen $path $mode FAILED");
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
        $this->logger->debug("touch $path $mtime");
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
        $ret = false;
        $file1 = $this->irodsSession->getNewFile($path1);
        $file2 = $this->irodsSession->getNewFile($path2);
        if($file1 instanceof File && $file2 instanceof File)
        {
            $ret = $file1->rename($file2);
            $this->logger->debug("rename $path1 $path2 ".($ret ? "TRUE":"FALSE"));
        }
        $this->logger->debug("rename $path1 $path2 ".($ret ? "FAILED":"OK"));
        return $ret;
    }

    public function isReadable($path)
    {
        $irodsPath = $this->resolve($path);
        if($irodsPath === false)
        {
            $this->logger->fatal("isReadable $path not resolvable");
            return false;
        }
        else
        {
            $ret = false;
            $ret = $irodsPath->isReadable();
            $this->logger->debug("isReadable $path ".($ret ? "TRUE":"FALSE"));
            return $ret;
        }
    }

    public function isUpdatable($path)
    {
        $irodsPath = $this->resolve($path);
        if($irodsPath === false)
        {
            $this->logger->fatal("isUpdatable $path not resolvable");
            return false;
        }
        else
        {
            $ret = false;
            $ret = $irodsPath->isUpdatable();
            $this->logger->debug("isUpdatable $path ".($ret ? "TRUE":"FALSE"));
            return $ret;
        }
    }

    public function isCreatable($path)
    {
        $irodsPath = $this->resolve($path);
        if($irodsPath === false)
        {
            $this->logger->error("isCreatable $path not resolvable");
            return false;
        }
        else
        {
            $ret = false;
            $ret = $irodsPath->isCreatable();
            $this->logger->debug("isCreatable $path ".($ret ? "TRUE":"FALSE"));
            return $ret;
        }
    }

    public function isDeletable($path)
    {
        $irodsPath = $this->resolve($path);
        if($irodsPath === false)
        {
            $this->logger->fatal("isDeletable $path not resolvable");
            return false;
        }
        else
        {
            $ret = false;
            $ret = $irodsPath->isDeletable();
            $this->logger->debug("isDeletable $path ".($ret ? "TRUE":"FALSE"));
            return $ret;
        }
    }
}
