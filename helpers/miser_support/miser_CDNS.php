<?php
/*	Miser CDNS 1.0.4
 *	A helper class for Miser that manages Content Delivery Network Lists
 * @author ShaunR
 * @copyright  Copyright ( c ) 2011 Shaun Rumbell [ShaunR@labview-tools.com]
 * @license    Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License
 * http://creativecommons.org/licenses/by-nc-sa/3.0/
 * No warrantee  expressed or implied. Use at your own risk. 
 *
 * Credits: Many thanks to Adam Johnson for his enduring patience and testing.
 */
class miser_CDNS{
		
		public $CDNS;
		private $cache_path;
		public $use_cache=TRUE;
		
		const CDNS_VERSION = '1.0.4';
		const CDN_FILE = '/CDNS_cache.txt';
		
		// the constructor initialises the CDN list with pre-dfined values from a cache file if
		// it exists. If not, then it adds the CDNS then creates the cache file for faster subsequent acccess.
		public function __construct($path=NULL) {
			// load the CDN list from cache if the file exists
			if (!empty($path))	$this->cache_path = $path 	. self::CDN_FILE;
			else $this->cache_path = dirname(__FILE__) 		. self::CDN_FILE;
			if (@file_exists($this->cache_path)) $this->CDNS = unserialize(file_get_contents($this->cache_path));
			else{
				$this->Add('modernizr',				'HEAD',0,	'cdnjs.cloudflare.com/ajax/libs/modernizr/2.0.6/modernizr.min.js');
				
				$this->Add('yui-min.js',			'FOOT',0,	'yui.yahooapis.com/3.3.0/build/yui/yui-min.js');
				$this->Add('jquery.min.js',			'FOOT',0,	'ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js');
				$this->Add('jquery.js',				'FOOT',0,	'ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js');
				$this->Add('prototype.js',			'FOOT',1,	'ajax.googleapis.com/ajax/libs/prototype/1.7/prototype.js');			
				$this->Add('swfobject.js',			'FOOT',2,	'ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js');				
				$this->Add('jquery.cycle.all.js',	'FOOT',2,	'ajax.microsoft.com/ajax/jquery.cycle/2.88/jquery.cycle.all.js');
				
				$this->Add('jquery.liveupdate.js',	'FOOT',2,	'cachedcommons.org/cache/jquery-liveupdate/0.0.0/javascripts/jquery-liveupdate-min.js');
				$this->Add('superfish.js',			'FOOT',2,	'cachedcommons.org/cache/jquery-superfish/1.4.8/javascripts/jquery-superfish-min.js');				
				//$this->Add('jquery.form.js',		'FOOT',2,	'cachedcommons.org/cache/jquery-form/2.4.3/javascripts/jquery-form-min.js');
				$this->Add('jquery.scrollto.js',	'FOOT',2,	'cachedcommons.org/cache/jquery-scrollto/1.4.2/javascripts/jquery-scrollto-min.js');
				$this->Add('jquery-cookie.js',		'FOOT',2,	'cachedcommons.org/cache/jquery-cookie/0.0.0/javascripts/jquery-cookie-min.js');
				$this->Add('jquery-easing.js',		'FOOT',2,	'cachedcommons.org/cache/jquery-easing/1.3.0/javascripts/jquery-easing-min.js');
				$this->Add('g-raphael.js',			'FOOT',2,	'cachedcommons.org/cache/g-raphael/0.4.1/javascripts/g-raphael.js');
				$this->Add('jquery.Jcrop',			'FOOT',1,	'cachedcommons.org/cache/jquery-jcrop/0.9.8/javascripts/jquery-jcrop.js');
				$this->Add('webfont.js',			'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/webfont/1.0.19/webfont.js');
				$this->Add('CFInstall.js',			'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/chrome-frame/1.0.2/CFInstall.min.js');
				$this->Add('CFInstall.min.js',		'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/chrome-frame/1.0.2/CFInstall.min.js');
				//$this->Add('jquery.ui.js',			'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/jqueryui/1.8.13/jquery-ui.min.js');
				$this->Add('dojo.xd.js',			'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/dojo/1.6.0/dojo.xd.js');
				$this->Add('galleria',				'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/galleria/1.2.3/galleria.min.js');
				$this->Add('json2',					'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/json2/20110223/json2.js');
				$this->Add('cufon.js',				'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/cufon/1.09i/cufon-yui.js');
				$this->Add('raphael',				'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/raphael/2.0.0/raphael-min.js');
				$this->Add('require.js',			'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/require.js/0.26.0/require.min.js');
				$this->Add('backbone.js',			'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/backbone.js/0.5.3/backbone-min.js');
				$this->Add('scriptaculous.js',		'FOOT',2,	'cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.8.3/scriptaculous.js');
				$this->Add('mootools-core.js',		'FOOT',1,	'cdnjs.cloudflare.com/ajax/libs/mootools/1.3.2/mootools-yui-compressed.js');
				$this->Add('ext-core.js',			'FOOT',1,	'cdnjs.cloudflare.com/ajax/libs/ext-core/3.1.0/ext-core.js');
				
				$this->Add('jquery.tools',			'FOOT',2,	'cdn.jquerytools.org/1.2.6/all/jquery.tools.min.js');// need to be smarter here...somehow
				
				$this->Add('jquery.backstretch.js',	'FOOT',3,	'cdn.ucb.org.br/Scripts/jquery.backstretch.js');
				
			}
		}
		
