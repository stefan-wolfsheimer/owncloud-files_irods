<?php
namespace OCA\files_irods\iRodsApi;
require_once("irods-php/src/Prods.inc.php");  //@todo configure include path
use OCA\files_irods\iRodsApi\Path;
use OCA\files_irods\iRodsApi\Collection;
use OCA\files_irods\iRodsApi\Root;

class iRodsSession
{
    public $params = null;
    private $root = null;
    private $roles = null;
    private $storageMountPoint = null;

    public function __construct($params, $storageMountPoint = null)
    {
        $this->params = $params;
        $this->storageMountPoint = $storageMountPoint;
        if(!array_key_exists("zone", $this->params))
        {
            $this->params["zone"] = "tempZone";
        }
        if(!array_key_exists("irods_resc", $this->params))
        {
            $this->params["resc"] = "demoResc";
        }
        if(!array_key_exists("auth_mode", $this->params))
        {
            $this->params["auth_mode"] = "Native";
        }
        $this->getRoles();
        if(array_key_exists("researcher", $this->roles))
        {
            $this->root = new Root($this,
                                   [
                                       "LandingZone"=> new FilteredCollection($this,
                                                                              $this->home(),
                                                                              "NEW"),
                                       "Submitted"=> new FilteredCollection($this,
                                                                            $this->home(),
                                                                            "SUBMITTED"),
                                       "Revised"=> new FilteredCollection($this,
                                                                          $this->home(),
                                                                          "REVISED"),
                                       "Rejected"=> new FilteredCollection($this,
                                                                           $this->home(),
                                                                           "REJECTED"),
                                       "Archive"=> new Collection($this, sprintf("/%s/home/public",
                                                                                 $this->params['zone']))
                                   ]);
        }
        else if(array_key_exists("steward", $this->roles))
        {
            $this->root = new Root($this,
                                   [
                                       "Submitted"=> new FilteredCollection($this,
                                                                            $this->home(),
                                                                            "SUBMITTED"),
                                       "Revised"=> new FilteredCollection($this,
                                                                          $this->home(),
                                                                          "REVISED"),
                                       "Rejected"=> new FilteredCollection($this,
                                                                           $this->home(),
                                                                           "REJECTED"),
                                       "Archive"=> new Collection($this, sprintf("/%s/home/public",
                                                                                 $this->params['zone']))
                                   ]);
        }
        else
        {
            $this->root = new Root($this, []);
        }
    }

    public function home()
    {
        return sprintf("/%s/home/%s", $this->params['zone'], $this->params['user']);
    }

    public function getRoles()
    {
        if(!$this->roles)
        {
            $this->initRoles = [];
            $throwex = null;
            try
            {
                $conn = $this->open();
                $que = $conn->genQuery(
                    array("COL_USER_NAME", "COL_USER_GROUP_NAME"),
                    array(new \RODSQueryCondition("COL_USER_NAME", $this->params['user']),
                          new \RODSQueryCondition("COL_USER_ZONE", $this->params['zone'])));
                for($i=0; $i<sizeof($que["COL_USER_GROUP_NAME"]);$i++)
                {
                    if($que["COL_USER_GROUP_NAME"][$i] != $que["COL_USER_NAME"][$i])
                    {
                        $this->roles[$que["COL_USER_GROUP_NAME"][$i]] = true;
                    }
                }
            }
            catch(Exception $ex)
            {
                $throwex = $ex;
            }
            finally
            {
                $this->close($conn);
            }
            if($throwex)
            {
                throw $throwex;
            }
        }
        return $this->roles;
    }


    /**
     * Create an IRodsSession object from an owncloud path.
     * @param string $path
     * @return IRodsSession | false
     */
    public static function createFromPath($path)
    {
        $storageMountPoint = explode('/', ltrim($path, '/'), 2)[0];
        $storages = \OC::$server->query('UserStoragesService');
        $params = false;
        foreach($storages->getStorages() as $m)
        {
            if(ltrim($m->getMountPoint(), '/') == $storageMountPoint)
            {
                $params = $m->getBackendOptions();
                break;
            }
        }
        if($params === false)
        {
            throw \Exception("cannot resolve path '$path'");
        }
        return new IRodsSession($params, $storageMountPoint);
    }

    /**
     * @return RODSAccount
     */
    public function getAccount()
    {
        return  new \RODSAccount($this->params['hostname'],
                                 $this->params['port'],
                                 $this->params['user'],
                                 $this->params['password'],
                                 $this->params['zone'],
                                 $this->params['resc'],
                                 $this->params['auth_mode']);
    }

    /**
     * Open an iRODS connection
     */
    public function open()
    {
        $account = $this->getAccount();
        return \RODSConnManager::getConn($account);
    }

    public function close($conn)
    {
        if($conn)
        {
            \RODSConnManager::releaseConn($conn);
        }
    }

    public function getUrlToFilteredCollections()
    {
        $ret = array();
        foreach($this->root->getChildCollectionMapping() as $k=>$coll)
        {
            if($coll instanceof FilteredCollection)
            {
                $ret[$coll->getState()] = $this->storageMountPoint."/".$k;
            }
        }
        return $ret;
    }

    /**
     * Resolves the iRODS path from owncloud path
     * 
     * @param string $path owncloud path
     * @return string | false
     */
    public function resolvePath($path)
    {
        return $this->root->resolve($path);
    }

    public function resolveCollection($path)
    {
        return $this->root->resolveCollection($path);
    }

    public function resolveFile($path)
    {
        return $this->root->resolveFile($path);
    }

}
