<?php
namespace OCA\files_irods\Controller;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;

class VirtualCollectionsController extends Controller
{
    public function __construct($AppName, $request)
    {
        parent::__construct($AppName, $request);
    }

    /**
     * @NoAdminRequired
     */
    public function get()
    {
        $storages = \OC::$server->query('UserStoragesService');
        $mp = [];
        foreach($storages->getStorages() as $m)
        {
            if($m->getBackend() instanceof \OCA\files_irods\Backend\iRods)
            {
                $mp[] = $m->getMountPoint();
            }
        }
        return array("mount_points"=>$mp);
    }
};
