<?php
/**
 * iRodsSession object
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\iRodsApi;
//@todo configure include path
require_once("irods-php/src/Prods.inc.php");
use OCA\files_irods\iRodsApi\Path;
use OCA\files_irods\iRodsApi\Collection;
use OCA\files_irods\iRodsApi\Root;
use OCA\files_irods\iRodsApi\File;
use OCP\Files\StorageNotAvailableException;

class iRodsSession
{
    public $params = null;
    private $root = null;
    private $roles = null;

    public function __construct($params)
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
        $collections = array();
        foreach($this->params["mount_points"] as $obj)
        {
            $coll = $this->createVirtualCollection($collections, $obj);
        }
        $this->root = new Root($this, $collections);
    }

    /**
     * @return home directory of current user
     */
    public function home()
    {
        return sprintf("/%s/home/%s",
                       $this->params['zone'],
                       $this->params['user']);
    }

    /**
     * Get iRODS groups of the current user
     * 
     * @return ascociative array that maps from group name to true
     */
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
     * @param $group string groupname
     * @return an array with all users of the group $group
     */
    public function getUsersOfGroup($group)
    {
        $throwex = null;
        $ret = [];
        try
        {
            $conn = $this->open();
            $que = $conn->genQuery(
                array("COL_USER_NAME", "COL_USER_GROUP_NAME"),
                array(new \RODSQueryCondition("COL_USER_GROUP_NAME", $group),
                      new \RODSQueryCondition("COL_USER_ZONE", $this->params['zone'])));
            for($i=0; $i<sizeof($que["COL_USER_NAME"]);$i++)
            {
                if($que["COL_USER_NAME"][$i] != $group)
                {
                    $ret[$que["COL_USER_NAME"][$i]] = true;
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
        return array_keys($ret);
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
     *
     * @return RODSConn
     * @throws RODSException
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

    /**
     * Resolves the iRODS path from owncloud path
     * 
     * @param string $path owncloud path
     * @return string | false
     */
    public function resolve($path)
    {
        return $this->root->resolve($path);
    }

    /**
     * Create a OCA\files_irods\iRodsApi\File object 
     * 
     * @param string $path OwnCloud path
     * @return File object
     */
    public function getNewFile($path)
    {
        $child = basename($path);
        $path = dirname($path);
        $irodsPath = $this->resolve($path);
        if(!($irodsPath instanceof Collection))
        {
            return false;
        }
        $file = new File($this,
                         $irodsPath->getPath()."/".$child,
                         $irodsPath->getRootCollection());
        return $file;
    }

    ///////////////////////////////////////////////////////////////////////////
    // Create a virtual collection from configuration object $obj
    //
    // add a new OCA\files_irods\iRodsApi\Collection object
    // to array $collection if all requirements are met
    //
    ///////////////////////////////////////////////////////////////////////////
    protected function createVirtualCollection(&$collections, $obj)
    {
        if(array_key_exists("if_group", $obj))
        {
            if(!array_key_exists($obj['if_group'], $this->roles))
            {
                // group is not allowed
                return false;
            }
        }
        if(array_key_exists("type", $obj))
        {
            $type = $obj['type'];
        }
        else
        {
            $type = '';
        }
        if(array_key_exists("path", $obj))
        {
            $path = str_replace (['{ZONE}',
                                  '{USER}',
                                  '{HOME}'],
                                 [$this->params['zone'],
                                  $this->params['user'],
                                  '/'.$this->params['zone']."/home/".$this->params['user']],
                                 $obj['path']);
        }
        else
        {
            $path = '';
        }
        switch($type)
        {
        case 'Collection':
            $collections[$obj['name']] = new Collection($this,
                                                        $path);
            return true;
            break;
        case 'FilteredCollection':
            $collections[$obj['name']] = new FilteredCollection($this,
                                                                $path,
                                                                $obj['filter']);
            return true;
            break;
        case 'FilteredHomeCollection':
            foreach($this->getUsersOfGroup($obj['group']) as $user)
            {
                $path = str_replace(["{ZONE}", "{USER}"],
                                    [$this->params['zone'], $user],
                                    $obj['path']);
                $name = str_replace("{USER}", $user, $obj['name']);
                $coll = new FilteredCollection($this,
                                               $path,
                                               $obj['filter']);
                if($coll->acl())
                {
                    //at least read
                    $collections[$name] = $coll;
                }
            }
            return true;
            break;
        default:
            throw StorageNotAvailableException("invalid type '$type' in configuration ".json_encode($obj));
        }
    }
}
