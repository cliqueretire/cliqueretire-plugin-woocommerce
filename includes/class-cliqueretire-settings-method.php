<?php
/**
 * Ebox.IT
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is available through the world-wide-web at this URL:
 * http://www.cliqueretire.com.br/licencing
 *
 * @category   Ebox
 * @copyright  Copyright (c) 2021 by Clique Retire (http://www.cliqueretire.com.br)
 * @author     Christiano de Chermont <chermont@cliqueretire.com.br>
 * @license    http://www.cliqueretire.com.br/licencing
 */

class Ebox_Cliqueretire_Settings_Method
{
    /**
     * Init fields.
     *
     * Add fields to the Cliqueretire settings page.
     *
     */
    public function getFields($isInstance = false)
    {
        $fields['enabled'] = array(
            'id' => 'wc_settings_cliqueretire_enabled',
            'title' => __('Habilitado', 'woocommerce-cliqueretire'),
            'desc'     => 'Utilize esse metodo de envio para cotação em tempo real.',
            'desc_tip' => true,
            'class' => 'wc-enhanced-select',
            'default' => 'no',
            'type' => 'select',
            'options' => array(
                'no' => __('Não', 'woocommerce-cliqueretire'),
                'yes' => __('Sim', 'woocommerce-cliqueretire'),
            ),
        );

        $fields['title'] = array(
            'title' => __('Título', 'woocommerce-cliqueretire'),
            'type' => 'text',
            'default' => 'Clique Retire',
        );

        // Only show "filter enabled" and "filter_enabled_products"
        // on the legacy shipping method class
        //
        // Also enables merchants to avoid this functionality if they have
        // larger stores by setting the "CLIQUERETIRE_PRODUCT_FILTERING" constant
        // to false
        //
        // @Depreciated: this functionality is due to be removed in 2018 Q1;
        if (!$isInstance && !defined('CLIQUERETIRE_DISABLE_PRODUCT_FILTER')) {
            $fields['filter_enabled'] = array(
                'title' => __('Filtrar por produtos habilitados', 'woocommerce-cliqueretire'),
                'description' => __('Aplica a forma de envio pela CliqueRetire apenas aos produtos habilitados', 'woocommerce-cliqueretire'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('Não', 'woocommerce-cliqueretire'),
                    'yes' => __('Sim', 'woocommerce-cliqueretire'),
               ),
            );

            $fields['filter_enabled_products'] = array(
                'title' => __('Habilitar Produtos', 'woocommerce-cliqueretire'),
                'description' => __('Produtos habilitados para envio pela Clique Retire', 'woocommerce-cliqueretire'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => '',
                'type' => 'multiselect',
                'options' => $this->_getProducts(),
            );
        }

        $fields['filter_attribute'] = array(
            'title' => __('Filtar produtos pelo atributo', 'woocommerce-cliqueretire'),
            'description' => __('Aplica a forma de envio pela Clique Retire apenas aos produtos que possuem o atributo relacionado', 'woocommerce-cliqueretire'),
            'desc_tip' => true,
            'class' => 'wc-enhanced-select',
            'default' => 'no',
            'type' => 'select',
            'options' => array(
                'no' => __('Não', 'woocommerce-cliqueretire'),
                'yes' => __('Sim', 'woocommerce-cliqueretire'),
           ),
        );

        $fields['filter_attribute_code'] = array(
            'title' => __('Selecione o atributo', 'woocommerce-cliqueretire'),
            'description' => __('Atributo relacionado ao produto', 'woocommerce-cliqueretire'),
            'desc_tip' => true,
            'type' => 'select',
            'class' => 'wc-enhanced-select',
            'default' => '',
            'options' => $this->_getAttributes(),
        );

        $fields['filter_attribute_value'] = array(
            'title' => __('Valor do atributo', 'woocommerce-cliqueretire'),
            'description' => __('Valor do atributo relacionado ao produto', 'woocommerce-cliqueretire'),
            'desc_tip' => true,
            'default' => '',
            'type' => 'text',
        );
        $fields['margin_days'] = array(
            'title' => __('Aplicar Margem Prazo Entrega'),
            'class' => 'wc-enhanced-select',
            'default' => 'no',
            'description' => __('Adicionar margem no prazo de dias para entrega do pedido', 'woocommerce-cliqueretire'),
            'desc_tip' => true,
            'type' => 'select',
            'options' => array(
                'no' => __('Não', 'woocommerce-cliqueretire'),
                'yes' => __('Sim', 'woocommerce-cliqueretire')
           ),
        );

        $fields['margin_days_amount'] = array(
            'title' => __('Margem prazo entrega em dias', 'woocommerce-cliqueretire'),
            'description' => __('Informe os dias que deseja adicionar ao prazo de entrega', 'woocommerce-cliqueretire'),
            'desc_tip' => true,
            'default' => '',
            'type' => 'number',
        );

        $fields['margin'] = array(
            'title' => __('Aplicar Margem Valor Frete'),
            'class' => 'wc-enhanced-select',
            'default' => 'no',
            'description' => __('Adicionar margem no valor da cotação do frete', 'woocommerce-cliqueretire'),
            'desc_tip' => true,
            'type' => 'select',
            'options' => array(
                'no' => __('Não', 'woocommerce-cliqueretire'),
                'yes-percentage' => __('Sim - Percentual', 'woocommerce-cliqueretire'),
                'yes-fixed' => __('Sim - Valor Fixo', 'woocommerce-cliqueretire'),
           ),
        );

        $fields['margin_amount'] = array(
            'title' => __('Valor Margem Frete', 'woocommerce-cliqueretire'),
            'description' => __('Informe o valor da margem, fixa ex: 5.50 ou percentual ex: 5', 'woocommerce-cliqueretire'),
            'desc_tip' => true,
            'default' => '',
            'type' => 'text',
        );

        return $fields;
    }

    /**
     * Get products with id/name for a multiselect
     *
     * @return array     An associative array of product ids and name
     */
    private function _getProducts()
    {
        $productArgs = array(
            'post_type' => 'product',
            'posts_per_page' => -1
        );

        $products = get_posts($productArgs);

        $productOptions = array();

        foreach ($products as $product) {
            $productOptions[$product->ID] = __($product->post_title, 'woocommerce-cliqueretire');
        }

        return $productOptions;
    }

    public function _getAttributes()
    {
        $productAttributes = array();

        $attributeTaxonomies = wc_get_attribute_taxonomies();

        foreach ($attributeTaxonomies as $tax) {
            $productAttributes[$tax->attribute_name] = __($tax->attribute_name, 'woocommerce-cliqueretire');
        }

        return $productAttributes;
    }
}