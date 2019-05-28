<?php
namespace OCA\files_irods\iRodsApi;
use OCA\files_irods\iRodsApi\iRodsSession;


/**
 * A Path to iRODS collection or object
 */
abstract class Path
{
    protected $session;
    protected $path;

    public function __construct(iRodsSession $session, $path)
    {
        $this->session = $session;
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function isReadable()
    {
        return false;
    }

    public function isUpdatable()
    {
        return false;
    }

    public function isCreatable()
    {
        return false;
    }

    public function isDeletable()
    {
        return false;
    }

    public function canEditMetaData()
    {
        return false;
    }

    public function canSubmit()
    {
        return false;
    }

    public function canApprove()
    {
        return false;
    }

    public function canReject()
    {
        return false;
    }

    abstract public function stat();
    abstract public function getChildren();
    abstract public function filetype();
    abstract public function resolve($path, $root=null);
    abstract public function resolveCollection($path);
    abstract public function resolveFile($path);
}
