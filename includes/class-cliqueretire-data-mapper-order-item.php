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

class Ebox_Cliqueretire_Data_Mapper_Order_Item extends Ebox_Cliqueretire_Object
{
    const CUSTOM_OPTION_VALUE = '_custom';

    protected $order;
    protected $orderItem;
    protected $product;
    protected $helper;

    public function __invoke($order, $orderItem, $product)
    {
        $this->order = $order;
        $this->orderItem = $orderItem;
        $this->product = $product;
        $this->helper = new Ebox_Cliqueretire_Helper();

        $this->mapProductLineId()
            ->mapSku()
            ->mapTitle()
            ->mapQty()
            ->mapPrice()
            ->mapWeight();

        if (!defined('CLIQUERETIRE_IGNORE_ITEM_DIMENSIONS') || !CLIQUERETIRE_IGNORE_ITEM_DIMENSIONS) {
            $this->mapDepth()
                ->mapLength()
                ->mapWidth();
        }

        return $this;
    }

    public function mapProductLineId()
    {
        $productLineId = $this->product->get_id();

        return $this->setProductLineId($productLineId);
    }

    public function mapSku()
    {
        // @TODO: since WC version 3.0 get_variation_id is deprecated
        // suggested to use get_id() instead
        if ($this->product->get_type() == 'variation') {
            $sku = sprintf(
                '%s|%s',
                $this->product->get_sku(),
                $this->product->get_variation_id()
            );
        }
        else {
            $sku = $this->product->get_sku();
        }

        return $this->setSku($sku);
    }

    public function mapTitle()
    {
        $title = $this->orderItem->get_name();

        return $this->setTitle($title);
    }

    public function mapQty()
    {
        $qty = $this->orderItem->get_quantity();

        return $this->setQty($qty);
    }

    public function mapPrice()
    {
        $price = round(
            (
                ($this->orderItem->get_total() + $this->orderItem->get_total_tax())
                /
                $this->orderItem->get_quantity()
            ),
            2
        );

        return $this->setPrice($price);
    }

    public function mapWeight()
    {
        $itemWeight = $this->product->get_weight();

        // Get the weight if available, otherwise stub weight to 0.2kg
        $weight = (!empty($itemWeight) ? $this->helper->convertWeight($itemWeight) : 0.2);

        return $this->setWeight($weight);
    }

    public function mapDepth()
    {
        $depth = $this->product->get_height();

        if (empty($depth)) {
            return $this;
        }

        $depth = $this->helper->convertDimension($depth);

        return $this->setDepth($depth);
    }

    public function mapLength()
    {
        $length = $this->product->get_length();

        if (empty($length)) {
            return $this;
        }

        $length = $this->helper->convertDimension($length);

        return $this->setLength($length);
    }

    public function mapWidth()
    {
        $width = $this->product->get_width();

        if (empty($width)) {
            return $this;
        }

        $width = $this->helper->convertDimension($width);

        return $this->setWidth($width);
    }

    public function mapProductAttribute($attribute, $customAttribute)
    {
        $value = null;

        // If we have a mapped DG custom value, and the custom value is not empty, use this value
        if ($attribute == self::CUSTOM_OPTION_VALUE) {
            $value = $this->product->get_attribute($customAttribute);
        }
        // Otherwise, if we have a mapped text attribute, use this value
        else {
            $value = $this->product->get_attribute($attribute);
        }

        return $value;
    }
}
