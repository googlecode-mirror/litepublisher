<?php

class tlocalfiler extends tremotefiler {

public function getfile($file) {
		return file_get_contents($file);
	}

public function putcontent($filename, $content, $mode ) {
if (file_put_contents($filename, $content) === false) return false;
		$this->chmod($file, $mode);
		return true;
	}

public function upload($localfile, $filename) {
return file_put_contents($filename, file_get_contents($localfile)) !== false;
}

public function pwd() {
		return getcwd();
	}

public function chdir($dir) {
		return chdir($dir);
	}

public function chgrp($file, $group, $recursive = false) {
		if ( ! $this->exists($file) ) return false;
		if ( ! $recursive  || ! $this->is_dir($file) ) return @chgrp($file, $group);

		$file = rtrim($file, '/') . '/';
		$filelist = $this->getdir($file);
		foreach ($filelist as $filename) {
			$this->chgrp($file . $filename, $group, $recursive);
}
		return true;
	}

public function chmod($file, $mode = false, $recursive = false) {
if (!$mode && !($mode = $this->getmode($mode))) return false;
		if ( ! $this->exists($file) ) return false;
		if ( ! $recursive  || ! $this->is_dir($file) ) return @chmod($file, $mode);

		$file = rtrim($file, '/') . '/';
		$filelist = $this->getdir($file);
		foreach ($filelist as $filename) {
			$this->chmod($file . $filename, $mode, $recursive);
}
		return true;
	}

public function chown($file, $owner, $recursive = false) {
		if ( ! $this->exists($file) ) return false;
		if ( ! $recursive  || ! $this->is_dir($file) ) return @chown($file, $owner);

		$filelist = $this->getdir($file);
		foreach ($filelist as $filename) {
			$this->chown($file . '/' . $filename, $owner, $recursive);
		}
		return true;
	}

public function owner($file) {
return $self::getownername(@fileowner($file));
	}

public function getchmod($file) {
		return substr(decoct(@fileperms($file)),3);
}

public function group($file) {
return $this->getgroupname(@filegroup($file));
}

public function move($source, $destination, $overwrite = false) {
		if ( $this->copy($source, $destination, $overwrite) && $this->exists($destination) ) {
			$this->delete($source);
			return true;
}
			return false;
	}

public function delete($file, $recursive = false) {
		if ( empty($file)) return false;
		$file = str_replace('\\', '/', $file); //for win32, occasional problems deleteing files otherwise
		if ( $this->is_file($file) ) return unlink($file);
		if ( ! $recursive && $this->is_dir($file) ) return rmdir($file);

$result = true;
		if ($filelist = $this->getdir(rtrim($file, '/') . '/', true)) {
			foreach ($filelist as $filename => $fileinfo) {
$result = $this->delete($file . $filename, true ) && $result;
}
}
		if ( file_exists($file) && ! @rmdir($file) ) return  false;
return $result;;
	}

public function exists($file) {
		return file_exists($file);
	}

public function is_file($file) {
		return is_file($file);
	}

public function is_dir($path) {
		return is_dir($path);
	}

public function is_readable($file) {
		return is_readable($file);
	}

public function is_writable($file) {
		return is_writable($file);
	}

public function atime($file) {
		return fileatime($file);
	}

public function mtime($file) {
		return filemtime($file);
	}

public function size($file) {
		return filesize($file);
	}

public function mkdir($path, $chmod = false, $chown = false, $chgrp = false) {
		if ( ! @mkdir($path) ) return false;
return parent::mkdir($path, $chmod , $chown , $chgrp );
	}

public function getdir($path, $include_hidden = true, $recursive = false) {
		if ( $this->is_file($path) ) {
			$base = basename($path);
			$path = dirname($path);
		} else {
			$base = false;
		}
		if ( ! $this->is_dir($path) ) return false;

		if ($dir = @dir($path)) {
		$result = array();
		while (false !== ($name= $dir->read()) ) {
if (($name == '.') || ($name == '..')) continue;
			if ( ! $include_hidden && '.' == $name[0] ) continue;
			if ( $base && $name != $base) continue;
$fullname = $path.'/'.$name;
$a = $this->getfileinfo($fullname);
$a['name'] = $name;
			if ( 'd' == $a['type'] ) {
					$a['files'] = $recursive  ? $this->getdir($fullname, $include_hidden, true) :
array();
			}

			 $result[$name] = $a;
		}
		$dir->close();
		unset($dir);
		return $result;
	}
return false;
}

}//class