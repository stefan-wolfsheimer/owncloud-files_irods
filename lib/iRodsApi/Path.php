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

    public function isMetaDataEditable()
    {
        return false;
    }

    /** @todo move to business layer */
    public function canEditMetaData()
    {
        $roles = $this->session->getRoles();
        if(array_key_exists("researcher", $roles) && $this->rootCollection)
        {

            if($this->rootCollection->getState() == "NEW" ||
               $this->rootCollection->getState() == "REVISED")
            {
                return true;
            }
        }
        else
        {
            return false;
        }
    }

    /** @todo move to business layer */
    public function canSubmit()
    {
        return false;
    }

    /** @todo move to business layer */
    public function canApprove()
    {
        return false;
    }

    /** @todo move to business layer */
    public function canReject()
    {
        return false;
    }

    public function getMeta()
    {
        $p = $this->getIrodsPath();
        if($p === false)
        {
            throw new \Exception("Could not resolve iRODS path");
        }
        return $p->getMeta();
    }

    public function rmMeta(Array $names)
    {
        $lu = [];
        foreach($names as $k)
        {
            $lu[$k] = true;
        }
        try
        {
            $p = $this->getIrodsPath();
            if($p === false)
            {
                return false;
            }
            $meta = $p->getMeta();
            foreach($meta as $alu)
            {
                if(array_key_exists($alu->name, $lu))
                {
                    $p->rmMeta($alu);
                }
            }
            return true;
        }
        catch(Exception $ex)
        {
        }
        finally
        {
        }
        return false;
    }

    public function addMeta($field, $value)
    {
        try
        {
            $p = $this->getIrodsPath();
            if($p === false)
            {
                return false;
            }
            $p->addMeta(new \RODSMeta($field, $value));
            return true;
        }
        catch(Exception $ex)
        {
        }
        finally
        {
        }
        return false;
    }

    public function setMeta($field, $value)
    {
        try
        {
            $p = $this->getIrodsPath();
            if($p === false)
            {
                return false;
            }
            $p->addMeta(new \RODSMeta($field, $value));
            return true;
        }
        catch(Exception $ex)
        {
        }
        finally
        {
        }
        return false;
    }

    abstract public function stat();
    abstract public function getChildren();
    abstract public function filetype();
    abstract public function resolve($path, $root=null);
    abstract protected function getIrodsPath();
}
