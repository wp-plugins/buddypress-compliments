<?php
/**
 * Functions related to notification component.
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Format the items of notifications tab.
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 *
 * @global object $bp BuddyPress instance.
 * @param string $action The action type.
 * @param int $item_id User ID.
 * @param int $secondary_item_id Secondary Item ID.
 * @param int $total_items Total items.
 * @param string $format Format.
 * @return bool|mixed|void
 */
function bp_compliments_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {
    global $bp;

    /**
     * Functions hooked to this action will be processed before formatting compliment notifications.
     *
     * @since 0.0.1
     * @package BuddyPress_Compliments
     *
     * @param string $action The action type.
     * @param int $item_id User ID.
     * @param int $secondary_item_id Secondary Item ID.
     * @param int $total_items Total items.
     * @param string $format Format.
     */
    do_action( 'bp_compliments_format_notifications', $action, $item_id, $secondary_item_id, $total_items, $format );

    switch ( $action ) {
        case 'new_compliment':
            $link = false;
            $text = false;

            if ( 1 == $total_items ) {
                $text = sprintf( __( '%s has sent you a compliment', BP_COMP_TEXTDOMAIN ), bp_core_get_user_displayname( $item_id ) );
                $link = bp_core_get_user_domain( $bp->loggedin_user->id ) .BP_COMPLIMENTS_SLUG. '/?bpc_read=true&bpc_sender_id='.$item_id;
            }
            break;

        default :
            /**
             * Filters the notification link.
             *
             * @since 0.0.1
             * @package BuddyPress_Compliments
             *
             * @param string $action The action type.
             * @param int $item_id User ID.
             * @param int $secondary_item_id Secondary Item ID.
             * @param int $total_items Total items.
             */
            $link = apply_filters( 'bp_compliments_extend_notification_link', false, $action, $item_id, $secondary_item_id, $total_items );
            /**
             * Filters the notification text.
             *
             * @since 0.0.1
             * @package BuddyPress_Compliments
             *
             * @param string $action The action type.
             * @param int $item_id User ID.
             * @param int $secondary_item_id Secondary Item ID.
             * @param int $total_items Total items.
             */
            $text = apply_filters( 'bp_compliments_extend_notification_text', false, $action, $item_id, $secondary_item_id, $total_items );
            break;
    }

    if ( ! $link || ! $text ) {
        return false;
    }

    if ( 'string' == $format ) {
        /**
         * Filters the notification link.
         *
         * @since 0.0.1
         * @package BuddyPress_Compliments
         *
         * @param int $total_items Total items.
         * @param string $link Notification URL.
         * @param string $text Notification Text.
         * @param int $item_id User ID.
         * @param int $secondary_item_id Secondary Item ID.
         */
        return apply_filters( 'bp_compliments_new_compliment_notification', '<a href="' . $link . '">' . $text . '</a>', $total_items, $link, $text, $item_id, $secondary_item_id );
    } else {
        return false;
    }
}

/**
 * Add a notification when a compliment get submitted.
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 *
 * @global object $bp BuddyPress instance.
 * @param BP_Compliments $compliment The compliment object.
 */
function bp_compliments_notifications_add_on_compliment( BP_Compliments $compliment ) {
    // Add a screen notification
    // BP 1.9+
    if ( bp_is_active( 'notifications' ) ) {
        bp_notifications_add_notification( array(
            'item_id'           => $compliment->sender_id,
            'user_id'           => $compliment->receiver_id,
            'component_name'    => buddypress()->compliments->id,
            'component_action'  => 'new_compliment'
        ) );

        // BP < 1.9 - add notifications the old way
    } elseif ( ! class_exists( 'BP_Core_Login_Widget' ) ) {
        global $bp;

        bp_core_add_notification(
            $compliment->sender_id,
            $compliment->receiver_id,
            $bp->compliments->id,
            'new_compliment'
        );
    }

    // Add an email notification
    bp_compliments_new_compliment_email_notification( array(
        'receiver_id'   => $compliment->receiver_id,
        'sender_id' => $compliment->sender_id
    ) );
}
add_action( 'bp_compliments_start_compliment', 'bp_compliments_notifications_add_on_compliment' );

