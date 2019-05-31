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
                'field'=>'IBRIDGES_STATE',
                'type'=> 'str',
                'mode'=> 'ro',
                'required' => false),
            array(
                'field'=>'Identifier',
                'type'=> 'str',
                'mode'=> 'ro',
                'required' => false),
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
     */
    public function get($path)
    {
        try
        {
            $session = iRodsSession::createFromPath($path);
            $irodsPath = $session->resolvePath($this->stripMountPoint($path));
            if($irodsPath)
            {
                $data = $this->getMetaData($irodsPath, $session);
                $data = $this->validate($data, "warning");
                return $data;
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
        $data = array();
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
                $data = $this->getMetaDataFromRequest($irodsPath, $entries, $session, true);
                if($op == "update")
                {
                    $data = $this->validate($data, "warning");
                }
                else if($op == "submit")
                {
                    $data = $this->validate($data, "error");
                }
                else
                {
                    throw new \Exception("invalid operation '$op'");
                }
                if(!$data['error'])
                {
                    $data = $this->update($irodsPath, $data);
                    if(!$this->update($irodsPath, $data))
                    {
                        $data['error'] = 'failed to update MetaData';
                    }
                }
                if($op == "submit" && !$data['error'])
                {
                    $data = $this->submit($irodsPath, $data);
                }
                return $data;
            }
            else
            {
                throw new \Exception("invalid irods path");
            }
        }
        catch(\Exception $ex)
        {
            $data['error'] = "failed to update meta data: ".$ex->getMessage();
            return $data;
        }
    }

    public function update($irodsPath, $data)
    {
        $md = [];
        foreach($data['entries'] as $k=>$v)
        {
            if(!$v['readonly'])
            {
                $md[$k] = array();
                foreach($v['values'] as $v)
                {
                    $md[$k][$v] = true;
                }
            }
        }
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
        if(!$ret)
        {
            $data['error'] = 'failed to update MetaData';
        }
        return $data;
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

    private function getMetaData($irodsPath, $session)
    {
        $entries = [];
        $state = "NEW";
        $canEdit = $irodsPath->canEditMetaData();
        $orderedEntries = [];
        foreach($this->profile as $v)
        {
            $v['values'] = [];
            $v['readonly'] = ($v['mode'] == 'ro' || !$canEdit);
            $entries[$v['field']] = $v;
            $orderedEntries[] = $v['field'];
        }
        $meta = $irodsPath->getMeta();
        foreach($meta as $alu)
        {
            if($alu->name == "IBRIDGES_STATE")
            {
                $state = $alu->value;
            }
            if(array_key_exists($alu->name, $entries))
            {
                $entries[$alu->name]['values'][] = $alu->value;
            }
        }
        return array("path"=>$irodsPath->getPath(),
                     "file"=>basename($irodsPath->getPath()),
                     "can_edit_meta_data" => $irodsPath->canEditMetaData(),
                     "can_submit" => $irodsPath->canSubmit(),
                     "can_approve" => $irodsPath->canApprove(),
                     "can_reject" => $irodsPath->canReject(),
                     "fields" => $orderedEntries,
                     "entries"=> $entries,
                     "roles" => $session->getRoles(),
                     "warning" => false,
                     "error" => false,
                     "state" => $state,
                     "state_urls"=>$session->getUrlToFilteredCollections());
    }

    private function getMetaDataFromRequest($irodsPath, $input, $session, $loadMissing=true)
    {
        $entries = [];
        $state = "NEW";
        $canEdit = $irodsPath->canEditMetaData();
        $orderedEntries = [];
        $transformed = [];
        foreach($this->profile as $v)
        {
            $v['values'] = [];
            $v['readonly'] = ($v['mode'] == 'ro' || !$canEdit);
            if(!$v['readonly'] && array_key_exists($v['field'], $input))
            {
                $v['values'] = $input[$v['field']];
            }
            else
            {
                $v['values'] = [];
            }
            $entries[$v['field']] = $v;
            $orderedEntries[] = $v['field'];
        }
        if($loadMissing)
        {
            $meta = $irodsPath->getMeta();
            foreach($meta as $alu)
            {
                if($alu->name == "IBRIDGES_STATE")
                {
                    $state = $alu->value;
                }
                if(array_key_exists($alu->name, $entries) && $entries[$alu->name]['readonly'])
                {
                    $entries[$alu->name]['values'][] = $alu->value;
                }
            }
        }
        return array("path"=>$irodsPath->getPath(),
                     "file"=>basename($irodsPath->getPath()),
                     "can_edit_meta_data" => $irodsPath->canEditMetaData(),
                     "can_submit" => $irodsPath->canSubmit(),
                     "can_approve" => $irodsPath->canApprove(),
                     "can_reject" => $irodsPath->canReject(),
                     "fields" => $orderedEntries,
                     "entries"=> $entries,
                     "roles" => $session->getRoles(),
                     "warning" => false,
                     "error" => false,
                     "state" => $state,
                     "state_urls"=>$session->getUrlToFilteredCollections());
    }

    private function validate($data, $error_escalation)
    {
        $err = false;
        foreach($data['entries'] as $k => &$entry)
        {
            if($entry['required'] && $entry['mode'] == 'rw' && !$entry['readonly'] && !$entry['values'])
            {
                $entry[$error_escalation] = true;
                $err = 'There are missing MetaData entries';
            }
        }
        $data[$error_escalation] = $err;
        return $data;
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
