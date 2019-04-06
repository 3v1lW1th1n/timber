<?php

namespace Timber;

use Timber\Factory\PostFactory;
use Timber\Factory\TermFactory;
use Timber\URLHelper;
use Timber\Helper;

use Timber\Post;

/**
 * Class Twig
 */
class Twig {

	public static $dir_name;

	/**
	 * @codeCoverageIgnore
	 */
	public static function init() {
		new self();
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		add_action('timber/twig/filters', array($this, 'add_timber_filters'));
		add_action('timber/twig/functions', array($this, 'add_timber_functions'));
		add_action('timber/twig/escapers', array($this, 'add_timber_escapers'));
	}

	/**
	 * Add Timber-specific functions to Twig.
	 *
	 * @param \Twig_Environment $twig
	 *
	 * @return \Twig_Environment
	 */
	public function add_timber_functions( $twig ) {
		/* actions and filters */
		$twig->addFunction( new Twig_Function( 'action', function() {
			call_user_func_array( 'do_action', func_get_args() );
		} ) );

		$twig->addFunction(new Twig_Function('function', array(&$this, 'exec_function')));
		$twig->addFunction(new Twig_Function('fn', array(&$this, 'exec_function')));

		$twig->addFunction(new Twig_Function('shortcode', 'do_shortcode'));

		$twig->addFunction( new Twig_Function( 'PostQuery', function( $args ) {
			return new PostQuery( $args );
		} ) );

		$twig->addFunction(new Twig_Function('Post', function( $pid, $PostClass = '' ) {
			if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
				foreach ( $pid as &$p ) {
					$p = ( new PostFactory( $PostClass ) )->get_object( $p );
				}
				return $pid;
			}
			return ( new PostFactory( $PostClass ) )->get_object( $pid );
		} ));

