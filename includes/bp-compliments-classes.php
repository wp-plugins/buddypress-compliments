<?php
/**
 * Class that interact with the custom db table.
 *
 * @since 0.0.1
 * @package BuddyPress_Compliments
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Compliments {
    /**
     * The compliments ID.
     */
    public $id = 0;

    /**
     * The user ID of receiver.
     */
    public $receiver_id;

    /**
     * The user ID of sender.
     */
    var $sender_id;

    /**
     * The compliment type term ID.
     */
    var $term_id;

    /**
     * The post ID.
     */
    var $post_id;

    /**
     * The compliment message.
     */
    var $message;


    /**
     * Constructor.
     *
     * @since 0.0.1
     * @package BuddyPress_Compliments
     *
     * @param int $receiver_id The user ID of the user you want to compliment.
     * @param int $sender_id The user ID initiating the compliment request.
     * @param int $term_id The term ID of the compliment type.
     * @param int $post_id Optional. The post ID. If the compliment is for a post.
     * @param string $message Optional. The compliment message.
     */
    public function __construct( $receiver_id = 0, $sender_id = 0, $term_id = 0, $post_id = 0, $message = '' ) {
        if ( ! empty( $receiver_id ) && ! empty( $sender_id ) ) {
            $this->receiver_id   = (int) $receiver_id;
            $this->sender_id = (int) $sender_id;
        }
        if ( ! empty( $term_id ) ) {
            $this->term_id   = (int) $term_id;
        }
        if ( ! empty( $post_id ) ) {
            $this->post_id   = (int) $post_id;
        }
        if ( ! empty( $message ) ) {
            $this->message   = $message;
        }
    }

    /**
     * Saves a compliment into the database.
     *
     * @since 0.0.1
     * @package BuddyPress_Compliments
     *
     * @global object $bp BuddyPress instance.
     * @global object $wpdb WordPress db object.
     * @return bool|mixed
     */
    public function save() {
        global $wpdb, $bp;
        $table_name = BP_COMPLIMENTS_TABLE;

        /**
         * Filters the value of compliment receiver ID.
         *
         * @since 0.0.1
         * @package BuddyPress_Compliments
         *
         * @param int $this->receiver_id Compliment receiver ID.
         * @param int $this->id Optional Compliment ID.
         */
        $this->receiver_id   = apply_filters( 'bp_compliments_receiver_id_before_save',   $this->receiver_id,   $this->id );
        /**
         * Filters the value of compliment sender ID.
         *
         * @since 0.0.1
         * @package BuddyPress_Compliments
         *
         * @param int $this->sender_id Compliment sender ID.
         * @param int $this->id Optional Compliment ID.
         */
        $this->sender_id = apply_filters( 'bp_compliments_sender_id_before_save', $this->sender_id, $this->id );
        /**
         * Filters the value of compliment term ID.
         *
         * @since 0.0.1
         * @package BuddyPress_Compliments
         *
         * @param int $this->term_id Compliment term ID.
         * @param int $this->id Optional Compliment ID.
         */
        $this->term_id = apply_filters( 'bp_compliments_term_id_before_save', $this->term_id, $this->id );
        /**
         * Filters the value of compliment post ID.
         *
         * @since 0.0.1
         * @package BuddyPress_Compliments
         *
         * @param int $this->post_id Compliment post ID.
         * @param int $this->id Optional Compliment ID.
         */
        $this->post_id = apply_filters( 'bp_compliments_post_id_before_save', $this->post_id, $this->id );
        /**
         * Filters the value of compliment message.
         *
         * @since 0.0.1
         * @package BuddyPress_Compliments
         *
         * @param string $this->message Compliment message.
         * @param int $this->id Optional Compliment ID.
         */
        $this->message = apply_filters( 'bp_compliments_message_before_save', $this->message, $this->id );

        /**
         * Functions hooked to this action will be processed before saving the complement data.
         *
         * @since 0.0.1
         * @package BuddyPress_Compliments
         *
         * @param object $this The compliment data object.
         */
        do_action_ref_array( 'bp_compliments_before_save', array( &$this ) );

        if (!$this->term_id OR !$this->receiver_id OR !$this->sender_id) {
            return false;
        }

        $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$table_name} ( receiver_id, sender_id, term_id, post_id, message, created_at ) VALUES ( %d, %d, %d, %d, %s, %s )", $this->receiver_id, $this->sender_id, $this->term_id, $this->post_id, $this->message, current_time( 'mysql' ) ) );

        $this->id = $wpdb->insert_id;

        /**
         * Functions hooked to this action will be processed after saving the complement data.
         *
         * @since 0.0.1
         * @package BuddyPress_Compliments
         *
         * @param object $this The compliment data object.
         */
        do_action_ref_array( 'bp_compliments_after_save', array( &$this ) );

        return $result;
    }

    /**
     * Deletes a compliment from the database.
     *
     * @since 0.0.1
     * @package BuddyPress_Compliments
     *
     * @global object $bp BuddyPress instance.
     * @global object $wpdb WordPress db object.
     * @param int $c_id The compliment ID.
     * @return mixed
     */
    public static function delete($c_id) {
        global $wpdb, $bp;
        $table_name = BP_COMPLIMENTS_TABLE;
        return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE id = %d", $c_id ) );
    }

    /**
     * Get the compliments for a given user.
     *
     * @since 0.0.1
     * @package BuddyPress_Compliments
     *
     * @global object $bp BuddyPress instance.
     * @global object $wpdb WordPress db object.
     * @param int $user_id The user ID.
     * @param int $offset Query results offset.
     * @param int $limit Query results limit.
     * @param bool|int $c_id The compliment ID.
     * @return mixed
     */
    public static function get_compliments( $user_id, $offset, $limit, $c_id = false ) {
        global $bp, $wpdb;
        $table_name = BP_COMPLIMENTS_TABLE;
        if ($c_id) {
            return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE receiver_id = %d AND id = %d ORDER BY created_at DESC LIMIT %d, %d", $user_id, $c_id, $offset, $limit ) );
        } else {
            return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE receiver_id = %d ORDER BY created_at DESC LIMIT %d, %d", $user_id, $offset, $limit ) );
        }
    }

    /**
     * Get the compliment received / sent count for a given user.
     *
     * @since 0.0.1
     * @package BuddyPress_Compliments
     *
     * @global object $bp BuddyPress instance.
     * @global object $wpdb WordPress db object.
     * @param int $user_id The user ID.
     * @return array The count array.
     */
    public static function get_counts( $user_id ) {
        global $bp, $wpdb;
        $table_name = BP_COMPLIMENTS_TABLE;
        $received = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name} WHERE receiver_id = %d", $user_id ) );
        $sent = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name} WHERE sender_id = %d", $user_id ) );

        return array( 'received' => $received, 'sent' => $sent );
    }


    /**
     * Deletes all compliments for a given user.
     *
     * @since 0.0.1
     * @package BuddyPress_Compliments
     *
     * @global object $bp BuddyPress instance.
     * @global object $wpdb WordPress db object.
     * @param int $user_id The user ID.
     */
    public static function delete_all_for_user( $user_id ) {
        global $bp, $wpdb;
        $table_name = BP_COMPLIMENTS_TABLE;
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE receiver_id = %d OR sender_id = %d", $user_id, $user_id ) );
    }
}