<?php
namespace OCA\files_irods\Controller;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCA\files_irods\iRodsApi\iRodsSession;

class MetaController extends Controller
{
    private $profile;

    public function __construct($AppName, $request)
    {
        parent::__construct($AppName, $request);
        $this->profile = array(
            array(
                'field'=>'Identifier',
                'type'=> 'str',
                'mode'=> 'ro',
                'required' => true),
            array(
                'field' => 'Title',
                'type'=> 'str',
                'mode'=> 'rw',
                'required' => true),
            array(
                'field'=>'Creator',
                'type'=> 'str',
                'mode'=> 'rw',
                'required' => true),
            array(
                'field'=>'Description',
                'type'=> 'str',
                'mode'=> 'rw'));
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
        try
        {
            $session = iRodsSession::createFromPath($path);
            $irodsPath = $session->resolvePath($this->stripMountPoint($path));
            if($irodsPath)
            {
                return $this->getMetaData($irodsPath, $session, "warning");
            }
            else
            {
                throw new \Exception("invalid iRODS path");
            }
        }
        catch(\Exception $ex)
        {
            return array("error" => "failed to load metadata: ".$ex->getMessage());
        }
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
        try
        {
            $session = iRodsSession::createFromPath($path);
            if(!$entries)
            {
                $entries  = [];
            }
            $irodsPath = $session->resolvePath($this->stripMountPoint($path));
            if($irodsPath)
            {
                //@todo more efficient solution
                //currently: save meta data and then validate
                //future: first validate everything then save
                $md = $this->mapMetaDataForUpdate($irodsPath, $entries);
                if(!$this->update($irodsPath, $md))
                {
                    throw new \Exception("failed to update meta data");
                }

                if($op == "update")
                {
                    return $this->getMetaData($irodsPath, $session, "warning");
                }
                else if($op == "submit")
                {
                    $md = $this->getMetaData($irodsPath, $session, "error");
                    return $this->submit($irodsPath, $md);
                }
                else
                {
                    throw new \Exception("invalid operation '$op'");
                }
            }
            else
            {
                throw new \Exception("invalid irods path");
            }
        }
        catch(\Exception $ex)
        {
            return array("error" => "failed to update meta data: ".$ex->getMessage());
        }
    }

    public function update($irodsPath, $md)
    {
        $ret = $irodsPath->rmMeta(array_keys($md));
        if($ret)
        {
            foreach($md as $field => $values)
            {
                foreach($values as $value => $d)
                {
                    $ret &= $irodsPath->addMeta($field, $value);
                }
            }
        }
        return $ret;
    }

    public function submit($irodsPath, $md)
    {
        if(!$md['error'])
        {
            if(!$irodsPath->rmMeta(["IBRIDGES_STATE"]) ||
               !$irodsPath->setMeta("IBRIDGES_STATE", "SUBMITTED"))
            {
                throw new \Exception("failed to change state to SUBMITTED");
            }
            else
            {
                $md['state'] = "SUBMITTED";
            }
        }
        return $md;
    }

    private function getMetaData($irodsPath, $session, $error_escalation)
    {
        $entries = [];
        $state = "NEW";
        $meta = $irodsPath->getMeta();
        if($meta !== false)
        {
            foreach($meta as $alu)
            {
                if($alu->name == "IBRIDGES_STATE")
                {
                    $state = $alu->value;
                }
                else
                {
                    $entries[$alu->name][] = $alu->value;
                }
            }
            $entries = $this->mapMetaData($irodsPath, $entries, $error_escalation);
            $canEdit = $irodsPath->canEditMetaData();
            return array("path"=>$irodsPath->getPath(),
                         "file"=>basename($irodsPath->getPath()),
                         "can_edit_meta_data" => $irodsPath->canEditMetaData(),
                         "can_submit" => $irodsPath->canSubmit(),
                         "can_approve" => $irodsPath->canApprove(),
                         "can_reject" => $irodsPath->canReject(),
                         "entries"=> $entries,
                         "roles" => $session->getRoles(),
                         "warning" => $this->getWarning($irodsPath, $state, $entries),
                         "error" => $this->getError($irodsPath, $state, $entries),
                         "state" => $state);
        }
        else
        {
            throw new \Exception("failed to get metadata");
        }
    }

    private function getWarning($irodsPath, $state, $entries)
    {
        foreach($entries as $entry)
        {
            if(array_key_exists("warning", $entry))
            {
                return 'There are missing MetaData entries';
            }
        }
        return false;
    }

    private function getError($irodsPath, $state, $entries)
    {
        foreach($entries as $entry)
        {
            if(array_key_exists("error", $entry))
            {
                return 'There are missing MetaData entries';
            }
        }
        return false;
    }

    private function mapMetaData($irodsPath, Array $entries, $error_escalation=false)
    {
        $ret = [];
        $canEdit = $irodsPath->canEditMetaData();
        foreach($this->profile as $v)
        {
            if(array_key_exists($v['field'], $entries))
            {
                $v['values'] = $entries[$v['field']];
            }
            if($error_escalation && $v['required'] && $v['mode'] == 'rw' && $canEdit && (!$v['values']))
            {
                $v[$error_escalation] = true;
            }
            $v['readonly'] = ($v['mode'] == 'ro' || !$canEdit);
            $ret[] = $v;
        }
        return $ret;
    }

    private function mapMetaDataForUpdate($irodsPath, Array $entries)
    {
        $ret = [];
        $profile = [];
        foreach($this->profile as $v)
        {
            $profile[$v['field']] = $v;
        }
        $canEdit = $irodsPath->canEditMetaData();
        $ret = [];
        foreach($entries as $item)
        {
            if(array_key_exists($item['field'], $profile) &&
               $canEdit && $profile[$item['field']]['mode'])
            {
                $ret[$item['field']][$item['value']] = true;
            }
        }
        
        return $ret;
    }
};
