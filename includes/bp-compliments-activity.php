<?php
/**
 * Functions related to activity component.
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * 
 * @since 0.0.2
 * @package BuddyPress_Compliments
 * 
 * @param string $args
 * @return bool|int
 */
function compliments_record_activity( $args = '' ) {

    if ( ! bp_is_active( 'activity' ) ) {
        return false;
    }

    $r = wp_parse_args( $args, array(
        'user_id'           => bp_loggedin_user_id(),
        'action'            => '',
        'content'           => '',
        'primary_link'      => '',
        'component'         => buddypress()->compliments->id,
        'type'              => false,
        'item_id'           => false,
        'secondary_item_id' => false,
        'recorded_time'     => bp_core_current_time(),
        'hide_sitewide'     => false
    ) );

    return bp_activity_add( $r );
}

/**
 * 
 * @since 0.0.2
 * @package BuddyPress_Compliments
 * 
 * @param BP_Compliments $compliment
 */
function compliments_record_sent_activity( BP_Compliments $compliment ) {
    if ( ! bp_is_active( 'activity' ) ) {
        return;
    }

    // Record in activity streams for the sender
    compliments_record_activity( array(
        'user_id'           => $compliment->sender_id,
        'type'              => 'compliment_sent',
        'item_id'           => $compliment->id,
        'secondary_item_id' => $compliment->receiver_id
    ) );

    // Record in activity streams for the receiver
    compliments_record_activity( array(
        'user_id'           => $compliment->receiver_id,
        'type'              => 'compliment_received',
        'item_id'           => $compliment->id,
        'secondary_item_id' => $compliment->sender_id
    ) );
}
add_action( 'bp_compliments_start_compliment', 'compliments_record_sent_activity' );


/**
 * Register the activity actions.
 * 
 * @since 0.0.2
 * @package BuddyPress_Compliments
 */
function compliments_register_activity_actions() {

    if ( !bp_is_active( 'activity' ) ) {
        return false;
    }

    $bp = buddypress();

    bp_activity_set_action(
        $bp->compliments->id,
        'compliment_received',
        __( 'Compliment Received', BP_COMP_TEXTDOMAIN ),
        'compliments_format_activity_action_compliment_received',
        __( 'Compliments', BP_COMP_TEXTDOMAIN ),
        array( 'activity' )
    );

    bp_activity_set_action(
        $bp->compliments->id,
        'compliment_sent',
        __( 'Compliment Sent', BP_COMP_TEXTDOMAIN ),
        'compliments_format_activity_action_compliment_sent',
        __( 'Compliments', BP_COMP_TEXTDOMAIN ),
        array( 'activity' )
    );

    do_action( 'compliments_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'compliments_register_activity_actions' );

/**
 * Format 'compliment_received' activity actions.
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 *
 * @global object $bp BuddyPress instance.
 * @param object $activity Activity data.
 * @return string $action Formatted activity action.
 */
function compliments_format_activity_action_compliment_received( $action, $activity ) {
    global $bp;
    $receiver_link = bp_core_get_userlink( $activity->user_id );
    $sender_link    = bp_core_get_userlink( $activity->secondary_item_id );
    $receiver_url    = bp_core_get_userlink( $activity->user_id, false, true );
    $compliment_url = $receiver_url . $bp->compliments->id . '/?c_id='.$activity->item_id;
    $compliment_link = '<a href="'.$compliment_url.'">'.__("compliment").'</a>';

    $action = sprintf( __( '%1$s has received a %2$s from %3$s', BP_COMP_TEXTDOMAIN ), $receiver_link, $compliment_link, $sender_link );


    /**
     * Filters the 'compliment_received' activity action format.
     *
     * @since 0.0.2
     *
     * @param string $action String text for the 'compliment_received' action.
     * @param object $activity Activity data.
     */
    return apply_filters( 'compliments_format_activity_action_compliment_received', $action, $activity );
}

/**
 * Format 'compliment_sent' activity actions.
 *
 * @since 0.0.2
 * @package BuddyPress_Compliments
 *
 * @global object $bp BuddyPress instance.
 * @param string $action Static activity action.
 * @param object $activity Activity data.
 * @return string $action Formatted activity action.
 */
function compliments_format_activity_action_compliment_sent( $action, $activity ) {
    global $bp;
    $sender_link = bp_core_get_userlink( $activity->user_id );
    $receiver_link    = bp_core_get_userlink( $activity->secondary_item_id );
    $receiver_url    = bp_core_get_userlink( $activity->secondary_item_id, false, true );
    $compliment_url = $receiver_url . $bp->compliments->id . '/?c_id='.$activity->item_id;
    $compliment_link = '<a href="'.$compliment_url.'">'.__("compliment").'</a>';

    $action = sprintf( __( '%1$s has sent a %2$s to %3$s', BP_COMP_TEXTDOMAIN ), $sender_link, $compliment_link, $receiver_link );

    /**
     * Filters the 'compliment_sent' activity action format.
     *
     * @since 0.0.2
     *
     * @param string $action String text for the 'compliment_sent' action.
     * @param object $activity Activity data.
     */
    return apply_filters( 'compliments_format_activity_action_compliment_sent', $action, $activity );
}


/**
 * 
 * @since 0.0.2
 * @package BuddyPress_Compliments
 * 
 * @param $c_id
 */
function compliments_delete_activity( $c_id ) {
    if ( ! bp_is_active( 'activity' ) ) {
        return;
    }

    bp_activity_delete( array(
        'component' => buddypress()->compliments->id,
        'item_id'   => $c_id
    ) );
}
add_action('bp_compliments_after_remove_compliment', 'compliments_delete_activity');

/**
 * 
 * @since 0.0.2
 * @package BuddyPress_Compliments
 * 
 * @param $user_id
 */
function compliments_delete_activity_for_user( $user_id ) {
    if ( ! bp_is_active( 'activity' ) ) {
        return;
    }

    bp_activity_delete( array(
        'component' => buddypress()->compliments->id,
        'user_id'   => $user_id
    ) );

    bp_activity_delete( array(
        'component' => buddypress()->compliments->id,
        'secondary_item_id'   => $user_id
    ) );
}
add_action('bp_compliments_after_remove_data', 'compliments_delete_activity_for_user');