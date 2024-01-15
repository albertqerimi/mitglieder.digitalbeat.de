<?php
namespace FileBird\Classes;

defined( 'ABSPATH' ) || exit;

class Helpers {
	protected static $instance = null;

	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function foldersFromEnhanced( $parent = 0, $flat = false ) {
		global $wpdb;
		$folders = $wpdb->get_results( $wpdb->prepare( "SELECT t.term_id as id, t.name as title, tt.term_taxonomy_id FROM $wpdb->terms as t INNER JOIN $wpdb->term_taxonomy as tt ON (t.term_id = tt.term_id) WHERE tt.taxonomy = 'media_category' AND tt.parent = %d", $parent ) );
		foreach ( $folders as $k => $folder ) {
			$folders[ $k ]->parent = $parent;
		}
		if ( $flat ) {
			foreach ( $folders as $k => $folder ) {
				$children = self::foldersFromEnhanced( $folder->id, $flat );
				foreach ( $children as $k2 => $v2 ) {
					$folders[] = $v2;
				}
			}
		} else {
			foreach ( $folders as $k => $folder ) {
				$folders[ $k ]->children = self::foldersFromEnhanced( $folder->id, $flat );
			}
		}
		return $folders;
	}
	public static function foldersFromWpmlf( $parent = 0, $flat = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mgmlp_folders';
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) == $table_name ) {
			return array();
		}

		$folders = $wpdb->get_results( $wpdb->prepare( "select p.ID as id, p.post_title as title, mlf.folder_id as parent from $wpdb->posts as p LEFT JOIN {$wpdb->prefix}mgmlp_folders as mlf ON(p.ID = mlf.post_id) where p.post_type = 'mgmlp_media_folder' and mlf.folder_id = %d order by mlf.folder_id", $parent ) );

		if ( $flat ) {
			foreach ( $folders as $k => $folder ) {
				$children = self::foldersFromWpmlf( $folder->id, $flat );
				foreach ( $children as $k2 => $v2 ) {
					$folders[] = $v2;
				}
			}
		} else {
			foreach ( $folders as $k => $folder ) {
				$folders[ $k ]->children = self::foldersFromWpmlf( $folder->id, $flat );
			}
		}
		return $folders;
	}
	public static function foldersFromWpmf( $parent = 0, $flat = false ) {
		global $wpdb;
		$folders = $wpdb->get_results( $wpdb->prepare( "SELECT t.term_id as id, t.name as title, tt.term_taxonomy_id FROM $wpdb->terms as t INNER JOIN $wpdb->term_taxonomy as tt ON (t.term_id = tt.term_id) WHERE tt.taxonomy = 'wpmf-category' AND tt.parent = %d", $parent ) );
		foreach ( $folders as $k => $folder ) {
			$folders[ $k ]->parent = $parent;
		}
		if ( $flat ) {
			foreach ( $folders as $k => $folder ) {
				$children = self::foldersFromWpmf( $folder->id, $flat );
				foreach ( $children as $k2 => $v2 ) {
					$folders[] = $v2;
				}
			}
		} else {
			foreach ( $folders as $k => $folder ) {
				$folders[ $k ]->children = self::foldersFromWpmf( $folder->id, $flat );
			}
		}
		return $folders;
	}
	public static function foldersFromRealMedia( $parent = 0, $flat = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'realmedialibrary';
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) == $table_name ) {
			return array();
		}

		$folders = $wpdb->get_results( $wpdb->prepare( "select r.id as id, r.name as title, r.parent as parent from {$wpdb->prefix}realmedialibrary as r where r.parent = %d order by r.ord", $parent ) );

		if ( $flat ) {
			foreach ( $folders as $k => $folder ) {
				$children = self::foldersFromRealMedia( $folder->id, $flat );
				foreach ( $children as $k2 => $v2 ) {
					$folders[] = $v2;
				}
			}
		} else {
			foreach ( $folders as $k => $folder ) {
				$folders[ $k ]->children = self::foldersFromRealMedia( $folder->id, $flat );
			}
		}
		return $folders;
	}

	public static function foldersFromHappyFiles( $parent = 0, $flat = false ) {
		global $wpdb;
		$folders = $wpdb->get_results( $wpdb->prepare( "SELECT t.term_id as id, t.name as title, tt.term_taxonomy_id FROM $wpdb->terms as t INNER JOIN $wpdb->term_taxonomy as tt ON (t.term_id = tt.term_id) WHERE tt.taxonomy = 'happyfiles_category' AND tt.parent = %d", $parent ) );
		foreach ( $folders as $k => $folder ) {
			$folders[ $k ]->parent = $parent;
		}
		if ( $flat ) {
			foreach ( $folders as $k => $folder ) {
				$children = self::foldersFromHappyFiles( $folder->id, $flat );
				foreach ( $children as $k2 => $v2 ) {
					$folders[] = $v2;
				}
			}
		} else {
			foreach ( $folders as $k => $folder ) {
				$folders[ $k ]->children = self::foldersFromHappyFiles( $folder->id, $flat );
			}
		}
		return $folders;
	}
	public static function foldersFromPremio( $parent = 0, $flat = false ) {
		global $wpdb;
		$folders = $wpdb->get_results( $wpdb->prepare( "SELECT t.term_id as id, t.name as title, tt.term_taxonomy_id FROM $wpdb->terms as t  INNER JOIN  $wpdb->term_taxonomy as tt ON (t.term_id = tt.term_id) WHERE tt.taxonomy = 'media_folder' AND tt.parent = %d", $parent ) );
		foreach ( $folders as $k => $folder ) {
			$folders[ $k ]->parent = $parent;
		}
		if ( $flat ) {
			foreach ( $folders as $k => $folder ) {
				$children = self::foldersFromPremio( $folder->id, $flat );
				foreach ( $children as $k2 => $v2 ) {
					$folders[] = $v2;
				}
			}
		} else {
			foreach ( $folders as $k => $folder ) {
				$folders[ $k ]->children = self::foldersFromPremio( $folder->id, $flat );
			}
		}
		return $folders;
	}
	public static function sanitize_array( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'self::sanitize_array', $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}
	public static function getAttachmentIdsByFolderId( $folder_id ) {
		global $wpdb;
		return $wpdb->get_col( 'SELECT `attachment_id` FROM ' . $wpdb->prefix . 'fbv_attachment_folder WHERE `folder_id` = ' . (int) $folder_id );
	}

	public static function getAttachmentCountByFolderId( $folder_id ) {
		return Tree::getCount( $folder_id );
	}

	public static function view( $path, $data = array() ) {
		extract( $data );
		ob_start();
		include_once NJFB_PLUGIN_PATH . 'views/' . $path . '.php';
		return ob_get_clean();
	}

	public static function isActivated() {
		$code  = get_option( 'filebird_code', '' );
		$email = get_option( 'filebird_email', '' );
		return ( $code != '' && $email != '' );
	}
	public static function isListMode() {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			return ( isset( $screen->id ) && 'upload' == $screen->id );
		}
		return false;
	}

	public static function wp_kses_i18n( $string ) {
		return wp_kses(
            $string,
            array(
				'strong' => array(),
				'a'      => array(
					'target' => array(),
					'href'   => array(),
				),
			)
            );
	}
	public static function findFolder( $folder_id, $tree ) {
		$folder = null;
		foreach ( $tree as $k => $v ) {
			if ( $v['id'] == $folder_id ) {
				$folder = $v;
				break;
			} else {
				$folder = self::findFolder( $folder_id, $v['children'] );
				if ( ! is_null( $folder ) ) {
					break;
				} else {
					continue;
				}
			}
		}
		return $folder;
	}
}