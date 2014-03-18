<?php 
/*	Miser 1.9.1b
 *	A helper class that attempts to optimise and improve a websites user responsiveness
 * 	by re-organising, consolodating and minifying javascript and style sheets.
 * @author ShaunR
 * @copyright  Copyright ( c ) 2011 Shaun Rumbell [ShaunR@labview-tools.com]
 * @license    Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License
 * http://creativecommons.org/licenses/by-nc-sa/3.0/
 * No warrantee  expressed or implied. Use at your own risk. 
 *
 * Credits: Many thanks to Adam Johnson for his enduring patience and testing.
 */

class MiserHelper {
		
		//Enable/disable features
		private $endis_all				= TRUE;			// Enable/disable the helper (becomes transparent)	
		// Minifying
		private $endis_js_min 			= 3;		    // Enable/disable javascript minifcation 	0=OFF, 1=INLINE ONLY 2=FILE ONLY 3=INLINE+FILE
		private $endis_css_min 			= 3;			// Enable/disable CSS minification			0=OFF, 1=INLINE ONLY 2=FILE ONLY 3=INLINE+FILE
		private $endis_html_min			= 3;			// Enable/disable HTML minification			0=OFF, 1=MINIFY USING MINIFY_HTML OLD, 2=MINIFY USING QUICK, 3=MINIFY USING MINIFY_HTML FULL
		// Combining
		private $endis_js_combine		= TRUE;			// Enable/disable combining javascript into 1 file
		private $endis_css_combine		= TRUE;			// Enable/disable combining css into 1 file
		private $endis_imports_combine 	= TRUE;			// Enable/disable merging of @import files
		private $inline_js_to_file 		= TRUE;			// Include inline javascript in the merged file (only if endis_js_combine is TRUE)
		private $inline_css_to_file		= TRUE; 		// Include inline css in the merged file 		(only endis_css_combine is TRUE)
		// User Options
		private $Use_CDNS 				= TRUE;			// Use a pre-defined CDN instead of local scripts (e.g jquery)) 
		private $Use_Sprites			= FALSE;		// Use CSS sprites
		private $ga_loc					= 0;			// Relocate/remove google analytics code (1=MOVE TO HEAD, 2=REMOVE).
		private $ignore_selects			= FALSE; 		// Ignore IE select switches.
		private $eu_cookie				= "miser_1eu_cookie";			// The cookie name to detect when requiring GA to be removed from the page. This ust be changed to a unique value!
		private $del_cookies			= array();
		private $replace_keys			= FALSE;		// This switches the mode of adding keys. Default is to add, if replace is TRUE then the whole list is replaced.
		// Benchmark memory
		private $start 					= float;		// Used to benchmark execution time
		private $finish 				= float;		// Used to benchmark execution time
		// Category Lists
		private $top_Head_keys 			= array();		// Scripts that must be placed in the head
		private $top_Foot_keys 			= array();		// scripts that must be placed before script-links in the footer
		private $ignore_keys 			= array();		// Scripts that must be skipped ( ignored ) - leaving them in-place
		private $remove_keys 			= array();		// Scripts to be removed enirely from the page (used to replace CDNs)
		private $nofile_keys 			= array();		// Scripts and CSS files that must not be combined into files
		private $ga_keys				= array();		// analytics keywords to enable moving
		private $gads_keys				= array();		// google ads keywords to enable removing
		// Object holders
		private $CDNS					= NULL;			// Code Distribution Network object
		private $sprite					= NULL;			// Sprite generator object
		// Cache Files
		private $dir_css 				= '/css/';		
		private $dir_js					= '/js/';
		private $dir_cache 				= '';
		public  $cacheLifeTime 			= 604800; 		// Cache cleanup lifetime = 7 days
		public  $dir_rel 				= '';
		public  $lic 					= 'CC';
		// Regex
		private $regex_head_end = '#<\s*/head\s*>#i';
		private	$regex_body_start = '#<\s*body.*?>#i';
		
		const MISER_VERSION = '1.9.2';
		const MERGE_CSS		= '_merge.css';
		const MERGE_JS		= '_merge.js';
		