/**
 * Send an email to the receiver when a compliment get posted.
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 *
 * @return bool
 */
function bp_compliments_new_compliment_email_notification() {
    $args = '';

    $defaults = array(
        'receiver_id'   => bp_displayed_user_id(),
        'sender_id' => bp_loggedin_user_id()
    );

    $r = wp_parse_args( $args, $defaults );

    if ( 'no' == bp_get_user_meta( (int) $r['receiver_id'], 'notification_on_compliments', true ) )
        return false;

    // Check to see if this receiver has already been notified of this sender before
    $has_notified = bp_get_user_meta( $r['sender_id'], 'bp_compliments_has_notified', true );

    // Already notified so don't send another email
    if ( in_array( $r['receiver_id'], (array) $has_notified ) )
        return false;

    // Not been notified before, update usermeta and continue to mail
    $has_notified[] = $r['receiver_id'];
    bp_update_user_meta( $r['sender_id'], 'bp_compliments_has_notified', $has_notified );

    $sender_name = bp_core_get_user_displayname( $r['sender_id'] );
    $compliment_link = bp_core_get_user_domain( $r['receiver_id'] ) .BP_COMPLIMENTS_SLUG. '/?bpc_read=true&bpc_sender_id='.$r['sender_id'];

    $receiver_ud = bp_core_get_core_userdata( $r['receiver_id'] );

    // Set up and send the message
    $to = $receiver_ud->user_email;

    $subject = '[' . wp_specialchars_decode( bp_get_option( 'blogname' ), ENT_QUOTES ) . '] ' . sprintf( __( '%s has sent you a compliment',  BP_COMP_TEXTDOMAIN ), $sender_name );

    $message = sprintf( __(
        '%s has sent you a compliment.

To view %s\'s compliment: %s', BP_COMP_TEXTDOMAIN ), $sender_name, $sender_name, $compliment_link );

    // Add notifications link if settings component is enabled
    if ( bp_is_active( 'settings' ) ) {
        $settings_link = bp_core_get_user_domain( $r['receiver_id'] ) . BP_SETTINGS_SLUG . '/notifications/';
        $message .= sprintf( __( '

---------------------
To disable these notifications please log in and go to:
%s', BP_COMP_TEXTDOMAIN ), $settings_link );
    }

    // Send the message

    /**
     * Filters the notification receiver email.
     *
     * @since 0.0.1
     * @package BuddyPress_Compliments
     *
     * @param string $to Notification receiver email.
     */
    $to      = apply_filters( 'bp_compliments_notification_to', $to );
    /**
     * Filters the notification subject.
     *
     * @since 0.0.1
     * @package BuddyPress_Compliments
     *
     * @param string $to Notification subject.
     * @param string $sender_name Sender Name.
     */
    $subject = apply_filters( 'bp_compliments_notification_subject', $subject, $sender_name );
    /**
     * Filters the notification message.
     *
     * @since 0.0.1
     * @package BuddyPress_Compliments
     *
     * @param string $message Notification message.
     * @param string $sender_name Compliment Sender Name.
     * @param string $compliment_link Compliment Link.
     */
    $message = apply_filters( 'bp_compliments_notification_message', $message, $sender_name, $compliment_link );
    wp_mail( $to, $subject, $message );
}

/**
 * Remove query arg from link when the current notification sub tab is "read".
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 *
 * @param string $retval Link.
 * @return string Modified link.
 */
function bp_compliments_notifications_remove_queryarg_from_userlink( $retval ) {
    if ( bp_is_current_action( 'read' ) ) {
        // if notifications loop has finished rendering, stop now!
        if ( did_action( 'bp_after_member_body' ) ) {
            return $retval;
        }

        // individual parameter
        $retval = preg_replace( '/\?bpc_read=true&bpc_sender_id=[0-9]+/s', '', $retval );
    }

    return $retval;
}
add_filter( 'bp_compliments_new_compliment_notification', 'bp_compliments_notifications_remove_queryarg_from_userlink' );

/**
 * Adds Notification settings to the form.
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 *
 */
function bp_compliments_screen_notification_settings() {
    if ( !$notify = bp_get_user_meta( bp_displayed_user_id(), 'notification_on_compliments', true ) )
        $notify = 'yes';
    ?>

    <table class="notification-settings" id="compliments-notification-settings">
        <thead>
        <tr>
            <th class="icon"></th>
            <th class="title"><?php _e( 'Compliments', BP_COMP_TEXTDOMAIN ) ?></th>
            <th class="yes"><?php _e( 'Yes', BP_COMP_TEXTDOMAIN ) ?></th>
            <th class="no"><?php _e( 'No', BP_COMP_TEXTDOMAIN )?></th>
        </tr>
        </thead>

        <tbody>
        <tr>
            <td></td>
            <td><?php _e( 'A member sends you a compliment', BP_COMP_TEXTDOMAIN ) ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_on_compliments]" value="yes" <?php checked( $notify, 'yes', true ) ?>/></td>
            <td class="no"><input type="radio" name="notifications[notification_on_compliments]" value="no" <?php checked( $notify, 'no', true ) ?>/></td>
        </tr>
        </tbody>

        <?php
        /**
         * Use this hook to register additional compliment settings fields.
         *
         * @since 0.0.2
         * @package BuddyPress_Compliments
         */
        do_action( 'bp_compliments_screen_notification_settings' );
        ?>
    </table>
<?php
}
add_action( 'bp_notification_settings', 'bp_compliments_screen_notification_settings' );

/**
 * Marks a compliment as read when 'bpc_read' is set.
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 *
 * @global object $bp BuddyPress instance.
 */
function bp_compliments_notifications_mark_compliments_as_read() {
    if (!bp_is_user() || !is_user_logged_in()){
        return;
    }

    if ( ! isset( $_GET['bpc_read'] ) || ! isset( $_GET['bpc_sender_id'] ) ) {
        return;
    }

    $sender_id = (int) strip_tags(esc_sql($_GET['bpc_sender_id']));

    if (!is_int($sender_id)) {
        return;
    }


    // mark notification as read
    if ( bp_is_active( 'notifications' ) ) {
        bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $sender_id, buddypress()->compliments->id, 'new_compliment' );

    // check if we're not on BP 1.9
    // if so, delete notification since marked functionality doesn't exist
    } elseif ( ! class_exists( 'BP_Core_Login_Widget' ) ) {
        global $bp;

        bp_core_delete_notifications_by_item_id( bp_loggedin_user_id(), $sender_id, buddypress()->compliments->id, 'new_compliment' );
    }
    // Redirect
    bp_core_redirect( bp_displayed_user_domain() . BP_COMPLIMENTS_SLUG . '/' );
}
add_action( 'bp_actions', 'bp_compliments_notifications_mark_compliments_as_read' );


/**
 * Removes compliment notifications for the given user id.
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 *
 * @global object $bp BuddyPress instance.
 * @param int $user_id
 */
function bp_compliments_remove_notifications_for_user( $user_id = 0 ) {
    // BP 1.9+
    if ( bp_is_active( 'notifications' ) ) {
        bp_notifications_delete_all_notifications_by_type( $user_id, buddypress()->compliments->id, 'new_compliment' );

        // BP < 1.9 - delete notifications the old way
    } elseif ( ! class_exists( 'BP_Core_Login_Widget' ) ) {
        global $bp;

        bp_core_delete_notifications_from_user( $user_id, $bp->compliments->id, 'new_compliment' );
    }
}
add_action( 'bp_compliments_after_remove_data', 'bp_compliments_remove_notifications_for_user' );