		$twig->addFunction(new Twig_Function('Image', function( $pid, $ImageClass = 'Timber\Image' ) {
			if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
				foreach ( $pid as &$p ) {
					$p = new $ImageClass($p);
				}
				return $pid;
			}
			return new $ImageClass($pid);
		} ));

		$twig->addFunction(new \Twig_SimpleFunction('Term', function( $pid, $TermClass = '' ) {
			if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
				foreach ( $pid as &$p ) {
					$p = ( new TermFactory( $TermClass ) )->get_object( $p );
				}
				return $pid;
			}
			return ( new TermFactory( $TermClass ) )->get_object( $pid );
		} ));

		$twig->addFunction(new Twig_Function('User', function( $pid, $UserClass = 'Timber\User' ) {
			if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
				foreach ( $pid as &$p ) {
					$p = new $UserClass($p);
				}
				return $pid;
			}
			return new $UserClass($pid);
		} ));

		/**
		 * Deprecated Timber object functions.
		 */
		$twig->addFunction( new Twig_Function(
			'TimberPost',
			function( $pid, $PostClass = 'Timber\Post' ) {
				Helper::deprecated( '{{ TimberPost() }}', '{{ Post() }}', '2.0.0' );
			}
		) );
		$twig->addFunction( new Twig_Function(
			'TimberImage',
			function( $pid = false, $ImageClass = 'Timber\Image' ) {
				Helper::deprecated( '{{ TimberImage() }}', '{{ Image() }}', '2.0.0' );
			}
		) );
		$twig->addFunction( new Twig_Function(
			'TimberTerm',
			function( $tid, $taxonomy = '', $TermClass = 'Timber\Term' ) {
				Helper::deprecated( '{{ TimberTerm() }}', '{{ Term() }}', '2.0.0' );
			}
		) );
		$twig->addFunction( new Twig_Function(
			'TimberUser',
			function( $pid, $UserClass = 'Timber\User' ) {
				Helper::deprecated( '{{ TimberUser() }}', '{{ User() }}', '2.0.0' );
			}
		) );

		/* bloginfo and translate */
		$twig->addFunction(new Twig_Function('bloginfo', 'bloginfo'));
		$twig->addFunction(new Twig_Function('__', '__'));
		$twig->addFunction(new Twig_Function('translate', 'translate'));
		$twig->addFunction(new Twig_Function('_e', '_e'));
		$twig->addFunction(new Twig_Function('_n', '_n'));
		$twig->addFunction(new Twig_Function('_x', '_x'));
		$twig->addFunction(new Twig_Function('_ex', '_ex'));
		$twig->addFunction(new Twig_Function('_nx', '_nx'));
		$twig->addFunction(new Twig_Function('_n_noop', '_n_noop'));
		$twig->addFunction(new Twig_Function('_nx_noop', '_nx_noop'));
		$twig->addFunction(new Twig_Function('translate_nooped_plural', 'translate_nooped_plural'));

		return $twig;
	}

	/**
	 *
	 *
	 * @param \Twig_Environment $twig
	 * @return \Twig_Environment
	 */
	public function add_timber_filters( $twig ) {
		/* image filters */
		$twig->addFilter(new \Twig_SimpleFilter('resize', array('Timber\ImageHelper', 'resize')));
		$twig->addFilter(new \Twig_SimpleFilter('retina', array('Timber\ImageHelper', 'retina_resize')));
		$twig->addFilter(new \Twig_SimpleFilter('letterbox', array('Timber\ImageHelper', 'letterbox')));
		$twig->addFilter(new \Twig_SimpleFilter('tojpg', array('Timber\ImageHelper', 'img_to_jpg')));
		$twig->addFilter(new \Twig_SimpleFilter('towebp', array('Timber\ImageHelper', 'img_to_webp')));

		/* debugging filters */
		$twig->addFilter(new \Twig_SimpleFilter('get_class', function( $obj ) {
			Helper::deprecated( '{{ my_object | get_class }}', "{{ function('get_class', my_object) }}", '2.0.0' );
			return get_class( $obj );
		} ));
		$twig->addFilter(new \Twig_SimpleFilter('print_r', function( $arr ) {
			Helper::deprecated( '{{ my_object | print_r }}', '{{ dump(my_object) }}', '2.0.0' );
			return print_r($arr, true);
		} ));

		/* other filters */
		$twig->addFilter(new \Twig_SimpleFilter('stripshortcodes', 'strip_shortcodes'));
		$twig->addFilter(new \Twig_SimpleFilter('array', array($this, 'to_array')));
		$twig->addFilter(new \Twig_SimpleFilter('excerpt', 'wp_trim_words'));
		$twig->addFilter(new \Twig_SimpleFilter('excerpt_chars', array('Timber\TextHelper', 'trim_characters')));
		$twig->addFilter(new \Twig_SimpleFilter('function', array($this, 'exec_function')));
		$twig->addFilter(new \Twig_SimpleFilter('pretags', array($this, 'twig_pretags')));
		$twig->addFilter(new \Twig_SimpleFilter('sanitize', 'sanitize_title'));
		$twig->addFilter(new \Twig_SimpleFilter('shortcodes', 'do_shortcode'));
		$twig->addFilter(new \Twig_SimpleFilter('time_ago', array($this, 'time_ago')));
		$twig->addFilter(new \Twig_SimpleFilter('wpautop', 'wpautop'));
		$twig->addFilter(new \Twig_SimpleFilter('list', array($this, 'add_list_separators')));

		$twig->addFilter(new \Twig_SimpleFilter('pluck', array('Timber\Helper', 'pluck')));
		$twig->addFilter(new \Twig_SimpleFilter('filter', array('Timber\Helper', 'filter_array')));

		$twig->addFilter(new \Twig_SimpleFilter('relative', function( $link ) {
					return URLHelper::get_rel_url($link, true);
				} ));

		$twig->addFilter(new \Twig_SimpleFilter('date', array($this, 'intl_date')));

		$twig->addFilter(new \Twig_SimpleFilter('truncate', function( $text, $len ) {
					return TextHelper::trim_words($text, $len);
				} ));

		/* actions and filters */
		$twig->addFilter(new \Twig_SimpleFilter('apply_filters', function() {
					$args = func_get_args();
					$tag = current(array_splice($args, 1, 1));

					return apply_filters_ref_array($tag, $args);
				} ));

		/**
		 * Filters the Twig environment used in the global context.
		 *
		 * You can use this filter if you want to add additional functionality to Twig, like global variables, filters or functions.
		 *
		 * @example
		 * ```php
		 * /**
		 *  * @param \Twig_Environment $twig The Twig environment.
		 *  * @return $twig
		 *  *\/
		 * add_filter( 'timber/twig', function( $twig ) {
		 *     // Make get_theme_file_uri() usable as {{ theme_file() }} in Twig.
		 *     $twig->addFunction( new Timber_Twig_Function( 'theme_file', 'get_theme_file_uri' ) );
		 *
		 *     return $twig;
		 * } );
		 * ```
		 * ```twig
		 * <a class="navbar-brand" href="{{ site.url }}">
		 *     <img src="{{ theme_file( 'build/img/logo-example.svg' ) }}" alt="Logo {{ site.title }}">
		 * </a>
		 * ```
		 * @since 0.21.9
		 *
		 * @param \Twig_Environment $twig The Twig Environment to which you can add additional functionality.
		 */
		$twig = apply_filters('timber/twig', $twig);

		/**
		 * Filters the Twig environment used in the global context.
		 *
		 * @deprecated 2.0.0
		 */
		$twig = apply_filters_deprecated( 'get_twig', array( $twig ), '2.0.0', 'timber/twig' );
		return $twig;
	}

	/**
	 *
	 *
	 * @param Twig_Environment $twig
	 * @return Twig_Environment
	 */
	public function add_timber_escapers( $twig ) {

		$twig->getExtension('Twig_Extension_Core')->setEscaper('esc_url', function( \Twig_Environment $env, $string ) {
			return esc_url($string);
		});
		$twig->getExtension('Twig_Extension_Core')->setEscaper('wp_kses_post', function( \Twig_Environment $env, $string ) {
			return wp_kses_post($string);
		});

		$twig->getExtension('Twig_Extension_Core')->setEscaper('esc_html', function( \Twig_Environment $env, $string ) {
			return esc_html($string);
		});

		$twig->getExtension('Twig_Extension_Core')->setEscaper('esc_js', function( \Twig_Environment $env, $string ) {
			return esc_js($string);
		});

		return $twig;

	}

	/**
	 *
	 *
	 * @param mixed   $arr
	 * @return array
	 */
	public function to_array( $arr ) {
		if ( is_array($arr) ) {
			return $arr;
		}
		$arr = array($arr);
		return $arr;
	}

	/**
	 *
	 *
	 * @param string  $function_name
	 * @return mixed
	 */
	public function exec_function( $function_name ) {
		$args = func_get_args();
		array_shift($args);
		if ( is_string($function_name) ) {
			$function_name = trim($function_name);
		}
		return call_user_func_array($function_name, ($args));
	}

	/**
	 *
	 *
	 * @param string  $content
	 * @return string
	 */
	public function twig_pretags( $content ) {
		return preg_replace_callback('|<pre.*>(.*)</pre|isU', array(&$this, 'convert_pre_entities'), $content);
	}

	/**
	 *
	 *
	 * @param array   $matches
	 * @return string
	 */
	public function convert_pre_entities( $matches ) {
		return str_replace($matches[1], htmlentities($matches[1]), $matches[0]);
	}

	/**
	 *
	 *
	 * @param string|\DateTime  $date
	 * @param string            $format (optional)
	 * @return string
	 */
	public function intl_date( $date, $format = null ) {
		if ( $format === null ) {
			$format = get_option('date_format');
		}

		if ( $date instanceof \DateTime ) {
			$timestamp = $date->getTimestamp() + $date->getOffset();
		} else if ( is_numeric($date) && (strtotime($date) === false || strlen($date) !== 8) ) {
			$timestamp = intval($date);
		} else {
			$timestamp = strtotime($date);
		}

		return date_i18n($format, $timestamp);
	}

	/**
	 * @param int|string $from
	 * @param int|string $to
	 * @param string $format_past
	 * @param string $format_future
	 * @return string
	 */
	public static function time_ago( $from, $to = null, $format_past = '%s ago', $format_future = '%s from now' ) {
		$to = $to === null ? time() : $to;
		$to = is_int($to) ? $to : strtotime($to);
		$from = is_int($from) ? $from : strtotime($from);

		if ( $from < $to ) {
			return sprintf($format_past, human_time_diff($from, $to));
		} else {
			return sprintf($format_future, human_time_diff($to, $from));
		}
	}

	/**
	 * @param array $arr
	 * @param string $first_delimiter
	 * @param string $second_delimiter
	 * @return string
	 */
	public function add_list_separators( $arr, $first_delimiter = ',', $second_delimiter = ' and' ) {
		$length = count($arr);
		$list = '';
		foreach ( $arr as $index => $item ) {
			if ( $index < $length - 2 ) {
				$delimiter = $first_delimiter.' ';
			} elseif ( $index == $length - 2 ) {
				$delimiter = $second_delimiter.' ';
			} else {
				$delimiter = '';
			}
			$list = $list.$item.$delimiter;
		}
		return $list;
	}

}