		// Initialisation
		function __construct($root = NULL) {
			$this->top_Foot_keys 	= array();
			$this->ga_keys			= array( '_gaq.push', 'ga.async','google-analytics' );	
			$this->gads_keys		= array( 'google_ad', 'show_ads.js','doubleclick.net' );			
			$this->ignore_keys 		= array( 'gmodules.com');
			$this->remove_keys  	= array( 'jsapi' );
			$this->nofile_keys  	= array( 'jquery.js');
			$this->load_support();
			
			//  Control Defines
			if (defined( 'MISER_ENABLE' ) )				$this->enable(MISER_ENABLE);
			if (defined( 'MISER_KEYS_REPLACE' ) )		$this->keys_replace		(MISER_KEYS_REPLACE);
			
			if (defined( 'MISER_KEYS_IGNORE' ) )		$this->keys_ignore 		(array_map('trim',explode(',',MISER_KEYS_IGNORE)),	$this->replace_keys );
			if (defined( 'MISER_KEYS_HEAD' ) )			$this->keys_top_head 	(array_map('trim',explode(',',MISER_KEYS_HEAD)),	$this->replace_keys );
			if (defined( 'MISER_KEYS_FOOT' ) )			$this->keys_top_footer 	(array_map('trim',explode(',',MISER_KEYS_FOOT)),	$this->replace_keys );
			if (defined( 'MISER_KEYS_REMOVE' ) )		$this->keys_remove 		(array_map('trim',explode(',',MISER_KEYS_REMOVE)),	$this->replace_keys );
			if (defined( 'MISER_KEYS_NO_FILE' ) )		$this->keys_no_file		(array_map('trim',explode(',',MISER_KEYS_NO_FILE)),	$this->replace_keys );
			if (defined( 'MISER_KEYS_GA' ) )			$this->keys_ga 			(array_map('trim',explode(',',MISER_KEYS_GA)),		$this->replace_keys );
			if (defined( 'MISER_COOKIES' ) )			$this->cookies			(array_map('trim',explode(',',MISER_COOKIES)),		$this->replace_keys );
				
			if (defined( 'MISER_MINIFY_CSS' ) )			$this->minify_css(MISER_MINIFY_CSS);
			if (defined( 'MISER_MINIFY_JS' ) )			$this->minify_js(MISER_MINIFY_JS);
			if (defined( 'MISER_MINIFY_HTML' ) ) 		$this->minify_html(MISER_MINIFY_HTML);	
			
			if (defined( 'MISER_COMBINE_JS' ) )			$this->combine_js(MISER_COMBINE_JS);	
			if (defined( 'MISER_COMBINE_CSS' ) )		$this->combine_css(MISER_COMBINE_CSS);
			if (defined( 'MISER_COMBINE_IMPORTS' ) )	$this->combine_imports(MISER_COMBINE_IMPORTS);
			
			if (defined( 'MISER_INLINE_JS_TO_FILE' ) )	$this->inline_js_to_file(MISER_INLINE_JS_TO_FILE);	
			if (defined( 'MISER_INLINE_CSS_TO_FILE' )) 	$this->inline_css_to_file(MISER_INLINE_CSS_TO_FILE);
			
			if (defined( 'MISER_ANALYTICS_LOC' ) )		$this->ga_location(MISER_ANALYTICS_LOC);
			
			if (defined( 'MISER_EU_COOKIE' ) )			$this->eu_cookie(MISER_EU_COOKIE);	
			if (defined( 'MISER_USE_CDNS' ) )			$this->use_CDN(MISER_USE_CDNS);	

			if (defined( 'MISER_DIR_CSS' ) )			$this->css_dir(MISER_DIR_CSS);		
			if (defined( 'MISER_DIR_JS' ) )				$this->js_dir(MISER_DIR_JS);
			if (defined( 'MISER_DIR_REL' ) )			$this->dir_rel=MISER_DIR_REL;
			
			if (defined( 'MISER_CACHE_LIFETIME' ) )		$this->cacheLifeTime = MISER_CACHE_LIFETIME;
			
		}
		
		/* Properties and Methods to get/set Misers options */
		
		// Retrieves the class version number		
		public function optimise($content){
			return $this->do_optimise($content);
		}
		// Retrieves the Miser version number		
		public function version(){
			$lic=glob(dirname(__FILE__)."/Miser_*\.pdf");
			if (is_array($lic) && !empty($lic)) {
				$this->lic = $lic[0];
				$this->lic = str_ireplace("Miser_","",basename($this->lic,".pdf"));
			}
			return self::MISER_VERSION." [".strtoupper($this->lic)."]";
		}
		//For win IIS DOCUMENT_ROOT
		public function set_root($root = NULL){
			if (!isset($root)) return $_SERVER['DOCUMENT_ROOT'];
			$root = $this->clean($root);
			$_SERVER['DOCUMENT_ROOT'] = $root;
		}
		//Gets/Sets the combined files directory for CSS		
		public function css_dir($dir = NULL){
			if (!isset($dir)) return $this->dir_css;
			if (stripos($dir,$_SERVER['DOCUMENT_ROOT']) !== FALSE)
				$dir = substr($dir, strlen($_SERVER['DOCUMENT_ROOT']));
			$this->dir_css = $dir;
		}
		//Gets/Sets the combined files directory for Javasvript
		public function js_dir($dir = NULL){
			if (!isset($dir)) return $this->dir_js;
			if (stripos($dir,$_SERVER['DOCUMENT_ROOT']) !== FALSE)
				$dir = substr($dir, strlen($_SERVER['DOCUMENT_ROOT']));
			$this->dir_js = $dir;	
		}	
		//Gets/Sets the CDN cache file directory
		public function cache_dir($dir = NULL){
			if (!isset($dir)) return $this->dir_cache;
			if (stripos($dir,$_SERVER['DOCUMENT_ROOT']) !== FALSE)
				$dir = substr($dir, strlen($_SERVER['DOCUMENT_ROOT']));
			$this->dir_cache = $dir;	
		}
		
