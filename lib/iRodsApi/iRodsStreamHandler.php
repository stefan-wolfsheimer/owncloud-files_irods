<?php
/**
 * iRodsSession object
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\iRodsApi;
require_once("/var/www/lib/irods-php/src/Prods.inc.php");

    
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

    function stream_open($path, $mode, $options, &$opened_path)
    {
        $url = parse_url($path);
        $this->irods_port = array_key_exists('port', $url) ? $url['port'] : 1247;
        $this->irods_path = $url['path'];
        $this->irods_mode = $mode;
        $this->irods_zone = "tempZone";
        $this->irods_resc = "demoResc"; // @todo make it part of url
        $this->irods_auth_mode = "Native";
        $this->irods_account = new \RODSAccount($url['host'],
                                                $this->irods_port,
                                                $url['user'],
                                                $url['pass'],
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
