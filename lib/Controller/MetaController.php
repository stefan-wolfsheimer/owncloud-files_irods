<?php
/**
 * MetaController
 * Controller class for editing iRODS meta data.
 * @todo implement
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
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

    /**
     * removes first part of the path
     * example: $path = /iRODS/path/to/my/file.txt
     *          return path/to/my/file.txt
     *
     * @param string $path 
     * @return string
     */
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
     * @todo implement
     */
    public function get($path)
    {
        throw new \Exception("Not implemented yet");
    }


    /**
     * @NoAdminRequired
     *
     * @param string $path
     * @param array $entries
     * @param string $op
     */
    public function put($path, $entries, $op)
    {
        //@todo different verbs patch delete instead of $op
        throw new \Exception("Not implemented yet");
    }
};