		public function CDNS_cache($dir = NULL){
			$file = $this->clean($this->CDNS->cache_file());
			if (stripos($file,$_SERVER['DOCUMENT_ROOT']) !== FALSE)
				$file = substr($file, strlen($_SERVER['DOCUMENT_ROOT']));
				return $file;
		}
		public function CDNS(){
				return $this->CDNS;
		}
		// Gets/Sets the list of cookies to be delete if the EU compliance banner is shown
		public function cookies($val = array(), $replace = FALSE) {
			if (empty($val)) return $this->del_cookies;
			return $this->add_key($this->del_cookies, $val, $replace);
		}
		// Gets/Sets the list of javascript items to be relocated to the document head
		public function keys_top_head($val = array(), $replace = FALSE) {
			if (empty($val)) return $this->top_Head_keys;
			return $this->add_key($this->top_Head_keys, $val, $replace);
		}
		// Gets/Sets the list of javascript items to be relocated to the top of the foot
		// before the javascript links
		public function keys_replace($replace = NULL) {
			if (empty($replace)) return $this->replace_keys;
			$this->replace_keys = $replace;
		}
		// Gets/Sets the list of javascript items to be relocated to the top of the foot
		// before the javascript links
		public function keys_top_footer($val = array(), $replace = FALSE) {
			if (empty($val)) return $this->top_Foot_keys;
			return $this->add_key($this->top_Foot_keys, $val, $replace);
		}
		// Gets/Sets the list of javascript and CSS items to be ignored
		// and not subjected to the sorting and combining process.
		public function keys_ignore($val = array(), $replace = FALSE ) {
			if (empty($val))  return $this->ignore_keys;
			$s=$this->add_key($this->ignore_keys, $val, $replace);
			return $s;	
		}
		// Gets/Sets the list of javascript items that will be completely
		// removed from the document. This is mainly used by the script to remove
		// any blocks requesting JSAPI, but anything can be removed.
		public function keys_remove($val = array(), $replace = FALSE) {
			if (empty($val)) return $this->remove_keys;
			return $this->add_key($this->remove_keys, $val, $replace);
		}
		// Gets/Sets the list of javascript items that will be excluded from
		// consolidation into a single file if "inline_js_to_file is TRUE".
		public function keys_no_file($val = array(), $replace = FALSE) {
			if (empty($val)) return $this->nofile_keys;
			return $this->add_key($this->nofile_keys, $val, $replace);
		}
		public function keys_ga($val = array(), $replace = FALSE) {
			if (empty($val)) return $this->ga_keys;
			return $this->add_key($this->ga_keys, $val, $replace);
		}
		// En(dis)ables the entire script
		// Default: FALSE (ENALED)
		public function enable( $value = NULL ){
			if (!isset($value)) return $this->endis_all;
			$this->endis_all = $value;
		}
		// Sets the minfy style sheets option
		// Default: 3 (Minify all)
		public function minify_css( $value = NULL ){
			if (!isset($value)) return$this->endis_css_min;
			$this->endis_css_min = $value;
		}
		// Sets the minify javascript option
		// Default: 1 (Minify all)
		public function minify_js( $value = NULL ){
			if (!isset($value)) return $this->endis_js_min;
			$this->endis_js_min = $value;
		}
		// Sets the minify HTML option
		// Default: 1 (Minify HTML)
		public function minify_html( $value = NULL ){
			if (!isset($value)) return $this->endis_html_min;
			$this->endis_html_min = $value;
		}
		// Use code delivery networks for javascript files rather than locally hosted scripts
		// Default: TRUE (Use CDNS)
		public function use_CDN( $value = NULL ){
			if (!isset($value)) return $this->Use_CDNS && is_object($this->CDNS);
			$this->Use_CDNS = $value;
		}
		// Use code delivery networks for javascript files rather than locally hosted scripts
		// Default: FALSE (Don't use CSS sprites)
		public function use_sprites( $value = NULL ){
			if (!isset($value)) return $this->Use_Sprites && is_object($this->sprite);
			$this->Use_Sprites = $value;
		}
		// If 1, moves google analytics code in to the document head
		// If 2. removes GA completely
		// All other values result in GA being sorted and mmoved to the footer.
		//Default: 0 (Footer)
		public function ga_location( $value = NULL ){
			if (!isset($value)) return $this->ga_loc;
			$this->ga_loc = $value;
		}
		// Includes inline javascript in the merged javascript files
		// Default: FALSE (Do Not Include)
		public function inline_js_to_file( $value = NULL ){
			if (!isset($value)) return $this->inline_js_to_file;
			$this->inline_js_to_file = $value;
		}
		// Includes inline css in the merged css files
		// Default: TRUE (Do Not Include)
		public function inline_css_to_file( $value = NULL ){
			if (!isset($value)) return $this->inline_css_to_file;
			$this->inline_css_to_file = $value;
		}
		// Get/Sets the option to detect and merge @imports into the CSS file
		public function combine_imports( $value = NULL ){
			if (!isset($value)) return $this->endis_imports_combine;
			$this->endis_imports_combine = $value;
		}
		// Get/Sets the option to combine files into a single file
		public function combine_js( $value = NULL ){
			if (!isset($value)) return $this->endis_js_combine;
			$this->endis_js_combine = $value;
		}
		//Get/Set CSS merging
		public function combine_css( $value = NULL ){
			if (!isset($value)) return $this->endis_css_combine;
			$this->endis_css_combine = $value;
		}
		//Sets the cookie name for hiding Google Analytics code.
		public function eu_cookie( $value = NULL ){
			if (!isset($value)) return $this->eu_cookie;
			$this->eu_cookie = $value;
		}
		// clears the CSS, JS and CDN caches
		// If 1 clears JS and CSS
		// If 2 clears CDN
		// If 3 clears JS, CSS and CDN
		public function cache_clear( $value = 1,$checkAtime = FALSE ){
			return $this->clear_cache($value, $checkAtime);
		}
		// Returns the size of the css cache directory
		// Bytes=TRUE Returns the size in bytes
		// Bytes=FALSE Returns the size in friendly format (MB, KB etc))
		public function cache_css_size($bytes=FALSE){
			if($bytes) return $this->dir_size($this->dir_css);
			else return $this->format_bytes($this->dir_size($this->dir_css));
		}
		// Returns the size of the javascript cache directory
		// Bytes=TRUE Returns the size in bytes
		// Bytes=FALSE Returns the size in friendly format (MB, KB etc))
		public function cache_js_size($bytes=FALSE){
			if($bytes) return $this->dir_size($this->dir_js);
			else return $this->format_bytes($this->dir_size($this->dir_js));
		}
		// Causes Miser to reload the support files (e.g bridge)
		public function reload(){
			$this->load_support();
		}
		//Prints all public methods
		public function print_methods(){
			foreach ($this->get_methods() as $method) {
				echo "$method\n";
			}
		}
		// Gets all public methods and returns an array of thier names
		public function get_methods(){
			$reflect = new ReflectionObject($this);
			foreach ($reflect->getMethods() as $method) {
				if ($method->isPublic() && $method->name[0] !='_')
				$methods[] = $method->name;
			}
			return $methods;
		}
		// Gets the variables and returns an array of thier names and values
		public function get_vars(){
			$class_vars = get_object_vars($this);
			foreach ($class_vars as $name => $value) {
					$vars[$name]= $value;
			}
			return $vars;
		}
		// prints out the variable names and contents
		public function print_vars(){
			foreach ($this->get_vars() as $name => $value) {
				if (is_array($value)) {
					echo "$name :(";
					echo(implode(',',$value)).")\n";
				}
				else 
					echo "$name : $value\n";
			}
		}
		
		/* The main man. This is where the magic happens */
		
