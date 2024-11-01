<?php
global $wpdb;
$uav_user_tbl = $wpdb->users;
$user_ids     = UavSH()->get_inactive_users( "SELECT * FROM $uav_user_tbl WHERE user_activation_key != ''", true, OBJECT );

if ( ! empty( $user_ids ) ) { ?>

    <h2><?php _e( "List of all Inactive Users", 'uav' ); ?> </h2>

    <?php do_action( 'uav_before_user_list' ); ?>

    <span class="uav-msg"></span>
	<?php

	$uavTool = new Codemanas\UserActivationValidate\UavTable();

	// Display table anyway
	$uavTool->prepare_items();
	$uavTool->display();

	?>

    <style type="text/css">
        th#ID, th#status, th#activation_link_sent {
            width: 65px;
        }

        .row-actions.force-show {
            position: static;
        }

        .row-actions.force-show img {
            position: absolute;
        }

        span.uav-sgreen {
            color: #0b4e0b;
            margin: 0 0 0 5px;
        }

        .uav-help {
            position: relative;
        }

        .uav-help:hover > .uav-help-cnt {
            display: block;
        }

        .uav-help-cnt {
            width: max-content;
            max-width: 180px;
            position: absolute;
            background: #000;
            padding: 8px;
            color: #fff;
            font-size: 10px;
            top: -70px;
            left: 20px;
            border-radius: 11px 4px 4px 0;
            display: none;
        }

        .uav-help-cnt--position-left {
            right: 20px;
            left: auto;
            border-radius: 4px 11px 0 4px;
        }

        .uav-help-cnt--size-large {
            max-width: 220px;
        }

        .uav-help:after {
            content: '';
            display: inline-block;
            width: 18px;
            height: 18px;
            background-image: url( '<?php echo CODEMANAS_UAV_DIR_URL ;?>assets/help-icon.png');
            background-size: cover;
            vertical-align: sub;
            opacity: 0.5;
        }

        .uav-help:hover:after {
            opacity: 0.8;
        }
    </style>
<?php } else {
	echo '<p>'.__("Great, no inactive users.",'uav').'</p>';
} ?>