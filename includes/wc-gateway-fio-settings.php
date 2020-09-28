<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters( 'wc_fio_settings',
	array(
		'enabled' => array(
			'title'       => __( 'Enable/Disable', 'woocommerce-gateway-fio' ),
			'label'       => __( 'Enable FIO payments', 'woocommerce-gateway-fio' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => __( 'Title', 'woocommerce-gateway-fio' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-fio' ),
			'default'     => __( 'FIO (Digital currency)', 'woocommerce-gateway-fio' ),
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => __( 'Description', 'woocommerce-gateway-fio' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout. Leave it empty and it will not show.', 'woocommerce-gateway-fio' ),
			'default'     => __( 'Pay with FIO.', 'woocommerce-gateway-fio'),
			'desc_tip'    => true,
		),
		'fio_account' => array(
			'title'       => __( 'FIO account', 'woocommerce-gateway-fio' ),
			'type'        => 'text',
			'description' => __( 'Input your FIO account, it must be the owner of the address.', 'woocommerce-gateway-fio' ),
			'default'     => '',
			'placeholder' => 'yphvodse3ehb',
			'desc_tip'    => true,
		),
		'fio_address' => array(
			'title'       => __( 'FIO address', 'woocommerce-gateway-fio' ),
			'type'        => 'text',
			'description' => __( 'Input the FIO address where you want customers to pay FIO to.', 'woocommerce-gateway-fio' ),
			'default'     => '',
			'placeholder' => 'FIO8H4LUhA7fsciJJ7fWcRRTT6GUHp6botr2WeY6ZocfhJbeCyt3B',
			'desc_tip'    => true,
		),
        'prices_in_fio' => array(
            'title'       => __( 'Show prices in FIO', 'woocommerce-gateway-fio' ),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __( 'Show prices on store pages in FIO', 'woocommerce-gateway-fio' ),
            'default'     => 'no',
            'desc_tip'    => true,
            'options'     => array(
                    'no'    => __( 'Default prices', 'woocommerce-gateway-fio' ),
                    'only'    => __( 'Only FIO price', 'woocommerce-gateway-fio' ),
                    'both'    => __( 'Default and FIO prices', 'woocommerce-gateway-fio' ),
            ),
        ),
		'logging' => array(
			'title'       => __( 'Logging', 'woocommerce-gateway-fio' ),
			'label'       => __( 'Log debug messages', 'woocommerce-gateway-fio' ),
			'type'        => 'checkbox',
			'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'woocommerce-gateway-fio' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
	)
);