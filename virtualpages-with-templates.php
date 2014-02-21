<?php
/*
Plugin Name: Virtual Pages with Templates
Plugin URI: https://github.com/Link7/virtual-pages-with-templates
Description: A plugin to display virtual pages with support for shortcodes
Version: 1.0
Author: Link7
Author URI: https://github.com/Link7
License: GPL3
License URI: http://www.gnu.org/licenses/gpl.html
*/
if ( ! defined( 'ABSPATH' ) ) exit('No direct script access allowed'); // Exit if accessed directly

if (!class_exists('VirtualPagesTemplates'))
{
	class VirtualPagesTemplates
	{
		public $template = NULL;
        public $template_content = NULL;
        public $options = array();
        public $shortcodes = NULL;
        public $use_custom_permalink = FALSE;
        public $permalink_structure = NULL;
        public $custom_permalink_structure = NULL;
        public $keyword = NULL;
        public $notice = NULL;
        public $notice_iserror = FALSE;
        public $menu_slug = NULL;
        public $category_slug = NULL;
        private $is_virtual_page = FALSE;

        const ERR_URL = 1;
        const ERR_TEMPLATE = 2;
        const ERR_CATEGORY = 3;

		public function __construct() 
		{	
			if ( ! is_admin() )
			{
				add_filter('the_posts', array(&$this, 'create_virtual'));
				add_action('template_redirect', array($this, 'virtual_page_redirect'));

				remove_action('wp_head', 'rel_canonical');
				add_action('wp_head', array($this, 'vpt_rel_canonical'),10);

				remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 ); // Remove WordPress shortlink on wp_head hook
				add_action('wp_head', array($this, 'vpt_shortlink_wp_head'), 10, 0); // custom shortlink

				add_filter('body_class', array($this, 'vpt_body_class'), 20, 2);
				
			}else{
				add_action( 'admin_menu', array($this, 'display_menu') );
				register_uninstall_hook(__FILE__, array('VirtualPagesTemplates','vpt_uninstall_plugin'));

			}
			
			$this->permalink_structure = get_option('permalink_structure');
	  	}

	  	/**
		 * overrides the WP rel_canonical - use the correct canonical tags for virtual pages
		 *
		*/
	  	public function vpt_rel_canonical()
	  	{
	  		if ( !is_singular() )
				return;

			global $wp_the_query;
			if ( !$id = $wp_the_query->get_queried_object_id() )
				return;


			if ($this->is_virtual_page() && $id == $this->template->ID)
			{
				global $wp_rewrite;
				$link = $wp_rewrite->get_page_permastruct();
				$link = str_replace('%pagename%', get_page_uri( $GLOBALS['post'] ), $link);

				$link = home_url($link);
				$link = user_trailingslashit($link, $GLOBALS['post']->post_type);
			}else
				$link = get_permalink( $id );

			if ( $page = get_query_var('cpage') )
				$link = get_comments_pagenum_link( $page );

			echo "<link rel='canonical' href='{$link}' />\n";
	  	}

	  	/**
		 *  overrides the WP wp_get_shortlink - only display the shortlink for normal pages
		 *
		 *  Attached to the wp_head action.
		 *
		 * @uses vpt_shortlink_wp_head()
		 */
	  	public function vpt_shortlink_wp_head()
	  	{
	  		if (!$this->is_virtual_page())
	  		{
	  			$shortlink = wp_get_shortlink( 0, 'query' );

				if ( empty( $shortlink ) )
					return;

				echo "<link rel='shortlink' href='" . esc_url( $shortlink ) . "' />\n";
	  		}
	  	}

	  	/**
		 * deletes the body classes e.g. page-id-4 or postid-4 if a virtual post/page
		 *
		*/
	  	public function vpt_body_class($wp_classes)
	  	{
	  		if ($this->is_virtual_page())
	  		{
	  			foreach ($wp_classes as $k => $v)
	  			{
	  				if ($GLOBALS['post']->post_type == 'page')
	  				{
	  					if ($v == 'page-id-' . $GLOBALS['post']->ID) unset($wp_classes[$k]);
	  				}
	  				else
	  				{
	  					if ($v == 'postid-' . $GLOBALS['post']->ID) unset($wp_classes[$k]);
	  				}
	  			}
	  		}

	  		return $wp_classes;
	  	}
	  	
	  	/**
		* vpt_uninstall_plugin
		* 
		* completely removes the plugin installation
		*
		* @access public 
		* @return void
		*/
	  	public function vpt_uninstall_plugin(){
	  		delete_option('vpt_options');
	  	}

	  	/**
		* virtual_page_redirect
		* 
		* redirects to a virtual page/post if data being searched is not existing (posts/pages)
		*
		* @access public 
		* @return void
		*/
	  	public function virtual_page_redirect() {
		    if (is_search()) {
		        global $wp_query;

		        if ($this->options['affect_search'] )
		        {
		        	if (count($wp_query->posts) == 0  || !is_null($this->is_virtual_page()) && $wp_query->post->ID == $this->template->ID)
		        	{
		        		$structure = $this->permalink_structure;
			        	if ($this->use_custom_permalink){
			        		$structure = $this->options['virtualpageurl'];
				        }

			        	if (strpos($structure, '%postname%')){
			        		$structure = str_replace('%postname%', $wp_query->query['s'] , $structure) ;
			        		$structure = str_replace('%category%', $this->category_slug , $structure) ;
			        		wp_redirect( $structure );
			        	}
			        }	
		        }
		    }
		}

	  	/**
		* display_menu
		* 
		* add's the menu to the admin / dashboard
		*
		* @access public 
		* @return NONE
		*/
	  	public function display_menu()
		{
			$this->menu_slug = add_options_page( 'Virtual Page Settings', 'Virtual Page Settings', 'manage_options', dirname(__FILE__) . '/form.php' );
			$this->menu_slug = str_replace('settings_page_', '', $this->menu_slug) . '.php';

			// use `admin_print_scripts` instead of `admin_enqueue_scripts` so this only loads on this specific form and NOT on all admin pages
			add_action('admin_print_scripts-' . $this->menu_slug, array($this, 'admin_includes') );

			// load on checking of $_POSTs when on this page
			add_action('load-'.$this->menu_slug, array($this,'check_posts'));
			
		}

		/**
		* check_posts
		* 
		* used in the dashboard, checks if there's an update and save into DB - wp_options
		*
		* @access public 
		* @return NONE
		*/
		public function check_posts()
		{	
			if(isset($_POST['vpt_hidden']) && $_POST['vpt_hidden'] == 'Y') {  
				unset($_POST['vpt_hidden']);
				unset($_POST['submit']);
			
				$extra = '';
				
				if (isset($_POST['use_custom_permalink_structure']) && empty($_POST['virtualpageurl']))
				{
					$extra = '&error=' . self::ERR_URL ;
				}
				elseif (!isset($_POST['page_template']))
				{
					$extra = '&error=' . self::ERR_TEMPLATE ;
				}
				elseif (isset($_POST['use_custom_permalink_structure']) && strpos($_POST['virtualpageurl'],'%category%') !== false && get_post($_POST['page_template'])->post_type == 'page')
				{
					$extra = '&error=' . self::ERR_CATEGORY ;
				}
				else
				{
				$_POST['use_custom_permalink_structure'] = isset($_POST['use_custom_permalink_structure']) ? $_POST['use_custom_permalink_structure'] : '0';
				$_POST['affect_search'] = isset($_POST['affect_search']) ? $_POST['affect_search'] : '0';
				update_option('vpt_options', $_POST);
					$extra = '&settings-updated=true';
				}
				
				wp_redirect(admin_url('options-general.php?page=' . $this->menu_slug . $extra));
				
			}

			if ( ! empty( $_GET['settings-updated'] ) ) 
			{
				$this->notice = 'Settings saved.';
				add_action('admin_notices', array($this, 'display_notification'));
			}

			if ( ! empty( $_GET['error'] ) ) 
			{
				$this->set_error_message($_GET['error']);
				$this->notice_iserror = TRUE;
				add_action('admin_notices', array($this, 'display_notification'));
			}
		}

		/**
		* set_error_message
		* 
		* sets the error message based on the given error code
		*
		* @access public 
		* @return NONE
		*/
		private function set_error_message($error){
			switch ($error) {
				case self::ERR_URL:
					$this->notice = 'Please indicate the custom Virtual Page URL.';
					break;
				case self::ERR_TEMPLATE:
					$this->notice = 'Page template is required. You can make a template by creating a <a href="'.admin_url('post-new.php').'">post</a> or a <a href="'.admin_url('post-new.php?post_type=page').'">page</a> as save it as draft.';
					break;
				case self::ERR_CATEGORY:
					$this->notice = 'The `%category%` tag will not work on pages.';
					break;					
				default:
					# code...
					break;
			}
		}

		/**
		* init_keyord
		* 
		* initialize the keyword
		*
		* @access public 
		* @return void
		*/
		public function init_keyword($current_url_trimmed, $virtualpageurl_trimmed){
			global $wp,$wp_query;
			if (isset($wp_query->query['name']) and $wp_query->query['name'])
            {
            	$this->keyword = $wp_query->query['name'];
            }
            else
            {
            	$needles = array('%postname%', '%category%');
            	$replacements_regex = array(
                	'(?<postname>[^/]+)',
                	'(?<category>[^/]+)'
            	);
            	$regex = str_replace($needles, $replacements_regex, $virtualpageurl_trimmed);
            	$regex = str_replace('/', "\/", $regex);

            	$match = preg_match('/(?Ji)^' . $regex.'/', $current_url_trimmed, $matches);
            	if (!empty($matches) && count($matches) > 0 && !empty($matches[0])){
            		$this->keyword = $matches['postname'];
            	}
			}
		}

		/**
		* create_virtual
		* 
		* creates the virtual page / post based on the template used
		*
		* @access public 
		* @param object $posts - the wp posts
		* @return $posts
		*/
		public function create_virtual($posts)
		{
			global $wp,$wp_query;

            $this->options = get_option('vpt_options');

            $current_url = $_SERVER['REQUEST_URI'];
            if (!isset($this->options['use_custom_permalink_structure']))
            	$this->options['use_custom_permalink_structure'] = 0;
            $this->use_custom_permalink = (BOOL) $this->options['use_custom_permalink_structure'];

            if (!$this->use_custom_permalink)
				$virtualpageurl = $this->permalink_structure;
            else
            	$virtualpageurl = $this->options['virtualpageurl'];

            // trim slashes
            $virtualpageurl_trimmed = trim($virtualpageurl, '/');
            $current_url_trimmed = trim($current_url, '/');

            $this->init_keyword($current_url_trimmed, $virtualpageurl_trimmed);
            $virtual_url = str_replace('%postname%', $this->keyword, $virtualpageurl_trimmed);

            // get the template details
            $this->template_content = $this->get_template_content();
            $virtual_url = str_replace('%category%', $this->category_slug, $virtual_url);
            
            if ($virtual_url == $current_url_trimmed && (count($wp_query->posts) == 0 || (isset($wp_query->query['error']) && $wp_query->query['error'] == '404')) ) 
            {
            	if (!is_null($this->template))
            	{
            		$this->set_is_virtual_page( TRUE );

	            	$this->keyword = str_replace('-', ' ', $this->keyword);
	            	$post = $this->template;
	            	
	                $post->post_name = $wp->request;
	               	//$post->guid = get_home_url('/' . $this->keyword);
	             
	                $post->post_title = $this->keyword;
	                //put your custom content here
	                $post->post_content = $this->template_content;

	                //just needs to be a number - negatives are fine
	                $post->post_status = 'publish';
	                $post->comment_status = 'closed';
	                $post->ping_status = 'open';
	                $post->comment_count = 0;
	                //dates may need to be overwritten if you have a "recent posts" widget or similar - set to whatever you want
	                
	                $post->post_parent = 0;
	                $post->menu_order = 0;
	                $post->filter ='raw';

	                $posts = NULL;
	                $posts[] = $post;

	                $wp_query->is_singular = TRUE;
	                $wp_query->is_home = FALSE;
	                $wp_query->is_archive = FALSE;

	                if (!is_null($this->category_slug))
	                	$wp_query->is_category = FALSE;

	                unset($wp_query->query['error']);
	                $wp_query->query_vars['error']='';
	                $wp_query->is_404 = FALSE;
	                $wp_query->found_posts = TRUE;
	                $wp_query->is_attachment = FALSE;
	                $wp_query->query_vars['page'] = 0;
	                $wp_query->query_vars['attachment'] = NULL;
	                unset($wp_query->query['attachment']);
	                
	                if ($post->post_type == 'post')
	                {
	                	// add the uncateegorized class to the article
	            		add_filter('post_class',array($this, 'add_uncategorized_class'));
	                	$wp_query->query['name'] = $this->keyword;
	                	$wp_query->is_page = FALSE;
	                	$wp_query->is_single = TRUE;
	                }
	                else
	                {
	               		$wp_query->query['pagename'] = $this->keyword;
	               		$wp_query->query_vars['pagename'] = $this->keyword;
	               		$wp_query->is_page = TRUE;
	               		$wp_query->is_single = FALSE;
	                }
	                $wp_query->query['page'] = NULL;
            	}
            }

            return $posts;
            }
            
		/**
		* add_uncategorized_class
		* 
		* add the uncategorized class to the article the same as the normal posts does on uncategorized posts
		*
		* @access public 
		* @param array $classes
		* @return arrat $classes
		*/
		public function add_uncategorized_class($classes){
    		$classes[] = 'category-uncategorized';
    		return $classes;
		}

		/**
		* get_template_content
		* 
		* gets the template content and update the data e.g. converts %keyword% and spin texts
		*
		* @access public 
		* @param object $posts - the wp posts
		* @return $posts
		*/
		public function get_template_content()
		{
			global $wp,$wp_query;

			if (isset($this->options['page_template']))
			{
				$this->template = get_post($this->options['page_template']);      
			
				$this->template_content = str_replace('%vpt-keyword%', $this->keyword, $this->template->post_content);

				$categories = get_the_category($this->template->ID);
				$category = current($categories);

				if (!is_null($category) && is_object($category))
	            {
	            	$this->category_slug = $category->slug;
	            }

			}

			return $this->template_content;
		}

		/**
		 * Include required admin files.
		 *
		 * @access public
		 * @return void
		 */
		public function admin_includes()
		{
			// do admin includes here
			wp_enqueue_script('vpt-scripts',plugins_url( '/js/scripts.js' , __FILE__ ),array( 'jquery' ));
		}

		/**
		 * Include required frontend files.
		 *
		 * @access public
		 * @return void
		 */
		public function frontend_includes()
		{
			// do site includes here
			
		}

		public function display_notification()
		{	
			if ($this->notice_iserror) {
				echo '<div id="message" class="error">';
			}
			else {
				echo '<div id="message" class="updated fade">';
			}

			echo '<p><strong>' . $this->notice . '</strong></p></div>';
		}   

		/**
		* gets whether the current page is a virtual url or not
		* 
		*
		* @access public 
		* @return BOOL $is_virtual_page
		*/
		public function is_virtual_page()
		{
			return $this->is_virtual_page;
		}

		/**
		* sets whether the current page is a virtual url or not
		* 
		*
		* @access public 
		* @param BOOL $is_virtual_page
		* @return void
		*/
		public function set_is_virtual_page($is_virtual_page = FALSE)
		{
			$this->is_virtual_page = $is_virtual_page;
		}

	}	
}


new VirtualPagesTemplates;
