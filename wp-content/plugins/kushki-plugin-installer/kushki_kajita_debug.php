<?php
/*
Plugin Name: Kushki Kajita Debug for WooCommerce
Description: Integración de prueba Kajita en WooCommerce con soporte para checkout clásico y basado en bloques.
Version: 4.0
Author: Alex Cordova (adcordova@tes.edu.ec)
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'kushki_kajita_debug_init', 11 );

function kushki_kajita_debug_init() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    class WC_Gateway_Kushki_Kajita_Debug extends WC_Payment_Gateway {
        public $public_id_uat;
        public $public_id_prod;
        public $private_id_uat;
        public $private_id_prod;
        public $kform_id;
        public $test_mode;

        public function __construct() {
            $this->id                 = 'kushki_kajita_debug';
            $this->method_title       = 'Kushki Kajita Debug';
            $this->method_description = 'Integración de prueba con div dinámico y soporte para bloques.';
            $this->has_fields         = true;

            $this->init_form_fields();
            $this->init_settings();

            $this->title         = $this->get_option( 'title' );
            $this->description   = $this->get_option( 'description' );
            $this->test_mode     = 'yes' === $this->get_option( 'test_mode', 'yes' );
            $this->public_id_uat = $this->get_option( 'public_id_uat' );
            $this->public_id_prod= $this->get_option( 'public_id_prod' );
            $this->private_id_uat= $this->get_option( 'private_id_uat' );
            $this->private_id_prod= $this->get_option( 'private_id_prod' );
            $this->kform_id      = $this->get_option( 'kform_id' );

            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array( $this, 'process_admin_options' )
            );

            // Registrar integración con bloques
            add_action( 'woocommerce_blocks_loaded', array( $this, 'register_payment_method_block' ) );
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Activar/Desactivar',
                    'type'    => 'checkbox',
                    'label'   => 'Habilitar pagos con Kushki Kajita',
                    'default' => 'yes',
                ),
                'title' => array(
                    'title'   => 'Título',
                    'type'    => 'text',
                    'default' => 'Tarjeta de Crédito (Kushki)',
                ),
                'description' => array(
                    'title'   => 'Descripción',
                    'type'    => 'textarea',
                    'default' => 'Paga con tarjeta usando Kushki.',
                ),
                'test_mode' => array(
                    'title'   => 'Modo Prueba',
                    'type'    => 'checkbox',
                    'label'   => 'Usar sandbox (UAT)',
                    'default' => 'yes',
                ),
                'public_id_uat' => array(
                    'title' => 'Public Merchant ID (UAT)',
                    'type'  => 'text',
                ),
                'private_id_uat' => array(
                    'title' => 'Private Merchant ID (UAT)',
                    'type'  => 'text',
                ),
                'public_id_prod' => array(
                    'title' => 'Public Merchant ID (PROD)',
                    'type'  => 'text',
                ),
                'private_id_prod' => array(
                    'title' => 'Private Merchant ID (PROD)',
                    'type'  => 'text',
                ),
                'kform_id' => array(
                    'title'       => 'Kform ID',
                    'type'        => 'text',
                    'description' => 'ID del formulario Kajita creado en la consola de Kushki.',
                ),
            );
        }

        public function payment_fields() {
            if ( $this->description ) {
                echo wpautop( wp_kses_post( $this->description ) );
            }
            echo '<div id="kushki-kajita-form"></div>';
        }

        public function register_payment_method_block() {
            if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
                return;
            }

            // Registrar script
            wp_register_script(
                'kushki-block-js',
                plugin_dir_url( __FILE__ ) . 'kushki-block.js',
                array( 'wc-blocks-registry', 'wp-element', 'react', 'react-dom' ),
                filemtime( plugin_dir_path( __FILE__ ) . 'kushki-block.js' ),
                true
            );

            $public_id = $this->test_mode ? $this->public_id_uat : $this->public_id_prod;

            wp_localize_script(
                'kushki-block-js',
                'kushki_params',
                array(
                    'public_id'   => $public_id,
                    'kform_id'    => $this->kform_id,
                    'test_mode'   => $this->test_mode,
                    'cart_total'  => WC()->cart ? (int) ( WC()->cart->total * 100 ) : 0,
                    'title'       => $this->title,
                    'description' => $this->description,
                )
            );

            add_filter( 'woocommerce_blocks_payment_method_type_registration', function ( $payment_method_registry ) {
                $payment_method_registry->register( new WC_Gateway_Kushki_Kajita_Debug_Blocks() );
                return $payment_method_registry;
            } );
        }
    }

    function add_kushki_kajita_debug_class( $methods ) {
        $methods[] = 'WC_Gateway_Kushki_Kajita_Debug';
        return $methods;
    }
    add_filter( 'woocommerce_payment_gateways', 'add_kushki_kajita_debug_class' );
}

// Clase de soporte para Blocks (fuera de la clase principal)
if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
    class WC_Gateway_Kushki_Kajita_Debug_Blocks extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
        protected $name = 'kushki_kajita_debug';

        public function initialize() {
            $this->settings = get_option( 'woocommerce_kushki_kajita_debug_settings', [] );
        }

        public function get_payment_method_script_handles() {
            return array( 'kushki-block-js' );
        }

        public function is_active() {
            return true;
        }
    }
}
