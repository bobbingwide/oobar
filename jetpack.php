<?php 
   
class Jetpack {
   
	/**
	 * Handles the page load events for the Jetpack admin page
	 */
	function admin_page_load() {
		$error = false;

		// Make sure we have the right body class to hook stylings for subpages off of.
		add_filter( 'admin_body_class', array( __CLASS__, 'add_jetpack_pagestyles' ) );

		if ( ! empty( $_GET['jetpack_restate'] ) ) {
			// Should only be used in intermediate redirects to preserve state across redirects
			Jetpack::restate();
		}

		if ( isset( $_GET['connect_url_redirect'] ) ) {
			// User clicked in the iframe to link their accounts
			if ( ! Jetpack::is_user_connected() ) {
				$connect_url = $this->build_connect_url( true );
				if ( isset( $_GET['notes_iframe'] ) )
					$connect_url .= '&notes_iframe';
				wp_redirect( $connect_url );
				exit;
			} else {
				Jetpack::state( 'message', 'already_authorized' );
				wp_safe_redirect( Jetpack::admin_url() );
				exit;
			}
		}

		if ( isset( $_GET['action'] ) ) {
			switch ( $_GET['action'] ) {
			case 'authorize' :
				if ( Jetpack::is_active() && Jetpack::is_user_connected() ) {
					Jetpack::state( 'message', 'already_authorized' );
					wp_safe_redirect( Jetpack::admin_url() );
					exit;
				}
				Jetpack::log( 'authorize' );
				$client_server = new Jetpack_Client_Server;
				$client_server->authorize();
				exit;
			case 'register' :
				check_admin_referer( 'jetpack-register' );
				Jetpack::log( 'register' );
				Jetpack::maybe_set_version_option();
				$registered = Jetpack::try_registration();
				if ( is_wp_error( $registered ) ) {
					$error = $registered->get_error_code();
					Jetpack::state( 'error_description', $registered->get_error_message() );
					break;
				}

				wp_redirect( $this->build_connect_url( true ) );
				exit;
			case 'activate' :
				if ( ! current_user_can( 'jetpack_activate_modules' ) ) {
					$error = 'cheatin';
					break;
				}

				$module = stripslashes( $_GET['module'] );
				check_admin_referer( "jetpack_activate-$module" );
				Jetpack::log( 'activate', $module );
				Jetpack::activate_module( $module );
				// The following two lines will rarely happen, as Jetpack::activate_module normally exits at the end.
				wp_safe_redirect( Jetpack::admin_url( 'page=jetpack' ) );
				exit;
			case 'activate_default_modules' :
				check_admin_referer( 'activate_default_modules' );
				Jetpack::log( 'activate_default_modules' );
				Jetpack::restate();
				$min_version   = isset( $_GET['min_version'] ) ? $_GET['min_version'] : false;
				$max_version   = isset( $_GET['max_version'] ) ? $_GET['max_version'] : false;
				$other_modules = isset( $_GET['other_modules'] ) && is_array( $_GET['other_modules'] ) ? $_GET['other_modules'] : array();
				Jetpack::activate_default_modules( $min_version, $max_version, $other_modules );
				wp_safe_redirect( Jetpack::admin_url( 'page=jetpack' ) );
				exit;
			case 'disconnect' :
				if ( ! current_user_can( 'jetpack_disconnect' ) ) {
					$error = 'cheatin';
					break;
				}

				check_admin_referer( 'jetpack-disconnect' );
				Jetpack::log( 'disconnect' );
				Jetpack::disconnect();
				wp_safe_redirect( Jetpack::admin_url() );
				exit;
			case 'reconnect' :
				if ( ! current_user_can( 'jetpack_reconnect' ) ) {
					$error = 'cheatin';
					break;
				}

				check_admin_referer( 'jetpack-reconnect' );
				Jetpack::log( 'reconnect' );
				$this->disconnect();
				wp_redirect( $this->build_connect_url( true ) );
				exit;
			case 'deactivate' :
				if ( ! current_user_can( 'jetpack_deactivate_modules' ) ) {
					$error = 'cheatin';
					break;
				}

				$modules = stripslashes( $_GET['module'] );
				check_admin_referer( "jetpack_deactivate-$modules" );
				foreach ( explode( ',', $modules ) as $module ) {
					Jetpack::log( 'deactivate', $module );
					Jetpack::deactivate_module( $module );
					Jetpack::state( 'message', 'module_deactivated' );
				}
				Jetpack::state( 'module', $modules );
				wp_safe_redirect( Jetpack::admin_url( 'page=jetpack' ) );
				exit;
			case 'unlink' :
				check_admin_referer( 'jetpack-unlink' );
				Jetpack::log( 'unlink' );
				$this->unlink_user();
				Jetpack::state( 'message', 'unlinked' );
				wp_safe_redirect( Jetpack::admin_url() );
				exit;
			default:
				do_action( 'jetpack_unrecognized_action', sanitize_key( $_GET['action'] ) );
			}
		}

		if ( ! $error = $error ? $error : Jetpack::state( 'error' ) ) {
			$this->activate_new_modules();
		}

		switch ( $error ) {
		case 'cheatin' :
			$this->error = __( 'Cheatin&#8217; uh?', 'jetpack' );
			break;
		case 'access_denied' :
			$this->error = __( 'You need to authorize the Jetpack connection between your site and WordPress.com to enable the awesome features.', 'jetpack' );
			break;
		case 'wrong_state' :
			$this->error = __( 'Don&#8217;t cross the streams!  You need to stay logged in to your WordPress blog while you authorize Jetpack.', 'jetpack' );
			break;
		case 'invalid_client' :
			// @todo re-register instead of deactivate/reactivate
			$this->error = __( 'Return to sender.  Whoops! It looks like you got the wrong Jetpack in the mail; deactivate then reactivate the Jetpack plugin to get a new one.', 'jetpack' );
			break;
		case 'invalid_grant' :
			$this->error = __( 'Wrong size.  Hm&#8230; it seems your Jetpack doesn&#8217;t quite fit.  Have you lost weight? Click &#8220;Connect to WordPress.com&#8221; again to get your Jetpack adjusted.', 'jetpack' );
			break;
		case 'site_inaccessible' :
		case 'site_requires_authorization' :
			$this->error = sprintf( __( 'Your website needs to be publicly accessible to use Jetpack: %s', 'jetpack' ), "<code>$error</code>" );
			break;
		case 'module_activation_failed' :
			$module = Jetpack::state( 'module' );
			if ( ! empty( $module ) && $mod = Jetpack::get_module( $module ) ) {
				$this->error = sprintf( __( '%s could not be activated because it triggered a <strong>fatal error</strong>. Perhaps there is a conflict with another plugin you have installed?', 'jetpack' ), $mod['name'] );
				if ( isset( $this->plugins_to_deactivate[$module] ) ) {
					$this->error .= ' ' . sprintf( __( 'Do you still have the %s plugin installed?', 'jetpack' ), $this->plugins_to_deactivate[$module][1] );
				}
			} else {
				$this->error = __( 'Module could not be activated because it triggered a <strong>fatal error</strong>. Perhaps there is a conflict with another plugin you have installed?', 'jetpack' );
			}
			if ( $php_errors = Jetpack::state( 'php_errors' ) ) {
				$this->error .= "<br />\n";
				$this->error .= $php_errors;
			}
			break;
		case 'master_user_required' :
			$module = Jetpack::state( 'module' );
			$module_name = '';
			if ( ! empty( $module ) && $mod = Jetpack::get_module( $module ) ) {
				$module_name = $mod['name'];
			}

			$master_user = Jetpack_Options::get_option( 'master_user' );
			$master_userdata = get_userdata( $master_user ) ;
			if ( $master_userdata ) {
				if ( ! in_array( $module, Jetpack::get_active_modules() ) ) {
					$this->error = sprintf( __( '%s was not activated.' , 'jetpack' ), $module_name );
				} else {
					$this->error = sprintf( __( '%s was not deactivated.' , 'jetpack' ), $module_name );
				}
				$this->error .= '  ' . sprintf( __( 'This module can only be altered by %s, the user who initiated the Jetpack connection on this site.' , 'jetpack' ), esc_html( $master_userdata->display_name ) );

			} else {
				$this->error = sprintf( __( 'Only the user who initiated the Jetpack connection on this site can toggle %s, but that user no longer exists. This should not happen.', 'jetpack' ), $module_name );
			}
			break;
		case 'not_public' :
			$this->error = __( '<strong>Your Jetpack has a glitch.</strong> Connecting this site with WordPress.com is not possible. This usually means your site is not publicly accessible (localhost).', 'jetpack' );
			break;
		case 'wpcom_408' :
		case 'wpcom_5??' :
		case 'wpcom_bad_response' :
		case 'wpcom_outage' :
			$this->error = __( 'WordPress.com is currently having problems and is unable to fuel up your Jetpack.  Please try again later.', 'jetpack' );
			break;
		case 'register_http_request_failed' :
		case 'token_http_request_failed' :
			$this->error = sprintf( __( 'Jetpack could not contact WordPress.com: %s.  This usually means something is incorrectly configured on your web host.', 'jetpack' ), "<code>$error</code>" );
			break;
		default :
			if ( empty( $error ) ) {
				break;
			}
			$error = trim( substr( strip_tags( $error ), 0, 20 ) );
			// no break: fall through
		case 'no_role' :
		case 'no_cap' :
		case 'no_code' :
		case 'no_state' :
		case 'invalid_state' :
		case 'invalid_request' :
		case 'invalid_scope' :
		case 'unsupported_response_type' :
		case 'invalid_token' :
		case 'no_token' :
		case 'missing_secrets' :
		case 'home_missing' :
		case 'siteurl_missing' :
		case 'gmt_offset_missing' :
		case 'site_name_missing' :
		case 'secret_1_missing' :
		case 'secret_2_missing' :
		case 'site_lang_missing' :
		case 'home_malformed' :
		case 'siteurl_malformed' :
		case 'gmt_offset_malformed' :
		case 'timezone_string_malformed' :
		case 'site_name_malformed' :
		case 'secret_1_malformed' :
		case 'secret_2_malformed' :
		case 'site_lang_malformed' :
		case 'secrets_mismatch' :
		case 'verify_secret_1_missing' :
		case 'verify_secret_1_malformed' :
		case 'verify_secrets_missing' :
		case 'verify_secrets_mismatch' :
			$error = esc_html( $error );
			$this->error = sprintf( __( '<strong>Your Jetpack has a glitch.</strong>  Something went wrong that&#8217;s never supposed to happen.  Guess you&#8217;re just lucky: %s', 'jetpack' ), "<code>$error</code>" );
			if ( ! Jetpack::is_active() ) {
				$this->error .= '<br />';
				$this->error .= sprintf( __( 'Try connecting again.', 'jetpack' ) );
			}
			break;
		}

		$message_code = Jetpack::state( 'message' );

		$active_state = Jetpack::state( 'activated_modules' );
		if ( ! empty( $active_state ) ) {
			$available    = Jetpack::get_available_modules();
			$active_state = explode( ',', $active_state );
			$active_state = array_intersect( $active_state, $available );
			if ( count( $active_state ) ) {
				foreach ( $active_state as $mod ) {
					$this->stat( 'module-activated', $mod );
				}
			} else {
				$active_state = false;
			}
		}

		switch ( $message_code ) {
		case 'modules_activated' :
			$this->message = sprintf(
				__( 'Welcome to <strong>Jetpack %s</strong>!', 'jetpack' ),
				JETPACK__VERSION
			);

			if ( $active_state ) {
				$titles = array();
				foreach ( $active_state as $mod ) {
					if ( $mod_headers = Jetpack::get_module( $mod ) ) {
						$titles[] = '<strong>' . preg_replace( '/\s+(?![^<>]++>)/', '&nbsp;', $mod_headers['name'] ) . '</strong>';
					}
				}
				if ( $titles ) {
					$this->message .= '<br /><br />' . wp_sprintf( __( 'The following new modules have been activated: %l.', 'jetpack' ), $titles );
				}
			}

			if ( $reactive_state = Jetpack::state( 'reactivated_modules' ) ) {
				$titles = array();
				foreach ( explode( ',',  $reactive_state ) as $mod ) {
					if ( $mod_headers = Jetpack::get_module( $mod ) ) {
						$titles[] = '<strong>' . preg_replace( '/\s+(?![^<>]++>)/', '&nbsp;', $mod_headers['name'] ) . '</strong>';
					}
				}
				if ( $titles ) {
					$this->message .= '<br /><br />' . wp_sprintf( __( 'The following modules have been updated: %l.', 'jetpack' ), $titles );
				}
			}

			$this->message .= Jetpack::jetpack_comment_notice();
			break;

		case 'module_activated' :
			if ( $module = Jetpack::get_module( Jetpack::state( 'module' ) ) ) {
				$this->message = sprintf( __( '<strong>%s Activated!</strong> You can deactivate at any time by clicking the Deactivate link next to each module.', 'jetpack' ), $module['name'] );
				$this->stat( 'module-activated', Jetpack::state( 'module' ) );
			}
			break;

		case 'module_deactivated' :
			$modules = Jetpack::state( 'module' );
			if ( ! $modules ) {
				break;
			}

			$module_names = array();
			foreach ( explode( ',', $modules ) as $module_slug ) {
				$module = Jetpack::get_module( $module_slug );
				if ( $module ) {
					$module_names[] = $module['name'];
				}

				$this->stat( 'module-deactivated', $module_slug );
			}

			if ( ! $module_names ) {
				break;
			}

			$this->message = wp_sprintf(
				_nx(
					'<strong>%l Deactivated!</strong> You can activate it again at any time using the activate link next to each module.',
					'<strong>%l Deactivated!</strong> You can activate them again at any time using the activate links next to each module.',
					count( $module_names ),
					'%l = list of Jetpack module/feature names',
					'jetpack'
				),
				$module_names
			);
			break;

		case 'module_configured' :
			$this->message = __( '<strong>Module settings were saved.</strong> ', 'jetpack' );
			break;

		case 'already_authorized' :
			$this->message = __( '<strong>Your Jetpack is already connected.</strong> ', 'jetpack' );
			break;

		case 'authorized' :
			$this->message  = __( '<strong>You&#8217;re fueled up and ready to go.</strong> ', 'jetpack' );
			$this->message .= "<br />\n";
			$this->message .= sprintf( __( 'Jetpack is now active. Browse through each Jetpack feature below. Visit the <a href="%s">settings page</a> to activate/deactivate features.', 'jetpack' ), admin_url( 'admin.php?page=jetpack_modules' ) );
			$this->message .= Jetpack::jetpack_comment_notice();
			break;

		case 'linked' :
			$this->message  = __( '<strong>You&#8217;re fueled up and ready to go.</strong> ', 'jetpack' );
			$this->message .= Jetpack::jetpack_comment_notice();
			break;

		case 'unlinked' :
			$user = wp_get_current_user();
			$this->message = sprintf( __( '<strong>You have unlinked your account (%s) from WordPress.com.</strong>', 'jetpack' ), $user->user_login );
			break;
		}

		$deactivated_plugins = Jetpack::state( 'deactivated_plugins' );

		if ( ! empty( $deactivated_plugins ) ) {
			$deactivated_plugins = explode( ',', $deactivated_plugins );
			$deactivated_titles  = array();
			foreach ( $deactivated_plugins as $deactivated_plugin ) {
				if ( ! isset( $this->plugins_to_deactivate[$deactivated_plugin] ) ) {
					continue;
				}

				$deactivated_titles[] = '<strong>' . str_replace( ' ', '&nbsp;', $this->plugins_to_deactivate[$deactivated_plugin][1] ) . '</strong>';
			}

			if ( $deactivated_titles ) {
				if ( $this->message ) {
					$this->message .= "<br /><br />\n";
				}

				$this->message .= wp_sprintf(
					_n(
						'Jetpack contains the most recent version of the old %l plugin.',
						'Jetpack contains the most recent versions of the old %l plugins.',
						count( $deactivated_titles ),
						'jetpack'
					),
					$deactivated_titles
				);

				$this->message .= "<br />\n";

				$this->message .= _n(
					'The old version has been deactivated and can be removed from your site.',
					'The old versions have been deactivated and can be removed from your site.',
					count( $deactivated_titles ),
					'jetpack'
				);
			}
		}

		$this->privacy_checks = Jetpack::state( 'privacy_checks' );

		if ( $this->message || $this->error || $this->privacy_checks ) {
			add_action( 'jetpack_notices', array( $this, 'admin_notices' ) );
		}

		if ( isset( $_GET['configure'] ) && Jetpack::is_module( $_GET['configure'] ) && current_user_can( 'manage_options' ) ) {
			do_action( 'jetpack_module_configuration_load_' . $_GET['configure'] );
		}

		add_filter( 'jetpack_short_module_description', 'wptexturize' );
	}
  
}  
