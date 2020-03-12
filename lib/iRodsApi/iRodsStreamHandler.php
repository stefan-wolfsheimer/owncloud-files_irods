<?php
/**
 * iRodsSession object
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\iRodsApi;
require_once("/var/www/lib/irods-php/prods/src/Prods.inc.php");
    
class iRodsStreamHandler {
    var $irods_port;
    var $irods_host;
    var $irods_account;
    var $irods_path;
    var $irods_mode;

    static function register_proto()
    {
        if(! in_array("irods", stream_get_wrappers()))
        {
            stream_wrapper_register("irods", self::class)
                or die("Failed to register protocol irods");
        }
    }

    private function getRescPath($path)
    {
        if(preg_match ('|^\\/(.*?)\\/(.*?)$|', $path, $matches))
        {
            return [$matches[1], $matches[2]];
        }
        else
        {
            return ["", ""];
        }
    }

    function stream_open($path, $mode, $options, &$opened_path)
    {
        $url = parse_url($path);
        list($this->irods_user,
             $this->irods_zone,
             $this->irods_auth_mode) = explode("#", urldecode($url['user']));
        list($this->irods_resc,
             $this->irods_path) = $this->getRescPath(urldecode($url['path']));
        $this->irods_host = urldecode($url['host']);
        $this->irods_port = array_key_exists('port', $url) ? $url['port'] : 1247;
        $this->irods_mode = $mode;
        $this->irods_password = urldecode($url['pass']);
        $this->irods_account = new \RODSAccount($this->irods_host,
                                                $this->irods_port,
                                                $this->irods_user,
                                                $this->irods_password,
                                                $this->irods_zone,
                                                $this->irods_resc,
                                                $this->irods_auth_mode);
        $this->irods_file = new \ProdsFile($this->irods_account, $this->irods_path);
        $this->irods_file->open($this->irods_mode, $this->irods_resc);
        $this->irods_file_eof = false;
        return true;
    }

    function stream_close()
    {
        $this->irods_file_eof = true;
        $this->irods_file->close();
    }

    function stream_read($count)
    {
        $buff = $this->irods_file->read($count);
        if(strlen($buff) == 0)
        {
            $this->irods_file_eof = true;
        }
        return $buff;
    }

    function stream_write($data)
    {
        $bytes = $this->irods_file->write($data);
        if($bytes != strlen($data))
        {
            return 0;
        }
        else
        {
            return strlen($data);
        }
    }

    function stream_eof()
    {
        return $this->irods_file_eof;
    }

    function stream_tell()
    {
        return $this->irods_file->tell();
    }

    function stream_seek($offset, $whence)
    {
        return $this->irods_file->seek($offset, $whence);
    }
}
