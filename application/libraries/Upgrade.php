<?php
/**
 * Upgrading  Library
 * Provides the necessary functions to do the automatic upgrade
 * 
 * @package    Sharing
 * @author     Ushahidi Team
 * @copyright  (c) 2008 Ushahidi Team
 * @license    http://www.ushahidi.com/license.html
 */
 
 class Upgrade {
 	
 	public $notices;
	public $errors;
 	public $success;
 	
 	public function __construct() {
 		$this->log = array();
 		$this->errors = array();
 	}
 	
 	/**
 	 * Fetches ushahidi from download.ushahidi.com
 	 * 
 	 * @param String url-- download URL
 	 */
 	public function download_ushahidi($url) {
 		$snoopy = new Snoopy();
       	$snoopy->agent = "Ushahidi upgrade script";
       	$snoopy->read_timeout = 30;
    	$snoopy->gzip = false;
        $snoopy->fetch($url);

       	if( $snoopy == '200' ) {
        	$this->log[] = $snoopy->results;
        	$this->log[] = "Downloading the latest ushahidi went successful.";
        	$this->success = true;        
          	return $snoopy->results;
            
       	} else {        	
        	$this->log[] = $snoopy->results;
            $this->errors[] = sprintf("Downloading the latest ushahidi failed. HTTP status code: %d", $snoopy->status);    
        	$this->success = false;
        	return $snoopy;
    	}
 			
 	}
 	
 	/**
 	 * Copy files recursively. 
 	 * 
 	 * @param String srcdir-- the source directory.
 	 * @param String dstdir -- the destination directory.
 	 */
 	public function copy_recursively($srcdir, $dstdir) {
 		if ( !is_dir($dstdir) && !@mkdir($dstdir) )
       	{
	    	$this->errors[] = sprintf("File <code>%s</coded> could not be copied",strip_abspath($dstdir));
	    	$this->success = false;
	    }

	    if ($curdir = opendir($srcdir))
	    {
	        while($file = readdir($curdir))
            {
            	if($file != '.' && $file != '..')
				{
		        	$srcfile = "$srcdir/$file";
		        	$dstfile = "$dstdir/$file";
		        	if(is_file($srcfile))
					{
			    		$ow = (is_file($dstfile))
			        		? filemtime($srcfile) - filemtime($dstfile) : $ow = 1;
			    		if($ow > 0) {
			        		if(copy($srcfile, $dstfile)) {
				    			touch($dstfile, filemtime($srcfile));
				    			$this->success = true;
                        	} else {
                            	$this->errors[] = sprintf("File <code>%s</coded> could not be copied",strip_abspath($dstdir));
				        		$this->success = false;
							}
			    		}
					}
					elseif(is_dir($srcfile)){ 
			    		$this->copy_recursive($srcfile, $dstfile);
			    		$this->success = true;
					}
		    	}
        	}

			closedir($curdir);
	    }
 			
 	}
 	
 	/**
 	 * Remove files recursively.
 	 * 
 	 * @param String dir-- the directory to delete.
 	 */
 	public function remove_recursively($dir) {
 		if (empty($dir) || !is_dir($dir))
	        return false;
	    if (substr($dir,-1) != "/")
	        $dir .= "/";
	    if (($dh = opendir($dir)) !== false) {
	        while (($entry = readdir($dh)) !== false) {
		    if ($entry != "." && $entry != "..") {
		        if ( is_file($dir . $entry) ) {
			    if ( !@unlink($dir . $entry) ) {
			        $this->errors[] = sprintf( 'File <code>%s</code> could not be deleted!', $this->strip_abspath($dir.$entry) );
			    }
			} elseif (is_dir($dir . $entry)) {
			    $this->remove_recursive($dir . $entry);
			}
		    }
		}
		closedir($dh);
		if ( !@rmdir($dir) ) {
		    $this->errors[] = sprintf( 'Directory <code>%s</code> could not be deleted!', $this->strip_abspath($dir.$entry) );
		}
			return true;
	    }
	    return false;
 		
 	}
 	
 	/**
 	 * Unzip the file.
 	 * 
 	 * @param String zip_file-- the zip file to be extracted.
 	 * @param String destdir-- destination directory
 	 */
 	
 	public function unzip_ushahidi($zip_file, $destdir) {
 		$archive = new PclZip($this->workdir.$this->wp_package);
		if (@$archive->extract(PCLZIP_OPT_PATH, $destdir) == 0)
		{
			$this->errors[] = sprintf( 'Error while extracting: <code>%s</code>',$archive->errorInfo(true) ) ;
			return false;
		}
		return true;
 	} 
 	
 	/**
 	 * Write the zile file to a file.
 	 * 
 	 * @param String zip_file-- the zip file to be written.
 	 * @param String dest_file-- the file to write.
 	 */
 	public function write_to_file($zip_file, $dest_file) {
 		$handler = fopen( $dest_file,'w');
       	$fwritten = fwrite($handler,$zip_file);
       	if( !$fwritten ) {
       		$this->errors[] = sprintf("The downloaded ushahidi zip file <code>%s</code>, couldn't be written.",$dest_file);
       		return false;
       	}
       	fclose($handler);
 		return true;
 	}
 }
?>
