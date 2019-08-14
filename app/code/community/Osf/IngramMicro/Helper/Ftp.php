<?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * FTP Helper
 *
 * @category    Osf
 * @package     Osf_IngramMicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_Helper_Ftp extends Mage_Core_Helper_Data {
    
   private $conn;
   private $dir;
    /**
     * Set Remote Directory
     * @param string $dir
     */
    public function setRemoteDirectory( $dir ) {
        $this->dir = $dir;
    }
    
    /**
     * Connect to FTP
     */
    public function ftpConnect($host,$user,$pass)
    {
        
        $this->conn = ftp_connect($host);
        if($this->conn === false){
            throw new Exception("Ftp: Could not connect!", 1);
        }
        if(!ftp_login($this->conn, $user, $pass)){
            throw new Exception("Ftp: Could not login!", 1);
        }
        if(!ftp_pasv($this->conn, true)){
            throw new Exception("Ftp: Could not enter passive mode!", 1);
        }
        
        if ( !ftp_chdir($this->conn, $this->dir) ) {
            throw new Exception("Ftp: Could not change directory!", 1);
        }
        
        return;// $this->conn;
    }  
    
    /**
     * Download a file from the server
     *
     * @param string $localFile
     * @param string $remoteFile
     * @param string $type
     * @return bool
     */
	public function downloadFile($localFile, $remoteFile, $type=FTP_BINARY)
	{
		return ftp_get($this->conn, $localFile, $remoteFile, $type);
	}
    
    /**
     * Close the ftp connection
     *
     * @param none
     * @return bool
     */
	public function ftpClose()
	{
		return ftp_close($this->conn);
	}
} 

/* Filename: Ftp.php */
/* Location: app/code/local/Osf/IngraMicro/Helper/Ftp.php */