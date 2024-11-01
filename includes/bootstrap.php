<?php
function uav_initialize_plugin() {
	Codemanas\UserActivationValidate\Init::instance();
	Codemanas\UserActivationValidate\SignUpHandler::instance();
}
add_action( 'plugins_loaded', 'uav_initialize_plugin' );

function UavSH() {
	return Codemanas\UserActivationValidate\SignUpHandler::instance();
}