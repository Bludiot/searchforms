<?php
/**
 * Search Forms
 *
 * Plugin core class, do not namespace.
 *
 * Improved search form plugin for Bludit CMS.
 *
 * @package    Search Forms
 * @subpackage Core
 * @since      1.0.0
 */

// Stop if accessed directly.
if ( ! defined( 'BLUDIT' ) ) {
	die( 'You are not allowed direct access to this file.' );
}

// Access namespaced functions.
use function SearchForms\sidebar_search;

/**
 * Core plugin class
 *
 * Extends the Plugin class to inherit its methods.
 */
class Search_Forms extends Plugin {

	/**
	 * Pages found in search
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array An array of associative arrays.
	 */
	private $result_pages = [];

	/**
	 * Number of results
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    integer
	 */
	private $result_items = 0;

	/**
	 * Constructor method
	 *
	 * @since  1.0.0
	 * @access public
	 * @return self
	 */
	public function __construct() {

		// Run parent constructor.
		parent :: __construct();

		// Include functionality.
		if ( $this->installed() ) {
			$this->autoload();
			$this->get_files();
		}
	}

	/**
	 * Prepare plugin for installation
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function prepare() {
		$this->autoload();
		$this->get_files();
	}

	/**
	 * Include functionality
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function get_files() {

		// Plugin path.
		$path = PATH_PLUGINS . $this->directoryName() . DS;

		// Get plugin functions.
		foreach ( glob( $path . 'includes/*.php' ) as $filename ) {
			require_once $filename;
		}
	}

	/**
	 * Autoload classes
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function autoload() {

		// Path to class files.
		$path = PATH_PLUGINS . $this->directoryName() . DS . 'includes/classes' . DS;

		// Array of namespaced classes & filenames.
		$classes = [
			'SearchForms\Search_Results' => $path . 'class-search-results.php'
		];
		spl_autoload_register(
			function ( string $class ) use ( $classes ) {
				if ( isset( $classes[ $class ] ) ) {
					require $classes[ $class ];
				}
			}
		);
	}

	/**
	 * Initiate plugin
	 *
	 * @since  1.0.0
	 * @access public
	 * @global object $L Language class.
	 * @return void
	 */
	public function init() {

		// Access global variables.
		global $L;

		// Database fields array.
		$this->dbFields = [
			'min_chars'    => 3,
			'cache_words'  => 800,
			'in_sidebar'   => true,
			'wrap'         => true,
			'wrap_class'   => 'form-wrap search-form-wrap',
			'form_class'   => 'form search-form',
			'label'        => $L->get( 'Search' ),
			'label_wrap'   => 'h2',
			'placeholder'  => $L->get( "Enter at least 3 characters." ),
			'button'       => true,
			'button_text'  => $L->get( 'Submit' ),
			'button_class' => 'button btn btn-md search-submit-button'
		];

		// Array of custom hooks.
		$this->customHooks = [
			'search_form'
		];

		if ( ! $this->installed() ) {
			$Tmp = new dbJSON( $this->filenameDb );
			$this->db = $Tmp->db;
			$this->prepare();
		}
	}

	/**
	 * Admin head
	 *
	 * Used to insert a style block into the
	 * admin head section.
	 *
	 * @since  1.0.0
	 * @access public
	 * @global object $site Site class.
	 * @global object $site Url class.
	 * @return void
	 */
	public function adminHead() {

		// Access global variables.
		global $site, $url;

		if ( 'configureight' == $site->theme() ) {
			return null;
		}

		// Load only for this plugin's pages.
		if ( ! str_contains( $url->slug(), $this->className() ) ) {
			return null;
		}

		?>
		<style>
		.form-range-row {
			padding: 0 30px;
		}
		.form-range-controls {
			display: flex;
			align-items: center;
			flex-wrap: nowrap;
			gap: 1em;
			width: 100%;
			max-width: 640px;
			margin: 0;
			padding: 0;
		}
		.form-range-value {
			display: inline-block;
			min-width: 6ch;
			padding: 0.25em 0.5em;
			border: solid 1px #dee2e6;
			text-align: center;
		}
		</style>
		<?php
	}

