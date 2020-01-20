<?php
/**
 * iRodsSession object
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
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
            $meta = $p->getMeta();
            foreach($meta as $m)
            {
                if($m->name == $field)
                {
                    $res = $p->rmMeta($m);
                }
            }
            try
            {
                // @todo: this throws exception even though the data was tasneem.
                // See bug RDM-195
                $p->addMeta(new \RODSMeta($field, $value));
            }
            catch(\Exception $ex)
            {
            }
            finally
            {
            }
            return true;
        }
        catch(\Exception $ex)
        {
            error_log("failed ".$ex->getMessage());
            return false;
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
