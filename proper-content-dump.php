<?php
/*
Plugin Name: PROPER Content Dump
Version: 0.1
Plugin URI: http://theproperweb.com
Description: Displays all site content on one page for reading, editing, etc
Author: PROPER Development
Author URI: http://theproperweb.com
License: GPL v3

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function proper_barf_content () {
	if (
		current_user_can('edit_others_pages') &&
		!empty( $_GET['show-me'] ) &&
		$_GET['show-me'] == 'the-content'
	) {

		echo '
<!DOCTYPE HTML>
<html>
<head>
	<meta charset="'. get_bloginfo( 'charset' ) .'" >
	<title>LOOK AT ALL THE CONTENT</title>
	<style type="text/css">
		body {background: #f1f1f1}
		.all_content_wrap {width: 90%; min-width: 600px; max-width: 1000px; margin: 0 auto; padding: 20px; background-color: #fff;
		box-shadow: 0 2px 5px #bbb}
		.edit_link {display: block; padding: 10px 20px; background: #eee; font-weight: bold; font-size: 1.2em}
		img {max-width: 100%; height: auto !important;}
		hr {margin: 30px 0}
		h1 {background: black; color: white; margin: 20px 0; padding: 10px 20px}
		.post_content .alignleft {float: left; margin: 0 16px 10px}
		.post_content .alignright {float: right; margin: 0 0 10px 16px}
		.post_content .aligncenter {display: block; margin: 10px auto}
	</style>
</head>
<body>
	<div class="all_content_wrap">
		<h1>Table of Contents</h1>
		<ul>';

		// All post types
		$types = get_post_types ();
		unset ($types['revision'] );

		foreach ( $types as $type )
			echo '<li><a href="#'.$type.'_type">Post type: '. $type.'</a></li>';

		// All taxonomies
		$taxes = get_taxonomies ();
		foreach ( $taxes as $tax )
			echo '<li><a href="#' . $tax . '_tax">Taxonomy: ' . $tax . '</a></li>';

		// Links
		$args = array (
			'show_images' => 1,
			'show_description' => 1,
			'hide_invisible' => 0,
			'echo' => 0,
			'categorize' => 0,
			'title_li' => 0
		);
		$links = get_bookmarks ( $args );
		if (!empty( $links)) echo '<li><a href="#links">Links</a></li>';

		// Sidebars
		global $wp_registered_sidebars;
		foreach ( $wp_registered_sidebars as $sidebar )
			echo '<li><a href="#' . $sidebar['id'] . '_sidebar">Sidebar: ' . $sidebar['name'] . '</a></li>';

		echo '
		</ul>
		<hr>';

		/*
		 * First, start with the content, all types
		 */

		foreach ( $types as $type ) :

			echo '
			<a name="' . $type . '_type"></a>
			<h1>Post type: ' . $type . '</h1>
			<hr>';

			$args = array (
				'post_type' => $type,
				'posts_per_page' => -1,
			);

			foreach ( get_posts ( $args ) as $item ) :

				if ( $type == 'nev_menu_item' ) {

					echo '
					<a href="' . site_url () . '/wp-admin/nav-menus.php" class="edit_link" target="_blank">Edit</a>
					<h2>' . $item->post_title . '</h2>';

					if ( !empty( $item->post_excerpt))
						echo '<p><strong>Title attribute:</strong> ' . $item->post_excerpt . '</p>';

				} elseif ( $type == 'attachment' ) {

					echo '
					<a href="' . site_url () . '/wp-admin/post.php?post=' . $item->ID . '&action=edit"
					class="edit_link" target="_blank">Edit</a>
					<h2>' . $item->post_title . '</h2>';

					if (!empty( $item->post_excerpt ))
						echo '<p class="post_excerpt"><strong>Caption:</strong> ' . $item->post_excerpt  . '</p>';

					$img_alt = get_post_meta ( $item->ID, '_wp_attachment_image_alt', TRUE );
					 if ( !empty( $img_alt ) )
						echo '<p><strong>Image Alt:</strong> ' . $img_alt . '</p>';

					if (!empty( $item->post_content))
						echo '<div class="post_content"><strong>Description:</strong> ' . wpautop ( $item->post_content) .
					'</div>';

				} else {

					echo '
					<a href="' . site_url() . '/wp-admin/post.php?post=' . $item->ID . '&action=edit"
					class="edit_link" target="_blank">Edit</a>
					<h2>' . $item->post_title . '</h2>
					<div class="post_content">' . wpautop ( $item->post_content ) . '</div>';

					if (!empty( $item->post_excerpt))
						echo '<p><strong>Excerpt:</strong> ' . $item->post_excerpt .'</p>';

					$metas = get_post_custom ( $item->ID );

					foreach ( $metas as $key => $val ) :

						if (
							$key != '_wp_old_slug' &&
							$key != '_wp_attached_file' &&
							$key != '_wp_attachment_metadata' &&
							$key != '_wp_page_template' &&
							$key != '_edit_last' &&
							$key != '_edit_lock' &&
							$key != 'dsq_thread_id' &&
							$key != '_thumbnail_id'
						)
						{
							foreach ( $val as $thing ) :
								echo '<p><strong>' . $key . ':</strong> ' . $thing . '</p>';
							endforeach;

						}

					endforeach;

				}

				echo '<hr>';

			endforeach;

		endforeach;

		/*
		 * Now show all taxonomies
		 */

		foreach ( $taxes as $tax ) :

			echo '
			<a name="' . $tax . '_tax"></a>
			<h1>Taxonomy: ' . $tax . '</h1>
			<hr>';

			foreach ( get_terms ( $tax ) as $term ) :
				echo '
				<a href="' . site_url () . '/wp-admin/edit-tags.php?action=edit&taxonomy=' . $term->taxonomy .
				'&tag_ID=' . $term->term_id . '" class="edit_link" target="_blank">Edit</a>
				<h2>' . $term->name . '</h2>';

				if (!empty( $term->description))
					echo '<p><strong>Description:</strong> ' . $term->description . '</p>
				<hr>';
			endforeach;
		endforeach;

		/*
		 * Now show links, if any
		 */

		if (!empty( $links)) :

			echo '
			<a name="links"></a>
			<h1>Links</h1>
			<hr>';

			foreach ( $links as $link ) :
				echo '
				<a href="' . site_url () . '/wp-admin/link.php?action=edit&link_id=' . $link->link_id . '"
					class="edit_link" target="_blank">Edit</a>
				<h2>' . $link->link_name . '</h2>
				<p><strong>URL:</strong> <a href="' . $link->link_url . '" target="_blank">' . $link->link_url .
				'</a></p>';

				if ( !empty( $link->link_description ) )
					echo '<p><strong>Description:</strong> '. $link->link_description.'</p>';

				if ( !empty( $link->link_notes ) )
					echo '<p><strong>Notes:</strong> ' . $link->link_notes . '</p>';

				echo '<hr>';
			endforeach;

			echo '<hr>';

		endif;

		/*
		 * Finally, show the sidebars
		 */

		echo '<h1>Sidebars</h1>
		<hr>';
		foreach ( $wp_registered_sidebars as $sidebar ) :
			echo '
			<a name="' . $sidebar['id'].'_sidebar"></a>
			<h2>'. $sidebar['name'].'</h2>';
			dynamic_sidebar( $sidebar['id'] );
			echo '<hr>';
		endforeach;

		echo '
	</div>
</body>
</html>';

		die();
	}
}
add_action('wp_loaded', 'proper_barf_content');