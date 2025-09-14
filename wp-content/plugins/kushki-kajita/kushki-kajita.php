<?php
/*
Plugin Name: Kushki Kajita Gateway for WooCommerce
Description: Pasarela de pagos Kushki Kajita (tarjetas de crédito) para WooCommerce.
Version: 1.0
Author: Alex Cordova (adcordova@tes.edu.ec)
*/

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'plugins_loaded', 'kushki_kajita_init', 11 );

function kushki_kajita_init() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    class WC_Gateway_Kushki_Kajita extends WC_Payment_Gateway {
        public function __construct() {
            $this->id                 = 'kushki_kajita';
            $this->method_title       = 'Kushki Kajita';
            $this->method_description = 'Paga con tarjeta de crédito usando Kushki (Kajita).';
            $this->has_fields         = true;

            $this->init_form_fields();
            $this->init_settings();

            $this->title              = $this->get_option( 'title' );
            $this->description        = $this->get_option( 'description' );
            $this->test_mode          = 'yes' === $this->get_option( 'test_mode', 'yes' );
            $this->public_id_uat      = $this->get_option( 'public_id_uat' );
            $this->private_id_uat     = $this->get_option( 'private_id_uat' );
            $this->public_id_prod     = $this->get_option( 'public_id_prod' );
            $this->private_id_prod    = $this->get_option( 'private_id_prod' );

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Activar/Desactivar',
                    'type'    => 'checkbox',
                    'label'   => 'Habilitar pagos con Kushki Kajita',
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'   => 'Título',
                    'type'    => 'text',
                    'default' => 'Tarjeta de Crédito (Kushki)'
                ),
                'description' => array(
                    'title'   => 'Descripción',
                    'type'    => 'textarea',
                    'default' => 'Paga con tarjeta usando Kushki.'
                ),
                'test_mode' => array(
                    'title'   => 'Modo Prueba',
                    'type'    => 'checkbox',
                    'label'   => 'Usar sandbox (UAT)',
                    'default' => 'yes'
                ),
                'public_id_uat' => array(
                    'title' => 'Public Merchant ID (UAT)',
                    'type'  => 'text'
                ),
                'private_id_uat' => array(
                    'title' => 'Private Merchant ID (UAT)',
                    'type'  => 'text'
                ),
                'public_id_prod' => array(
                    'title' => 'Public Merchant ID (PROD)',
                    'type'  => 'text'
                ),
                'private_id_prod' => array(
                    'title' => 'Private Merchant ID (PROD)',
                    'type'  => 'text'
                ),
            );
        }

        public function payment_fields() {
            echo '<div id="kushki-kajita-form"></div>';
        }

        public function payment_scripts() {
            if ( ! is_checkout() ) return;

            $public_id = $this->test_mode ? $this->public_id_uat : $this->public_id_prod;

            wp_enqueue_script(
                'kushki-checkout',
                'https://cdn.kushkipagos.com/kushki-checkout.js',
                array(),
                null,
                true
            );

            // Obtenemos el monto real de la orden
            if ( is_checkout() && WC()->cart ) {
                $total = WC()->cart->total;
            } else {
                $total = 0;
            }

            wp_add_inline_script( 'kushki-checkout', "
                var checkout = new KushkiCheckout({
                    form: 'kushki-kajita-form',
                    publicMerchantId: '{$public_id}',
                    inTestEnvironment: " . ( $this->test_mode ? 'true' : 'false' ) . ",
                    amount: { subtotalIva: {$total}, iva: 0, subtotalIva0: 0, currency: 'USD' }
                });
            " );
        }

        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            $kushki_token = isset($_POST['kushkiToken']) ? sanitize_text_field($_POST['kushkiToken']) : '';

            if ( empty( $kushki_token ) ) {
                wc_add_notice( 'No se pudo generar el token de pago con Kushki.', 'error' );
                return array( 'result' => 'failure' );
            }

            $private_id = $this->test_mode ? $this->private_id_uat : $this->private_id_prod;
            $endpoint   = $this->test_mode
                ? 'https://api-uat.kushkipagos.com/card/v1/charges'
                : 'https://api.kushkipagos.com/card/v1/charges';

            $amount_minor = (int) round( floatval( $order->get_total() ) * 100 );

            $body = array(
                'token'  => $kushki_token,
                'amount' => array(
                    'subtotalIva' => $amount_minor,
                    'subtotalIva0' => 0,
                    'iva' => 0,
                    'currency' => get_woocommerce_currency()
                ),
                'metadata' => array(
                    'orderID' => (string) $order_id
                )
            );

            $resp = wp_remote_post( $endpoint, array(
                'headers' => array(
                    'Private-Merchant-Id' => $private_id,
                    'Content-Type'        => 'application/json'
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 45
            ));

            if ( is_wp_error( $resp ) ) {
                wc_add_notice( 'Error comunicando con Kushki.', 'error' );
                return array( 'result' => 'failure' );
            }

            $resp_body = json_decode( wp_remote_retrieve_body( $resp ), true );

            if ( isset( $resp_body['details']['transactionStatus'] ) && $resp_body['details']['transactionStatus'] === 'APPROVAL' ) {
                $order->payment_complete( $resp_body['ticketNumber'] );
                $order->add_order_note( 'Kushki: Pago aprobado. Ticket: ' . $resp_body['ticketNumber'] );
                WC()->cart->empty_cart();
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order )
                );
            } else {
                $order->update_status( 'failed', 'Kushki: Pago rechazado.' );
                wc_add_notice( 'El pago fue rechazado por Kushki.', 'error' );
                return array( 'result' => 'failure' );
            }
        }
    }

    function add_kushki_kajita_class( $methods ) {
        $methods[] = 'WC_Gateway_Kushki_Kajita';
        return $methods;
    }
    add_filter( 'woocommerce_payment_gateways', 'add_kushki_kajita_class' );
}
