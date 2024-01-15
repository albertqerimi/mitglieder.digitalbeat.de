<?php
namespace FileBird\Classes;

defined( 'ABSPATH' ) || exit;

use FileBird\Controller\UserSettings;
use FileBird\Model\Folder as FolderModel;

class Tree {
	public static function getCount( $folder_id, $lang = null ) {
		global $wpdb;

		$select = "SELECT COUNT(*) FROM {$wpdb->posts} as posts WHERE ";
		$where  = array( "post_type = 'attachment'" );

		// With $folder_id == -1. We get all
		$where[] = "(posts.post_status = 'inherit' OR posts.post_status = 'private')";

		// with specific folder
		if ( $folder_id > 0 && ! apply_filters( 'fbv_speedup_get_count_query', false ) ) {
			$post__in = $wpdb->get_col( "SELECT `attachment_id` FROM {$wpdb->prefix}fbv_attachment_folder WHERE `folder_id` = " . (int) $folder_id );
			if ( count( $post__in ) == 0 ) {
				$post__in = array( 0 );
			}
			$where[] = '(ID IN (' . implode( ', ', $post__in ) . '))';
		} elseif ( $folder_id == 0 ) {
			return 0;//return 0 if this is uncategorized folder
		}

		$where = apply_filters( 'fbv_get_count_where_query', $where );

		$query = apply_filters( 'fbv_get_count_query', $select . implode( ' AND ', $where ), $folder_id, $lang );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $query );
	}
	public static function getAllFoldersAndCount( $lang = null ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT fbva.folder_id, count(fbva.attachment_id) as count FROM {$wpdb->prefix}fbv_attachment_folder as fbva 
			INNER JOIN {$wpdb->prefix}fbv as fbv ON fbv.id = fbva.folder_id 
			INNER JOIN {$wpdb->posts} as posts ON fbva.attachment_id = posts.ID  
			WHERE (posts.post_status = 'inherit' OR posts.post_status = 'private') 
			AND (posts.post_type = 'attachment') 
			AND fbv.created_by = %d 
			GROUP BY fbva.folder_id",
			apply_filters( 'fbv_in_not_in_created_by', '0' )
		);
		$query = apply_filters( 'fbv_all_folders_and_count', $query, $lang );

		$results = $wpdb->get_results( $query );
		$return  = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $k => $v ) {
				$return[ $v->folder_id ] = $v->count;
			}
		}
		return $return;
	}
	public static function getFolders( $order_by = null, $flat = false, $level = 0, $show_level = false ) {
		$userSettings         = UserSettings::getInstance()->settings;
		$folders_from_db      = FolderModel::allFolders( '*', null, $order_by );
		$default_folders      = array();
		$folder_colors        = get_option( 'fbv_folder_colors', array() );
		$folder_default_color = $userSettings['theme']['themeColor'];
		$tree                 = self::getTree( $folders_from_db, 0, $default_folders, $flat, $level, $show_level, $folder_colors, $folder_default_color );
		return $tree;
	}
	public static function getFolder( $folder_id ) {
		$tree = self::getFolders();
		return Helpers::findFolder( $folder_id, $tree );
	}

	private static function getTree( $data = array(), $parent = 0, $default = null, $flat = false, $level = 0, $show_level = false, $folder_colors = array(), $folder_default_color = '#8f8f8f' ) {
		$tree = is_null( $default ) ? array() : $default;
		foreach ( $data as $k => $v ) {
			if ( $v->parent == $parent ) {
				$children = self::getTree( $data, $v->id, null, $flat, $level + 1, $show_level, $folder_colors );
				$f        = array(
					'id'      => (int) $v->id,
					'text'    => $show_level ? str_repeat( '-', $level ) . $v->name : $v->name,
					'li_attr' => array(
						'data-count'  => 0,
						'data-parent' => (int) $parent,
						'real-count'  => 0,
						'style'       => '--color: ' . ( isset( $folder_colors[ $v->id ] ) ? sanitize_hex_color( $folder_colors[ $v->id ] ) : $folder_default_color ),
					),
				);

				if ( $flat === true ) {
					$tree[] = $f;
					foreach ( $children as $k2 => $v2 ) {
						$tree[] = $v2;
					}
				} else {
					$f['children'] = $children;
					$tree[]        = $f;
				}
			}
		}
		return $tree;
	}
}