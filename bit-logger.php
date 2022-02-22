<?php
/**
 * Plugin Name: BIT - Logger
 * Plugin URI: https://exlac.com/
 * Description: A developer tool to debug stuff nicelly
 * Version: 1.0
 * Author: Galib, Rafiq
 * Author URI: https://exlac.com
 * License: GPLv2 or later
 * Text Domain: bit-logger
 */
// prevent direct access to the file
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

class BitLogger {

    protected static $instance = null;
    protected $logs = [];
    public $log_output_file_path = '';
    public $log_output_url_path = '';
    
    protected function __construct() {
        $this->set_output_file_path( dirname( __FILE__ ) . '/bit-logger' );
        $this->set_output_url_path( 'bit-logger' );

        $this->init_setup();
    }

    protected function init_setup() {
        add_action( 'rest_api_init', [ $this, 'register_rest_api' ] );
    }

    public function register_rest_api() {
        register_rest_route( 
            'bit-logger/v1', '/logs',
            [
                [
                    'methods'             => 'GET',
					'callback'            => [ $this, 'get_rest_api_logs' ],
					'permission_callback' => '__return_true',
                ]
            ]
        );
    }

    public function get_rest_api_logs() {
        $logs = $this->logs;

        if ( ! file_exists( $this->log_output_file_path ) ) {
            return $logs;
        }

        $contents = file_get_contents( $this->log_output_file_path );

        if ( empty( $contents ) ) {
            return $logs;
        }

        return json_decode( $contents, true );
    }

    protected function set_output_file_path( $path ) {

        $this->log_output_file_path = $path . '.json';

        return $this;
    }

    public function set_output_url_path( $path ) {

        $this->log_output_url_path = $path;

        return $this;
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new BitLogger();
        }

        return self::$instance;
    }

    public function add_log( $namespace, $data, $file = __FILE__, $line = __LINE__ ) {
        $this->logs[ $namespace ][] = [
            'file'      => $file,
            'line'      => $line,
            'data_type' => gettype( $data ),
            'data'      => $data,
        ];

        $this->print_log();
    }

    public function print_log( $namespace = '' ) {

        $logs = ( ! empty( $namespace ) && isset( $this->logs[ $namespace ] ) ) ? $this->logs[ $namespace ] : $this->logs;

        if ( empty( $this->log_output_file_path ) ) {
            return;
        }

        file_put_contents( $this->log_output_file_path, "" );

        if ( empty( $logs ) ) {
            return;
        }

        file_put_contents( $this->log_output_file_path, json_encode( $logs ) );
    }

}

if ( ! function_exists( 'BitLogger' ) ) {
    function BitLogger() {
        return BitLogger::get_instance();
    }
}