	/**
	 * Admin settings form
	 *
	 * @since  1.0.0
	 * @access public
	 * @global object $L Language class.
	 * @global object $plugin Plugin class.
	 * @global object $site Site class.
	 * @return string Returns the markup of the form.
	 */
	public function form() {

		// Access global variables.
		global $L, $plugin, $site;

		$html  = '';
		ob_start();
		include( $this->phpPath() . '/views/form-page.php' );
		$html .= ob_get_clean();

		return $html;
	}

	/**
	 * Admin controller
	 *
	 * Change the text of the `<title>` tag.
	 *
	 * @since  1.0.0
	 * @access public
	 * @global object $L The Language class.
	 * @global array $layout
	 * @return string Returns the head content.
	 */
	public function adminController() {
		global $L, $layout, $site;
		$layout['title'] = $L->get( 'Search Forms Guide' ) . ' | ' . $site->title();
	}

	/**
	 * Admin user guide
	 *
	 * @since  1.0.0
	 * @access public
	 * @global object $L Language class.
	 * @global object $plugin Plugin class.
	 * @global object $site Site class.
	 * @return string Returns the markup of the page.
	 */
	public function adminView() {

		// Access global variables.
		global $L, $plugin, $site;

		$html  = '';
		ob_start();
		include( $this->phpPath() . '/views/guide-page.php' );
		$html .= ob_get_clean();

		return $html;
	}

	/**
	 * Sidebar search form
	 *
	 * @since  1.0.0
	 * @access public
	 * @return string Returns the form markup.
	 */
	public function siteSidebar() {
		if ( $this->in_sidebar() ) {
			return sidebar_search();
		}
	}

	/**
	 * Install the plugin
	 *
	 * Sets sidebar position and creates
	 * the cache file.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  integer $position
	 * @return void
	 */
	public function install( $position = 0 ) {
		parent :: install( $position );
		return $this->create_cache();
	}