		 function __destruct() {
		 if (@!file_exists($this->cache_path) && (!empty($this->CDNS))) $this->Save();
		 unset($this->CDNS);
		 }
		// Add a CDN to the list.
		public function Add($name='', $position, $priority=0,$tag='',$dependancy = NULL){
			if (empty($name) || empty($tag)) return FALSE;
			$idx = $this->IndexOfName($name);
			// check to see if it exisits and return it if it does
			if ($idx !== FALSE) return $this->CDNS[$idx];
			// Nope. New one. Add it.
			$tag= '<script type="text/javascript" src="//'.$tag. '"></script>';
			$CDN = new miserCDN($name,$position, $priority, $tag, $dependancy);
			$this->CDNS[] =$CDN ;
			$this->Sort($this->CDNS);
			return $CDN;
		}
		
		// Deletes a CDN from the list.
		public function Delete($CDN=NULL){
			if (empty($CDN)) return;
			foreach ($this->CDNS as $item)
				if ($item === $CDN) {
					unset ($this->CDNS[$this->IndexOfName($item->Name())]);
					$this->CDNS = array_merge($this->CDNS);
					return TRUE;
			}
		}
		
		// Save CDNS to file cache.
		public function Save(){
			if ($this->use_cache)
			return @file_put_contents ($this->cache_path, serialize($this->CDNS));
		}
		// Deletes a CDN from the list.
		public function Clear_Cache(){
			if (@file_exists($this->cache_path)) return @unlink($this->cache_path);
		}
		// Return the CDN object by name.
		// If a section is defined. Only returns the object if it exists in that section
		// if "Section" is not defined; returns an object if it exists in any section.
		// Returns FALSE if not found.
		public function CDN($name='',$section=''){
			if (empty($name)) return FALSE;
			else
			foreach ($this->CDNS as $CDN){
				if (!empty($section)) {
					if (stripos($name, $CDN->Name()) !== FALSE && ($CDN->Position() == $section)) return $CDN;
				}
				else
					if (stripos($name, $CDN->Name()) !== FALSE) return $CDN;

			}
			return FALSE;
		}
		// Retrieve the index of the element with he name of....
		public function IndexOfName($name=null){
			if (empty($name)) return FALSE;
			else
			for ($i=0;$i<count($this->CDNS);$i++){
				if (is_object($this->CDNS[$i]) && $this->CDNS[$i]->Name() === $name) return $i;
				}
			return FALSE;
		}
		public function IndexOfTag($tag=null){
			if (empty($tag)) return FALSE;
			else
			for ($i=0;$i<count($this->CDNS);$i++){
				if (is_object($this->CDNS[$i]) && $this->CDNS[$i]->Tag() === $tag) return $i;
				}
			return FALSE;
		}
		// Is a CDN name present the list
		public function Exists($name=null){
			if (empty($name)) return FALSE;
			foreach ( $this->CDNS as $CDN )
				if (stripos($name, $CDN->Name()) ==  TRUE) return True;			
		}
		// Clear the list
		public function Clear(){
				unset ($this->CDNS) ;
				$this->CDNS = array();
		}
		// Number of CDNS in the list
		public function Count(){
			return count($this->CDNS);
		}
		// Sort Itms by priority
		public function Sort(&$items){
			usort($items, array($this,"cmp"));
		}
		// Internal Sort function callback. sorts the items by Priority
		Protected function cmp( $a, $b ){ 
			if(  $a->Priority() ==  $b->Priority() ){ return 0 ; } 
			return ($a->Priority() < $b->Priority()) ? -1 : 1;
	   } 
	   public function cache_file($path=NULL){
			if ($path === NULL) return $this->cache_path;
			else $this->cache_path = $path;
	   }
	   public function __toString(){
			return implode("\n",$this->CDNS);
		}
		
		public function version(){
			return self::CDNS_VERSION;
		}
		public function Get(){
			return $this->CDNS;
		}
}

class miserCDN{
		private $name;
		private $priority;
		private $tag;
		private $position;
		private $dependancy;
		
		function __construct($name, $position = 'HEAD',$priority = 0,$tag='',$dependancy = NULL){
			if (empty($name) || empty($tag)) return FALSE;
			$this->name =$name ;
			$this->priority = $priority;
			$this->tag = $tag;
			$this->position = $position;
			$this->dependancy = $dependancy;
		}
				 
		public function __toString(){
			
			return $this->tag;
		}
		
		public function Name($name =null){
			if (!is_null($name)) $this->name = $name;
			return $this->name;
		}
		
		public function Priority($priority = null){
			if (!is_null($priority)) $this->priority = $priority;
			return $this->priority;
		}
		
		public function Position($position = null){
			if (!is_null($position)) $this->position = $position;
			return $this->position;
		}
		
		public function Tag($tag = null){
			if (!is_null($tag)) $this->tag = $tag;
			$pos1=strpos($this->tag,'//')+2;
			$pos2=strpos(strrev($this->tag),'"')+1;
			$t= substr($this->tag,$pos1, -$pos2); 
			return $t;
		}	
		public function dependancy(){
			if (is_null($dependancy))return $this->dependancy;
			else return FALSE;
		}	
	}
?>