		Protected function do_optimise( $html ){			
			// Passthrough if we are disabled
			if (function_exists ( 'miserStartOptimise' )){
				call_user_func ( 'miserStartOptimise',$this );
			}
			if ( !$this->endis_all ) return $html;
			// Start Timer.
			$this->start = microtime( true );
			// Check to make sure $SERVER['DOCUMENT_ROOT'] is defined
			// as somethimesit isn't in Win IIS
			if (!$this->check_root()) return $html;
			// Check compliance cookie set
			$this->cookie_not_accept = (!empty($this->eu_cookie) && (empty($_COOKIE[$this->eu_cookie]) && !$this->cookie_override)) ;
			if ($this->cookie_not_accept) $this->ga_loc =2;
			if (!$this->cookie_not_accept) unset($this->compliance);

			//Check support is loaded
			$this->Use_CDNS 	= is_object($this->CDNS)	&& $this->Use_CDNS;
			$this->Use_Sprites 	= is_object($this->sprite) 	&& $this->Use_Sprites;
			if (!class_exists('JSMIN')) $this->endis_js_min = 0;
			// Init array variables
			$js = array();
			$js_links = array();
			$css = array();
			$css_links = array();
			$ico = array();
			$js_Top_Header= array();
			$js_Top_Footer= array();
			$css_Top_Links= array();
			$css_Top = array();
			
			// Generate CSS sprites if needed
			if ($this->use_Sprites){
				$css_sprite = $sprite->create($html);
			}
			
			// EU compliance banner
			// Injected first so that it can be oprimised with everything else
			if (!empty($this->compliance)) {
				$this->remove_keys =	$this->safe_merge($this->remove_keys, $this->gads_keys);
				if (is_array($this->ignore_keys))
					$this->ignore_keys =	array_diff ($this->ignore_keys, $this->gads_keys);
			    $this->compliance["CONTENT"] = str_ireplace('[MISER_COOKIE]',$this->eu_cookie,$this->compliance["CONTENT"]);
				$html = preg_replace($this->regex_head_end, 	$this->compliance["CSS"]."\n</head>", 	$html);
				$html = preg_replace($this->regex_head_end, 	$this->compliance["JS"]."\n</head>", 	$html);
				$bodyTag = preg_match ( $this->regex_body_start , $html ,$matches);
				if ($bodyTag){
					$html = str_ireplace($matches[0], 	$matches[0].$this->compliance["CONTENT"], 	$html);
					foreach ($this->del_cookies as $cookie) 
						if (!empty($cookie)) {
							setcookie($cookie, "", -1,DIR_REL."/");
						}
				}
			}
			else{
				$this->ignore_keys =	$this->safe_merge($this->ignore_keys, $this->gads_keys);
			}
			
			// Google analytics to head? If not it'll go in the footer.
			// Analytics uses https which is really, really slow so if you can, put it in the footer.
			
			switch ($this->ga_loc) {
				case 1: $this->top_Head_keys =	$this->safe_merge($this->top_Head_keys, $this->ga_keys);
						break;
				case 2:	$this->remove_keys =	$this->safe_merge($this->remove_keys, $this->ga_keys);
						break;
				}
			
			// find IF selects and add them to the ignores list
			if ( preg_match_all( '#<\s*!\s*--\s*\[\s*if.+\]-->#smUi',$html,$selects )>0 ) {
				foreach ( $selects[0] as $item ) {
				if ( preg_match_all( '#(?|href|src)\s*=\s*["\'](.+)["\']#i',$item,$urls) >0) {
						foreach ( $urls[1] as $url )
						$this->ignore_keys[] = $url;
						if (!$this->ignore_selects){
							$css_selects[] = $item;
							$html = str_replace( $item,'',$html );
						}
					}
				}
			}
			
			// Inline Javascript
			
			if ( preg_match_all( '#<\s*script\s*((type\s*=\s*["\']text/javascript["\'])|(language\s*=\s*["\']javascript["\']))?\s*>
								(.+)<\s*/script\s*>#smUix',$html,$_js )>0 ) {
				foreach ( $_js[0] as $item ) {
					// Ignored?
					if ( $this->CheckList( $item, $this->ignore_keys ) )			continue;
					// Not ignored - Process it.
					$html = str_replace( $item,'',$html );
					// Check to see if it needs to be removed
					if ($this->CheckList( $item, $this->remove_keys ) )				continue;
					// Categorise
					if ( $this->CheckList( $item, $this->top_Head_keys ) )			$js_Top_Header[] = $item; 
					else
						if ( $this->CheckList( $item, $this->top_Foot_keys ) )		$js_Top_Footer[] = $item;
					else
						if ( $this->CheckList( $item, $this->ga_keys ) )			$js_Top_Footer[] = $item;	
					else
						$js[] = $item;
				}	
			}
			// Minify top footer Js if required
			if (!empty($js_Top_Footer) && ($this->endis_js_min & 1)){
				$js_Top_Footer =  array(JSMin::minify( @implode('',$js_Top_Footer) ));
			}
			// Javascript links to files			
			$remove_items = array();
			if ( preg_match_all( '#<\s*script\s*(type\s*=\s*["\']text/javascript["\']\s*)?src=.+<\s*/script\s*>#smUi',$html,$_js_links )>0 ) {
				foreach ( $_js_links[0] as $item ) {
					// Ignored?
					if ( $this->CheckList( $item, $this->ignore_keys ) )			continue;
					// Not ignored - Process it.
					$html = str_replace( $item,'',$html );
					// Check to see if it needs to be removed
					if ($this->CheckList( $item, $this->remove_keys ) )				continue;
					// Categorise
					if ( $this->Use_CDNS &&  $this->CDNS->Exists( $item) )				$remove_items[] 	= $item;
					else
						if ( $this->CheckList( $item, $this->top_Head_keys ) )			$js_Top_Header[] 	= $item; 
					else 
						if ( $this->CheckList( $item, $this->top_Foot_keys ) ) 			$js_Top_Footer[]	= $item;	
					else
						$js_links[] = $item;
						
				}	
				// User wants CDN rather than locally hosted?
				if ( $this->Use_CDNS && is_object($this->CDNS)) {
						// Iterate through replaceable JS urls and add them to
						// the appropriate section.
						foreach ($remove_items as $item){
							// Check to see if it needs to go in the header from user defined list.
							if ( $this->CheckList( $item, $this->top_Head_keys ) ) {
								$js_Top[] = $this->CDNS->CDN($item);
								continue;
							}
							//Not in user list-use the default from cdns file.
							else $js_Top[] = $this->CDNS->CDN($item,'HEAD');
							$js_Foot[] = $this->CDNS->CDN($item,'FOOT');	
						}
						//Merge them with existing URLS
						$js_Top_Header = array_unique($this->safe_merge($js_Top, $js_Top_Header));
						$js_Top_Footer = array_unique($this->safe_merge($js_Foot, $js_Top_Footer));
						
						// Remove replaced urls from the links-they go in a different page location
						$js_links = array_unique(array_diff( $js_links, $remove_items ));						
				}				
				//force a re-index
				$js_links=  $this->safe_merge( $js_links ); 
			}
			
			// Style-sheet links to files	

			if ( preg_match_all( '#<\s*link\s*rel\s*=\s*"stylesheet".+>|<\s*link\s*href\s*=\s*".+"\s*rel\s*=\s*"stylesheet".*>#smUi',$html,$_css_links )>0 ) {
				foreach ( $_css_links[0] as $item ) {
					// Ignored?
					if ( $this->CheckList( $item, $this->ignore_keys ) )			continue;
					$html = str_replace( $item,'',$html );	
					// Remove?
					if ($this->CheckList( $item, $this->remove_keys ) )				continue;
					else
						$css_links[] = $item;
				}			
			}
			
			//if ( preg_match_all( '#<\s*img.*src=([\"\']?.+[\"\']?).*>#smUi',$html,$_img_links )>0 ) {
			//	foreach ( $_img_links[0] as $item ) {
					// Ignored?
			//		if ( $this->CheckList( $item, $this->ignore_keys ) )			continue;
					
					//$html = str_replace('src="','src="' .BASE_URL.'.nyud.net',$html);			
			//	}
			//}
			// Bookmark icons
			$ico = $this->find_replace( '#<\s*link.+image/x-icon.+>#iU',$html );
			
			// Inline-style-sheets
			$css_inlne = $this->find_replace( '#<\s*style.*>.*<\s*/style\s*\/?>#smUi',$html);
			$css_inlne = @implode( $css_inlne );
			
			// Add the sprite css if required
			if ($this->Use_Sprites)		$css_inlne .= $css_sprite;
			// CSS file merge
			if ($this->endis_css_combine){
				// Find those we can actually load from out server (i.e get rid of externally hosted and file not found)
				$_css_links= array_filter($css_links, array($this,url_exists));		//See callback "**"
				if ($this->inline_css_to_file) {						// Want inline in the file too.
					$title = $this->write_css($_css_links,$css_inlne);
					$css_inlne='';
				}
				else {
					$title = $this->write_css($css_links);				// Don't want inline, just links.
					if ( $this->endis_css_min & 1 ) $css_inlne = $this->css_minify( $css_inlne ) . "\n";
					}
				// Put it all tegether - Remove the links put in the file from the links list and add the link
				// for the merged file
				if ($title) {
					$css_links = @array_diff($css_links,$_css_links);	
					$css_links[] ='<link rel="stylesheet" type="text/css" href="'.$title .'" />';
					}
			}
			
			// JS file merge
			$js_inline = @implode( "\n",$js );
			// remove any script tags from inline JS
			if (!empty($js_inline))
				$js_inline =  preg_replace('#<\s*script\s*(type\s*=\s*["\']text/javascript["\']\s*)?>|
											<\s*script\s*language\s*=\s*["\']javascript["\']>|
											<\s*script\s*>|</script>#ix',"",$js_inline);
			if ($this->endis_js_combine){
				// Find those we can actually load from out server (i.e get rid of externally hosted and file not found)
				$_js_links= array_filter($js_links, array($this,url_exists));		//See callback "**"
				if ($this->inline_js_to_file) {							// Want inline in the file too.
					$title = $this->write_js( $_js_links,$js_inline);
					$js_inline='';
				}
				else {
					$title = $this->write_js( $_js_links );				// Don't want inline, just links.
					if ( $this->endis_js_min & 1) $js_inline =  JSMin::minify( $js_inline );	
				}
				// Put it all tegether - Remove the links put in the file from the links list and add the link
				// for the merged file
				if ($title) {
					$js_links = @array_diff($js_links,$_js_links);
					$js_links[] = '<script type="text/javascript" src="'.$title .'"></script>' ;}
			}
			
			
			if (!empty($css_inlne)){
				if ($this->endis_css_min & 1) $css_inlne = $this->css_minify( $css_inlne );
				$css_inlne = '<style type="text/css">' . $css_inlne . '</style>';	
			}
			
			if (!empty($js_inline)){
				if ($this->endis_js_min & 1) $js_inline =  JSMin::minify( $js_inline );
				$js_inline = '<script type="text/javascript">' . $js_inline . "</script>";
			}
			
			
			//To the Head
			$head = $this->safe_merge( $ico,$css_links,$css_inlne,$css_selects,$async,$js_Top_Header );
			$head = @implode( "",$head ) . "</head>";
			$html =  str_ireplace( '</head>', $head, $html );

			// To the footer
			$body = $this->safe_merge( $js_Top_Footer, $js_links,  $js_inline );
			$body = @implode( "",$body ) . "</body>";
			$html =  str_ireplace( '</body>', $body, $html );
			
			// calculate processing time
			$this->finish = microtime( true );
			$exec_time = $this->finish - $this->start;
			// Minfy HTML if required
			if ($this->endis_html_min>0)	$html = $this->html_minify($html);
			/* add exec time. Removing the following line is a breach of the License! */
			if (defined('SR_CACHE_VERSION')) $cache = "[Using SR Cache: ".SR_CACHE_VERSION."]";
			$html = preg_replace($this->regex_head_end,"\n<!-- Sorted by Miser ".$this->version() ." in ". round( $exec_time,3 ) ." Secs ".$cache." -->\n</head>", 	$html);					
			return $html;	
		}
		
		/* From this point on. Helper functions */
		
		// Find matches to the regex, deletes the text in $html and returns the replaced text;
		private function find_replace( $regex = '', &$html = null ) {
			if ( empty( $regex ) || !isset( $html ) ) return array();	// sanity check
			$found = array();
			// find and replace
			if ( preg_match_all( $regex,$html,$_found ) > 0 )
				foreach ( $_found[0] as $item ) {
				
					$html = str_replace( $item,'',$html);
					
					$found[] =  $item;
				}
			
			return $found;
		}
		// Check List - Return TRUE if it is in the provided list-FALSE if not
		// Does a partial, case insensitive string comparison
		private function CheckList( $item = '',$FilterList= array() ){
			foreach ( $FilterList as $ListItem )		
				if ( stripos( trim($item),trim($ListItem) ) !== FALSE ) return TRUE;					
			return FALSE;
		}
		
		// Writes the merged CSS file to disk and returns the location
		Protected function write_css( $files = array(),$the_rest=''){
			if ( !is_array( $files ) ) return FALSE;	// sanity check

			$dir = $_SERVER['DOCUMENT_ROOT'] . $this->dir_rel . $this->dir_css;
			// Get the files
			$contents = $this->get_files($files, '#href\s*=\s*[\"\'](.+?)[\"\']#i',TRUE). "\n";
			$contents .=$this->css_fixPaths (strip_tags($the_rest),"/") ;
			if ($this->endis_imports_combine)	$contents = $this->merge_imports($contents);
			if (empty($contents)) return FALSE;
			$hash=md5($contents);
			$filename = $hash .  self::MERGE_CSS;
			// Write the file only if it's changed.
			if (!file_exists($dir . $filename )){
				// Minify CSS?
				if ($this->endis_css_min & 2) $contents = $this->css_minify( $contents );
				$this->fsave($dir . $filename , $contents);
			}	
			//Return the path URI to the file so that it can be put as a link
			return $this->dir_css . $filename;
		}
		
		Protected function merge_imports($contents){
			static $count = 0;
			if ($count > 3) return ($contents);
			if (stripos($contents,'@import') === FALSE) return $contents;
			$num = preg_match_all( '#@import\s*(?:url\([\'"]?)?(.+?)[\'"\)]?;#i',$contents,$imports);
			if ( $num > 0 ){
				for ($i=0; $i < $num; $i++){
					$url = $this->clean($imports[1][$i]);
					if (stripos($url,"http") !== FALSE) continue;			// Ignore remote calls
					$_contents = $this->get_files(array($url),"#(".$url.")#",TRUE);	
					$contents = str_replace($imports[0][$i],$_contents,$contents);				
				}
				$contents = $this->merge_imports($contents);
			}
			$count++;
			
			return $contents;
		}
		
		// Writes the merged Javascript file to disk and returns the location
		Protected function write_js( $files = array(),$the_rest=''){
			if ( !is_array( $files ) ) return FALSE;
			
			$dir = $_SERVER['DOCUMENT_ROOT'] . $this->dir_rel . $this->dir_js;
			
			// Get the files
			$contents = $this->get_files($files, '#src\s*=\s*["\'](.+?)["\']#i',FALSE);
			if (empty($contents) && empty($the_rest)) return FALSE;
			$contents .= ";\n".$the_rest;
			//Check for changes
			$hash=md5($contents);
			$filename = $hash .  self::MERGE_JS;
			// Write the file only if it it's changed.
			if (!file_exists($dir . $filename)){
				// Minify?
				if ($this->endis_js_min & 2)
				//if ($this->endis_js_min & 2)				
					$contents =   JSMin::minify( $contents );	
				$this->fsave($dir . $filename, $contents);
			}	
			//Return the path URL to the file so that it can be put as a link
			
			return $this->dir_js . $filename;
		}
		
		// Retrieves the contents of a file list and merges them together.
		Protected function get_files($files=array(),$regex='',$replace_paths=FALSE){	
			if (empty($files) || empty($regex)) return  FALSE ;	
			foreach ( $files as $item ) {
				if (preg_match($regex,$item,$url )>0){
					$path = $this->clean($url[1]);	
					// this is in the miser_bridge_xyz.php
					$path = $this->find_path($path);					
					if ($path !== FALSE){							
						$_contents = file_get_contents( $path );
						if ($replace_paths) {
							$_contents = $this->css_fixPaths ($_contents,$path);	
							$contents .= "\n".$_contents."\n";
						}	
						else $contents .= $_contents.";\n";
					}						
				} 
			}
			return $contents;
		}

		// Writes the merged file to disk
		Protected function fsave($path='',$contents=''){
			if (empty($path) || empty($contents)) return;	//sanity check
			if (!file_exists($path)){
				$tmp = pathinfo($path,PATHINFO_DIRNAME);
				@mkdir($tmp, 0755,TRUE);
			}			
			if (version_compare(PHP_VERSION, '5.2.5', '>')) 	$fp = fopen($path, "c");
			else $fp = fopen($path, "a");
			// Get exclusive lock to the file.
			// We will just fail if unsuccessful and return.
			if(!@flock($fp, LOCK_EX | LOCK_NB)) {
				@fclose($fp);
				return FALSE;
			}
			ftruncate($fp,0);		
			fwrite($fp,$contents);	
			flock($fp, LOCK_UN);
			fclose($fp);
			chmod($path,0644);
			return TRUE;
		}
		
		// ** Callback function for array_diff when choosing links to combine (JS and CSS)
		Protected function url_exists($item=''){	
			if ($this->CheckList($item,$this->nofile_keys))return false;
			if (preg_match( '#(?:href|src)\s*=\s*["\'](.+?)["\']#i',$item,$url )>0){
				$path = $this->clean($url[1]);
				$path = $this->find_path($path);
				return $path !== FALSE;	
			}
			return false;					
		}
		// simple CSS minification
		Protected function css_minify( $css ) {
			$css = preg_replace('#/\*[^*]*\*+([^/][^*]*\*+)*/#', ' ', $css ); //comments
			$css = preg_replace( '#\s+#', ' ', $css );						 //Whitespace
			$css = str_replace( '; ', ';', $css );
			$css = str_replace( ': ', ':', $css );
			$css = str_replace( ' {', '{', $css );
			$css = str_replace( '{ ', '{', $css );
			$css = str_replace( ', ', ',', $css ); 
			$css = str_replace( ';)', ')', $css ); 
			return $css;
		} 

		Protected function html_minify( $html ) {
			$path = $this->clean(dirname(__FILE__)).'/miser_support/minify_HTML.php';
			if  (@file_exists( $path ) && ( $this->endis_html_min & 1 )){
				require_once $path;
				$options['fullCompress'] = $this->endis_html_min == 3;
				//$m = new Minify_HTML($html,$options);
				
				//var_dump($this->endis_html_min);
				return Minify_HTML::minify($html,$options);
			}
			else {
			
			//	$html = preg_replace('#\s*<!--\s*[^\[<>].*?(?<!!)\s*-->\s*#s', '', $html); 	//comments - also strips things like google ads
			$html = preg_replace( '#(^(\s{2,}))+|(\s{2,}+)$#sm', '', $html );				//Whitespace
			$html = str_replace( '< ', '<', $html );
			$html = str_replace( ' >', '>', $html );
			$html = str_replace( ' =', '=', $html );
			$html = str_replace( '= ', '=', $html );
			$html = str_replace( '/ ', '/', $html );
			$html = str_replace( ' /', '/', $html );
		}
			return $html;
		} 
		
		Protected function safe_merge(){
			$list = array();
			foreach (func_get_args() as $arg) {
				if (is_array($arg)) $list = array_merge($list,$arg);
				else $list = array_merge($list, array($arg));
			}
			return array_unique(array_filter($list));
		}
		
		// replaces css relative urls with full ones
		Protected function css_fixPaths ( $css = '', $scriptPath = '' ){
			if ( empty( $scriptPath ) ||  empty($css) ) return $css;						
			// remove file name
			if (stripos($scriptPath,$_SERVER['DOCUMENT_ROOT']) !== FALSE) 
				$scriptPath = substr($scriptPath,strlen($_SERVER['DOCUMENT_ROOT']));
			$s_path = @explode( '/',$scriptPath );
			@array_pop ( $s_path );	
			//find css urls 	
			if ( preg_match_all( '#url\((.+)\)#smUi',$css,$urls ) >0 ){
				// Found some - discard duplicates
				$urls = array_unique($urls[1]);	
				foreach( $urls as $url ){											// Process each url
					if (stripos($url,"http") 		!== FALSE) continue;			// Ignore remote calls
					if (stripos($url,"data:image/") !== FALSE) continue;			// Ignore embedded images
					$spath = implode( '/',$s_path )."/";
					$url_cleaned = $this->clean($url);
					$num = preg_match_all( '#\.\./#',$url,$backup );				// Detect relative parent paths
					// If none found then it is a sub directry.
					// If found then we need to back up the tree
					if ( $num>0 ) {	
						$path =	$s_path;											//a ). it's off of a parent directory.
						for ( $i = 0; $i < $num; $i++ )  @array_pop ( $path );
						
						$new_url =array( str_replace( '../','', $url_cleaned ) );		//Remove the ../ ( we know how many there are )
						$full_url = @implode( '/',array_merge( $path, $new_url ) );		//Create the full url from the scripts path and the relative path
						$full_url = '"'. $this->clean( $full_url) . '"';
						$css = str_replace( $url, $full_url, $css ); 				//replace all occurancies
					}
					else {															//b ). it's a subdirectory.
						if (stripos($url,'index.php') === FALSE){					
							if (!@file_exists( $_SERVER['DOCUMENT_ROOT'].$url_cleaned))
							$css = str_replace( $url, '"' .$this->clean( $spath.$url_cleaned) . '"', $css );				
						}
					}
				}
			}
			return $css;
		}
		// Strips quotes, double slashes and last slash if it exisits
		private function clean($txt){	
			if (substr($txt,-1) =="/") $txt  = substr($txt,0,-1);
			$txt = str_replace('//',"/",$txt);
			$txt = str_replace('\\',"/",$txt);
			$txt = str_replace('..',"",$txt);
			$txt = str_replace("\x00","",$txt);
			return trim(str_replace(array("'",'"','`',";"),"",$txt));
		}
		// Outputs debug strings
		Protected function debug($val="",$name=""){		
			echo("\n<!--$name ===>\n");
			var_dump($val);
			echo("\n-->");
		}
		//Used by methods to safely add to (or replace) an array list
		private function add_key(&$list = array(), $val = NULL, $replace = FALSE){
			if (!isset($val)) return	$list ;
			if ($replace) $list = array_unique($this->safe_merge($val));
			else  $list = array_unique($this->safe_merge( $list, $val));
		}
		
		//Windows IIS doesn't set the Document Root variable. So raise a warning and get
		// them to manually define it.
		private function check_root(){		
			if (empty($_SERVER['DOCUMENT_ROOT']) || !@is_dir($_SERVER['DOCUMENT_ROOT'])){
				trigger_error(
					'$_SERVER["DOCUMENT_ROOT"] Not Set Or Invalid - The path to the document root must be set using the set_root() method. ', E_USER_WARNING
				);
				return FALSE;
			}
			return TRUE;
		}
		private function load_support(){
			// Load CDNS
			$cdn_path = $this->clean(dirname(__FILE__)).'/miser_support/miser_CDNS.php';
			if (@file_exists($cdn_path)){
				require_once $cdn_path;
				$this->CDNS = new Miser_CDNS ();
			}
			//Load sprite generator
			$sprite_path = $this->clean(dirname(__FILE__)).'/miser/sprite_gen.php';
			if (@file_exists($sprite_path)){
				require_once $sprite_path;
				$this->sprite = new sprite_gen();
			}
			//Load JSmin
			$jsmin_path = $this->clean(dirname(__FILE__)).'/miser_support/jsmin.php';
			if (@file_exists($jsmin_path)){
				require_once $jsmin_path ;
			}
			// Load Compliance
			
			$this->compliance["JS"] = $this->clean(dirname(__FILE__)).'/miser_support/compliance/compliance.js';
			$this->compliance["CSS"] = $this->clean(dirname(__FILE__)).'/miser_support/compliance/compliance.css';
			$this->compliance["CONTENT"] = $this->clean(dirname(__FILE__)).'/miser_support/compliance/compliance.html';
			
			if (@file_exists($this->compliance["JS"]) && @file_exists($this->compliance["CSS"]) && @file_exists($this->compliance["CONTENT"]) ){
				$this->compliance["JS"]=str_ireplace($_SERVER['DOCUMENT_ROOT'],"",$this->compliance['JS']);
				$this->compliance["CSS"]=str_ireplace($_SERVER['DOCUMENT_ROOT'],"",$this->compliance['CSS']);
				$this->compliance["JS"]='<script type="text/javascript" src="'.$this->compliance['JS'].'"></script>';
				$this->compliance["CSS"]='<link rel="stylesheet" type="text/css" href="'.$this->compliance['CSS'].'" />';
				$this->compliance["CONTENT"]=@file_get_contents( $this->compliance["CONTENT"]);
				if ($this->compliance["CONTENT"] === FALSE){
					// Reported error on some windows platforms due to full path-remove ROOT
					$this->compliance["CONTENT"]=str_ireplace($_SERVER['DOCUMENT_ROOT'],"",$this->compliance['CONTENT']);
					$this->compliance["CONTENT"]=@file_get_contents( $this->compliance["CONTENT"]);
					if ($this->compliance["CONTENT"] === FALSE){
						unset($this->compliance); //Failed to load compliance contents-turn off.						
					}
				}
			}
			else{
				
				unset($this->compliance);
			}
			
			// Load CMS specific functions and variables
			if (defined( 'C5_EXECUTE' ))	$bridge = dirname(__FILE__).'/miser_support/miser_bridge_C5.php';
			if (defined( '_JEXEC' )) 		$bridge = dirname(__FILE__).'/miser_support/miser_bridge_JOOMLA.php';
			if (defined( 'PATH_tslib' )) 	$bridge = dirname(__FILE__).'/miser_support/miser_bridge_TYPO3.php';
			$bridge = $this->clean($bridge);
			if (@file_exists($bridge) && !empty($bridge))	require_once $bridge;
			//Load additional defines
			$defines_path = $this->clean(dirname(__FILE__)).'/miser_support/miser_defines.php';
			if (@file_exists($defines_path))	require_once $defines_path ;
		}
		// Generic interface to the CMS specific search functions located in the bridge file.
		private function find_path($path){
			if (empty($path)) return FALSE;
			if (function_exists('find_it')) $path = call_user_func('find_it', $path);
			$this->clean($path);
			$path = explode( "?",$path );
			$path = $path[0];
			if (stripos($path,$_SERVER['DOCUMENT_ROOT']) === FALSE) $path = $_SERVER['DOCUMENT_ROOT'] .$this->dir_rel . $path;
			if (@file_exists($path) && @is_file($path)) return $path;
			else return FALSE;	
		}
		Private function clear_cache($what=1,$checkAtime=FALSE){
			$ret=FALSE;
			if ($what & 1){
				$ret = 	$this->del_dir( $_SERVER['DOCUMENT_ROOT'] . $this->dir_css,'_merge.css',$checkAtime) + 
						 $this->del_dir( $_SERVER['DOCUMENT_ROOT'] . $this->dir_js, '_merge.js',$checkAtime);
			}
			if ($what & 2){
				$ret =$ret + $this->CDNS->Clear_Cache();
			}	
			return $ret;				
		}
		Private function del_dir($path=NULL,$ext='',$checkAtime=FALSE,$n=FALSE){
		
			if (strlen($path)<3) 		return FALSE;	//Sanity check
			if (!is_writable($path)) 	return FALSE;	//Sanity check
			if (!empty($ext)) $ext = '/*'.$ext;			
			else $ext = '/*';
			$path = $this->clean($path);				// TODO Being lazy here!
			// Delete recursively.
			if (is_file($path)){
				// It's a file.
				$ft = fileatime($path) ;
				// Check access time vs Cacheliftime
				if (!$checkAtime || (time()-$ft > $this->cacheLifeTime)){
					return @unlink($path);
				}
			}else{
				// It's a dir. List the contents and recurse
				$f = array_map(array( $this, 'del_dir' ),
								glob($path.$ext),
								array(),
								array($checkAtime),
								array($n)) ;
				$d = @rmdir($path);
				return ($f !== FALSE) || ($d !== FALSE);
			}
			return TRUE; // Only gets here if it is a file and is fresh
		}
		
		Private function dir_Size($dir=NULL) { 
			$size = 0; 
			if (strlen($dir)<2) return 0;			//sanity check
			if (file_exists($_SERVER['DOCUMENT_ROOT'].$dir)){
				foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'].$dir)) as $file){ 
					$size+=$file->getSize(); 
				} 
			}
			return $size; 
		} 
		// Pretty size.
		Private function format_bytes($bytes, $precision = 2) { 
			$units = array('B', 'KB', 'MB', 'GB', 'TB'); 
   
			$bytes = max($bytes, 0); 
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
			$pow = min($pow, count($units) - 1); 
   
			$bytes /= pow(1024, $pow); 
   
			return round($bytes, $precision) . ' ' . $units[$pow]; 
		} 
}

?>