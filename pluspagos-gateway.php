<?php
/**
 * Plugin Name: PlusPagos
 * Plugin URI: https://lucasfiorio.tech
 * Description: Integración con PlusPagos para cobros con tarjeta de crédito/débito.
 * Author: Lucas S. Fiorio
 * Author URI: https://lucasfiorio.tech
 * Version: 1.1.0
 * WC tested up to: 10.2.1
 * Text Domain: pluspagos-gateway
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2026 Lucas S. Fiorio
 *
 * @package   PlusPagos-Gateway
 * @author    Lucas S. Fiorio
 * @category  Admin
 * @copyright Copyright (c) 2026, Lucas S. Fiorio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include helper classes
require_once plugin_dir_path( __FILE__ ) . 'lib/AESEncrypter.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/SHA256Encript.php';

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

add_action( 'plugins_loaded', 'pluspagos_gateway_init' );

function pluspagos_gateway_init() {

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    class WC_PlusPagos_Gateway extends WC_Payment_Gateway {

        public $testmode;
        public $url_post_test;
        public $url_post_live;
        public $test_secret_key;
        public $test_comercio_key;
        public $live_secret_key;
        public $live_comercio_key;
        public $comercio_name;
        public $titulo_articulo;
        public $cargo_adicional;
        public $cargo_fijo;
        public $cargo_porcentaje;
        public $disable_free_shipping;
        public $url_callback_success;
        public $url_callback_cancel;

        public function __construct() {
            $this->id                 = 'pluspagos_gateway';
            $this->icon               = apply_filters( 'woocommerce_pluspagos_icon', plugins_url( 'pluspagos-gateway/img/logos-tarjetas.png', plugin_dir_path( __FILE__ ) ) );
            $this->has_fields         = false;
            $this->method_title       = 'PlusPagos';
            $this->method_description = 'Integración con PlusPagos. Configura los datos de conexión y opciones de pago.';

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->enabled      = $this->get_option( 'enabled' );
            $this->testmode     = 'yes' === $this->get_option( 'testmode' );

            // Configuration Fields
            $this->url_post_test     = $this->get_option( 'url_post_test' );
            $this->url_post_live     = $this->get_option( 'url_post_live' );
            $this->test_secret_key   = $this->get_option( 'test_secret_key' );
            $this->test_comercio_key = $this->get_option( 'test_comercio_key' );
            $this->live_secret_key   = $this->get_option( 'live_secret_key' );
            $this->live_comercio_key = $this->get_option( 'live_comercio_key' );
            $this->comercio_name     = $this->get_option( 'comercio_name' );
            
            $this->titulo_articulo   = $this->get_option( 'titulo_articulo' );
            $this->cargo_adicional   = $this->get_option( 'cargo_adicional' );
            $this->cargo_fijo        = $this->get_option( 'cargo_fijo' );
            $this->cargo_porcentaje  = $this->get_option( 'cargo_porcentaje' );
            $this->disable_free_shipping = 'yes' === $this->get_option( 'disable_free_shipping' );

            $this->url_callback_success = $this->get_option( 'url_callback_success' );
            $this->url_callback_cancel  = $this->get_option( 'url_callback_cancel' );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_api_pluspagos_gateway', array( $this, 'webhook' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            add_action( 'init', array( $this, 'handle_return' ) );
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Habilitar/Deshabilitar',
                    'type'    => 'checkbox',
                    'label'   => 'Habilitar PlusPagos Gateway',
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => 'Título',
                    'type'        => 'text',
                    'description' => 'Esto controla el título que ve el usuario durante el pago.',
                    'default'     => 'Tarjeta de Crédito / Débito',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Descripción',
                    'type'        => 'textarea',
                    'description' => 'Esto controla la descripción que ve el usuario durante el pago.',
                    'default'     => 'Paga de forma segura con tu tarjeta a través de PlusPagos.',
                ),
                
                // Entorno de Pruebas
                'section_test' => array(
                    'title'       => 'Configuración de Entorno de Pruebas (Sandbox)',
                    'type'        => 'title',
                    'description' => '',
                ),
                'testmode' => array(
                    'title'       => 'Modo Test',
                    'label'       => 'Habilitar Modo Test',
                    'type'        => 'checkbox',
                    'description' => 'Habilita el modo de pruebas para usar las credenciales de Sandbox.',
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'url_post_test' => array(
                    'title'       => 'URL POST Test',
                    'type'        => 'text',
                    'description' => 'URL para el entorno de pruebas.',
                    'default'     => 'https://sandboxpp.asjservicios.com.ar/',
                    'placeholder' => 'https://sandboxpp.asjservicios.com.ar/',
                ),
                'test_comercio_key' => array(
                    'title'       => 'ID Comercio (Test)',
                    'type'        => 'text',
                    'description' => 'ID de comercio provisto por PlusPagos para Sandbox.',
                ),
                'test_secret_key' => array(
                    'title'       => 'Clave Secreta / Hash (Test)',
                    'type'        => 'password',
                    'description' => 'Clave utilizada para firmar/encriptar en Sandbox.',
                ),

                // Entorno de Producción
                'section_live' => array(
                    'title'       => 'Configuración de Entorno de Producción',
                    'type'        => 'title',
                    'description' => '',
                ),
                'url_post_live' => array(
                    'title'       => 'URL POST Producción',
                    'type'        => 'text',
                    'description' => 'URL para el entorno de producción.',
                    'default'     => 'https://botonpp.asjservicios.com.ar/',
                    'placeholder' => 'https://botonpp.asjservicios.com.ar/',
                ),
                'live_comercio_key' => array(
                    'title'       => 'ID Comercio (Producción)',
                    'type'        => 'text',
                    'description' => 'ID de comercio provisto por PlusPagos para Producción.',
                ),
                'live_secret_key' => array(
                    'title'       => 'Clave Secreta / Hash (Producción)',
                    'type'        => 'password',
                    'description' => 'Clave utilizada para firmar/encriptar en Producción.',
                ),

                // Datos del Comercio y Productos
                'section_info' => array(
                    'title'       => 'Información del Comercio y Producto',
                    'type'        => 'title',
                    'description' => '',
                ),
                'comercio_name' => array(
                    'title'       => 'Nombre del Comercio',
                    'type'        => 'text',
                    'default'     => get_bloginfo( 'name' ),
                ),
                'titulo_articulo' => array(
                    'title'       => 'Título de artículo por defecto',
                    'type'        => 'text',
                    'default'     => 'Compra Online',
                ),

                // Cargos Adicionales
                'section_charges' => array(
                    'title'       => 'Cargos Adicionales',
                    'type'        => 'title',
                    'description' => 'Configura recargos opcionales.',
                ),
                 'cargo_adicional' => array(
                    'title'       => 'Descripción Cargo Adicional',
                    'type'        => 'text',
                    'description' => 'Texto descriptivo para cargos extra (si aplica).',
                ),
                'cargo_fijo' => array(
                    'title'       => 'Cargo Fijo ($)',
                    'type'        => 'number',
                    'description' => 'Monto fijo a sumar al total.',
                    'default'     => '0',
                    'custom_attributes' => array( 'step' => '0.01', 'min' => '0' ),
                ),
                'cargo_porcentaje' => array(
                    'title'       => 'Cargo Porcentaje (%)',
                    'type'        => 'number',
                    'description' => 'Porcentaje a sumar al total.',
                    'default'     => '0',
                     'custom_attributes' => array( 'step' => '0.01', 'min' => '0' ),
                ),
                'disable_free_shipping' => array(
                    'title'   => 'Desactivar en envío gratuito',
                    'type'    => 'checkbox',
                    'label'   => 'No aplicar cargos si el envío es gratuito',
                    'default' => 'no'
                ),

                // Callbacks y URLs
                'section_urls' => array(
                    'title'       => 'URLs de Retorno y Callbacks',
                    'type'        => 'title',
                    'description' => 'Configura las URLs de éxito, cancelación y el Webhook. Deja vacío para usar los valores por defecto.',
                ),
                'url_callback_success' => array(
                    'title'       => 'URL de Éxito (Success)',
                    'type'        => 'text',
                    'placeholder' => 'Dejar vacío para usar ' . wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() ),
                    'description' => 'URL a la que redirige tras un pago exitoso.',
                ),
                'url_callback_cancel' => array(
                    'title'       => 'URL de Cancelación/Fallo',
                    'type'        => 'text',
                     'placeholder' => 'Dejar vacío para usar ' . wc_get_cart_url(), // Usually cancel goes to cart? or cancel endpoint. Default uses order-cancel.
                    'description' => 'URL a la que redirige si se cancela el pago.',
                ),
            );
        }

        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            // Mark as on-hold (we're awaiting the payment)
            $order->update_status( 'on-hold', __( 'Esperando confirmación de PlusPagos.', 'pluspagos-gateway' ) );

            // Reduce stock levels
            wc_reduce_stock_levels( $order_id );

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }

        public function thankyou_page( $order_id ) {
            $order = wc_get_order( $order_id );

            if ( $order->get_payment_method() != $this->id ) {
                return;
            }
            
            // Avoid re-sending to payment gateway (already paid or already redirected)
            if ( $order->has_status( 'completed' ) || $order->has_status( 'processing' ) || $order->get_meta( '_pluspagos_payment_sent' ) ) {
                echo '<p>' . __( 'Tu pago está siendo procesado. Gracias por tu compra.', 'pluspagos-gateway' ) . '</p>';
                return;
            }

            // Mark as sent to prevent double submission on page reload or callback return
            $order->update_meta_data( '_pluspagos_payment_sent', 'yes' );
            $order->save();

            // Determine Environment
            if ( $this->testmode ) {
                $url_post    = $this->url_post_test;
                $comercio_id = $this->test_comercio_key;
                $secret_key  = $this->test_secret_key;
            } else {
                $url_post    = $this->url_post_live;
                $comercio_id = $this->live_comercio_key;
                $secret_key  = $this->live_secret_key;
            }

            // Generate Callbacks
            // CallbackSuccess: use a dedicated return handler to avoid re-triggering the payment form
            $return_url = add_query_arg( array(
                'pluspagos_return' => '1',
                'order_id'         => $order_id,
                'key'              => $order->get_order_key(),
            ), home_url( '/' ) );
            $callbackSuccess = ! empty( $this->url_callback_success ) ? $this->url_callback_success : $return_url;
            $callbackCancel  = ! empty( $this->url_callback_cancel ) ? $this->url_callback_cancel : $order->get_cancel_order_url();

            // Transaction Data
            $timestamp           = date( 'his' );
            $site_transaction_id = $order_id . '-' . $timestamp;
            
            // Calculate Totals with Extra Charges
            $total = $order->get_total();
            
            // Check logic for Free Shipping disable
            $free_shipping = false;
            foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
                if( strpos( $item->get_method_id(), 'free_shipping' ) !== false ){
                    $free_shipping = true;
                    break;
                }
            }

            // Apply Fixed Charge
            if ( ! empty( $this->cargo_fijo ) ) {
                if ( ! ($this->disable_free_shipping && $free_shipping) ) {
                     $total += floatval( $this->cargo_fijo );
                }
            }

            // Apply Percentage Charge
             if ( ! empty( $this->cargo_porcentaje ) ) {
                 if ( ! ($this->disable_free_shipping && $free_shipping) ) {
                     $total += ( $total * floatval( $this->cargo_porcentaje ) / 100 );
                }
            }

            $amount = str_replace( '.', '', number_format( $total, 2, '.', '' ) ); // Remove decimal point (156.34 -> 15634)
            
            // Sucursal (Empty by default per original code)
            $sucursalComercio = '';

            // AES Encryption
            $aes = new AESEncrypter();
            $phrase = $secret_key; // Assuming Secret Key acts as the AES passphrase prefix

            $callbackSuccessEnc = $aes->EncryptString( $callbackSuccess, $phrase );
            $callbackCancelEnc  = $aes->EncryptString( $callbackCancel, $phrase );
            $sucursalEnc        = $aes->EncryptString( $sucursalComercio, $phrase );
            $montoEnc           = $aes->EncryptString( $amount, $phrase );

            // Hash Generation
            // Signature: IP + Comercio + Sucursal + Monto + SecretKey
            $hashObj = new SHA256Encript();
            $hash = $hashObj->Generate( '', $secret_key, $comercio_id, $sucursalComercio, $amount );

            // Product Description
            $product_title_setting = !empty($this->titulo_articulo) ? $this->titulo_articulo : 'Compra en ' . get_bloginfo( 'name' );
            $product_desc = $product_title_setting . " - Orden #" . $order_id;

            // Form Data
            $data = array(
                'CallbackSuccess'       => $callbackSuccessEnc,
                'CallbackCancel'        => $callbackCancelEnc,
                'Comercio'              => $comercio_id,
                'SucursalComercio'      => $sucursalEnc,
                'Hash'                  => $hash,
                'TransaccionComercioId' => $site_transaction_id,
                'Monto'                 => $montoEnc,
                'Producto[0]'           => $product_desc
            );

            // Render Form
            echo '<p>' . __( 'Redirigiendo a PlusPagos...', 'pluspagos-gateway' ) . '</p>';
            echo '<form action="' . esc_url( $url_post ) . '" method="post" id="pluspagos_payment_form">';
            foreach ( $data as $key => $value ) {
                echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
            }
            echo '<input type="submit" class="button-alt" id="submit_pluspagos_payment_form" value="' . __( 'Pagar', 'pluspagos-gateway' ) . '" />';
            echo '</form>';

            // Auto-submit JS
            wc_enqueue_js( '
                jQuery("#pluspagos_payment_form").submit();
            ' );
        }

        /**
         * Handle the return from PlusPagos payment platform.
         * Redirects to the real WooCommerce thank-you page without re-triggering the payment form.
         */
        public function handle_return() {
            if ( ! isset( $_GET['pluspagos_return'] ) ) {
                return;
            }

            $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
            $key      = isset( $_GET['key'] ) ? wc_clean( $_GET['key'] ) : '';
            $order    = wc_get_order( $order_id );

            if ( ! $order || ! $order->key_is_valid( $key ) ) {
                wp_redirect( wc_get_checkout_url() );
                exit;
            }

            // Redirect to the real thank-you page (the meta flag prevents form re-generation)
            wp_redirect( $this->get_return_url( $order ) );
            exit;
        }

        public function webhook() {
            // Retrieve data
            $json_input = file_get_contents('php://input');
            $data       = json_decode( $json_input, true );

            if ( empty( $data ) ) {
                // Fallback for form-data if NOT JSON
                 $data = $_POST;
            }

            if ( empty( $data ) || ! isset( $data['TransaccionComercioId'] ) ) {
                wp_die( 'No data received', 'PlusPagos Webhook', array( 'response' => 400 ) );
            }

            // Extract Order ID
            // Format: OrderID-Timestamp
            $transaccion_parts = explode( '-', $data['TransaccionComercioId'] );
            $order_id          = $transaccion_parts[0];
            $order             = wc_get_order( $order_id );

            if ( ! $order ) {
                wp_die( 'Order not found', 'PlusPagos Webhook', array( 'response' => 404 ) );
            }

            $estado_id = isset( $data['EstadoId'] ) ? intval( $data['EstadoId'] ) : 0;
            
            // Status Mapping — según documentación oficial de PlusPagos:
            // 1  => CREADA        → en espera
            // 2  => EN_PAGO       → en espera (aguardando confirmación)
            // 3  => REALIZADA     → pago completo ✅
            // 4  => RECHAZADA     → cancelado
            // 7  => EXPIRADA      → cancelado
            // 8  => CANCELADA     → cancelado
            // 9  => DEVUELTA      → reembolsado
            // 10 => PENDIENTE     → en espera
            // 11 => VENCIDA       → cancelado

            if ( $estado_id == 3 ) {
                // REALIZADA — pago aprobado y exitoso
                $order->payment_complete( $data['TransaccionComercioId'] );
                $order->add_order_note( 'Pago aprobado por PlusPagos. ID Transacción: ' . $data['TransaccionComercioId'] );
            } elseif ( in_array( $estado_id, array( 1, 2, 10 ) ) ) {
                // CREADA / EN_PAGO / PENDIENTE — pago en proceso, mantener en espera
                $order->update_status( 'on-hold', 'Pago en espera de confirmación de PlusPagos (EstadoId: ' . $estado_id . ').' );
            } elseif ( in_array( $estado_id, array( 4, 7, 8, 11 ) ) ) {
                // RECHAZADA / EXPIRADA / CANCELADA / VENCIDA
                $order->update_status( 'cancelled', 'Pago cancelado/rechazado por PlusPagos (EstadoId: ' . $estado_id . ').' );
                // Resetear flag para que el cliente pueda reintentar el pago
                $order->delete_meta_data( '_pluspagos_payment_sent' );
                $order->save();
            } elseif ( $estado_id == 9 ) {
                // DEVUELTA — reembolso
                $order->update_status( 'refunded', 'Pago devuelto/reembolsado por PlusPagos.' );
            } else {
                $order->add_order_note( 'Webhook recibido con EstadoId desconocido: ' . $estado_id );
            }

            // Acknowledge receipt
            header( 'HTTP/1.1 200 OK' );
            exit;
        }
    }
}

function pluspagos_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_PlusPagos_Gateway';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'pluspagos_add_gateway_class' );