	/**
	 * Form save
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function post() {
		parent :: post();
		return $this->create_cache();
	}

	/**
	 * After page create hook
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function afterPageCreate() {
		$this->create_cache();
	}

	/**
	 * After page modify hook
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function afterPageModify() {
		$this->create_cache();
	}

	/**
	 * After page delete hook
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function afterPageDelete() {
		$this->create_cache();
	}

	/**
	 * Before all hook
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function beforeAll() {

		// Access global variables.
		global $site, $url;

		// Check if the URL matches the webhook.
		$webhook = 'search';
		if ( $this->webhook( $webhook, false, false ) ) {

			// Change the whereAmI to avoid load pages in the rule 69.pages.
			// This is only for performance purposes.
			$url->setWhereAmI( 'search' );

			// Get the string to search from the URL
			$search_string = $this->webhook( $webhook, true, false );
			$search_string = trim( $search_string, '/' );

			// Search the string in the cache and get all pages with matches
			$list = $this->search_results( $search_string );
			$this->result_items = count( $list );

			// Split the content in pages
			// The first page number is 1, so the real is 0
			$num_page = $url->pageNumber() - 1;
			$per_page = $site->itemsPerPage();

			if ( $per_page <= 0 ) {
				if ( $num_page === 0 ) {
					$this->result_pages = $list;
				}
			} else {
				$chunks = array_chunk( $list, $per_page );
				if ( isset( $chunks[$num_page] ) ) {
					$this->result_pages = $chunks[$num_page];
				}
			}
		}
	}

	/**
	 * Paginator
	 *
	 * Paginates search results according to per-page setting.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function paginator() {

		// Access global variables.
		global $result_items;

		$webhook = 'search';
		if ( $this->webhook( $webhook, false, false ) ) {

			/**
			 * Get the pre-defined variable from
			 * bl-kernal/boot/rules/99.paginator.php`.
			 *
			 * Is necessary to change this variable to fit the
			 * paginator with the result from the search.
			 */
			$result_items = $this->result_items;
		}
	}

	/**
	 * Before site load
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function beforeSiteLoad() {

		// Access global variables.
		global $content, $url, $WHERE_AM_I;

		$webhook = 'search';
		if ( $this->webhook( $webhook, false, false ) ) {

			$WHERE_AM_I = 'search';
			$content    = [];

			foreach ( $this->result_pages as $key ) {
				try {
					$page = new Page( $key );
					array_push( $content, $page );
				} catch ( Exception $e ) {
					// Continue.
				}
			}
		}
	}

	/**
	 * Generate cache file
	 *
	 * Necessary to call it when you create, edit or remove content.
	 *
	 * @since  1.0.0
	 * @access private
	 * @global object $pages The Pages class.
	 * @return void
	 */
	private function create_cache() {

		// Access global variables.
		global $pages;

		// Get list of published pages.
		$list = $pages->getList(
			$page_number   = 1,
			$result_items  = -1,
			$published     = true,
			$static        = true,
			$sticky        = true,
			$draft         = false,
			$scheduled     = false
		);
		$cache = [];

		// Get page object for each published.
		foreach ( $list as $key ) {

			$page = buildPage( $key );

			/**
			 * Process content
			 *
			 * Assuming average characters per word is 5.
			 */
			$words   = $this->cache_words() * 5;
			$content = $page->content();
			$content = Text :: removeHTMLTags( $content );
			$content = Text :: truncate( $content, $words, '' );

			// Include the page to the cache.
			$cache[$key]['title']       = $page->title();
			$cache[$key]['description'] = $page->description();
			$cache[$key]['content']     = $content;
		}

		// Generate JSON file with the cache.
		$json = json_encode( $cache );

		return file_put_contents( $this->cache_file(), $json, LOCK_EX );
	}

	/**
	 * Cache file
	 *
	 * Returns the absolute path of the cache file.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function cache_file() {
		return $this->workspace() . 'cache.json';
	}

	/**
	 * Get search results
	 *
	 * Searches text inside the cache, sorted by score.
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  string $text
	 * @return array Returns an array with the pages keys
	 *               related to the text.
	 */
	private function search_results( $text ) {

		// Read the cache file.
		$json    = file_get_contents( $this->cache_file() );
		$cache   = json_decode( $json, true );
		$search  = new SearchForms\Search_Results( $cache, 10, 1, true );
		$results = $search->search( $text, $this->min_chars() );

		return array_keys( $results );
	}

	// @return integer
	public function min_chars() {
		return $this->getValue( 'min_chars' );
	}

	// @return integer
	public function cache_words() {
		return $this->getValue( 'cache_words' );
	}

	// @return boolean
	public function in_sidebar() {
		return $this->getValue( 'in_sidebar' );
	}

	// @return boolean
	public function wrap() {
		return $this->getValue( 'wrap' );
	}

	// @return string
	public function label() {
		return $this->getValue( 'label' );
	}

	// @return string
	public function label_wrap() {
		return strtolower( $this->getValue( 'label_wrap' ) );
	}

	// @return string
	public function placeholder() {
		return $this->getValue( 'placeholder' );
	}

	// @return boolean
	public function button() {
		return $this->getValue( 'button' );
	}

	// @return string
	public function button_text() {
		return $this->getValue( 'button_text' );
	}

	// @return string
	public function button_class() {
		return $this->getValue( 'button_class' );
	}

	/**
	 * Custom hook
	 *
	 * Prints the sidebar search form by
	 * calling the `search_form' hook.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return string Returns the form markup.
	 */
	public function search_form() {
		return sidebar_search();
	}
}
