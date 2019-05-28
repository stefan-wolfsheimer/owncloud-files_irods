<?php
namespace OCA\files_irods\Controller;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCA\files_irods\iRodsApi\iRodsSession;

class MetaController extends Controller
{
    public function __construct($AppName, $request)
    {
        parent::__construct($AppName, $request);
    }

    public function getParams($path)
    {
        $storageMountPoint = explode('/', ltrim($path, '/'), 2)[0];
        $storages = \OC::$server->query('UserStoragesService');
        foreach($storages->getStorages() as $m)
        {
            //@todo fitler by mount point
            if(ltrim($m->getMountPoint(), '/') == $storageMountPoint)
            {
                $params = $m->getBackendOptions();
                return $params;
            }
        }
        return false;
    }

    public function stripMountPoint($path)
    {
        $tmp = explode('/', ltrim($path, '/'), 2);
        if(count($tmp) > 1)
        {
            return $tmp[1];
        }
        else
        {
            return "";
        }
    }

    /**
     * @NoAdminRequired
     */
    public function get($path)
    {
        $params = $this->getParams($path);
        if($params)
        {
            $session = new iRodsSession($params);
            $irodsPath = $session->resolvePath($this->stripMountPoint($path));
            if($irodsPath)
            {
                $entries = [];
                $state = "NEW";
                foreach($meta as $alu)
                {
                    if($alu->name == "IBRIDGES_STATE")
                    {
                        $state = $alu->value;
                    }
                    else
                    {
                        $entries[] = Array("name" => $alu->name,
                                           "value" => $alu->value,
                                           "units" => $alu->units);
                    }
                }
                $roles = $session->getRoles($params);
                return array("path"=>$irodsPath->getPath(),
                             "can_edit_meta_data" => $irodsPath->canEditMetaData(),
                             "can_submit" => $irodsPath->canSubmit(),
                             "can_approve" => $irodsPath->canApprove(),
                             "can_reject" => $irodsPath->canReject(),
                             "oc_path" => $path,
                             "entries"=> $entries,
                             "roles" => $roles,
                             "state" => $state);
            }
        }
        return array("error" => "failed to load metadata");
    }
};
