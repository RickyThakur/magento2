<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Create categories
 */
$categoriesNumber = 200;
$maxNestingLevel = 3;
$anchorStep = 2;

$nestingLevel = 1;
$parentCategoryId = $defaultParentCategoryId = Mage::app()->getStore()->getRootCategoryId();
$nestingPath = "1/$parentCategoryId";
$categoryPath = '';
$categoryIndex = 1;

$categories = array();

$category = new Mage_Catalog_Model_Category();
while ($categoryIndex <= $categoriesNumber) {
    $category->setId(null)
        ->setName("Category $categoryIndex")
        ->setParentId($parentCategoryId)
        ->setPath($nestingPath)
        ->setLevel($nestingLevel)
        ->setAvailableSortBy('name')
        ->setDefaultSortBy('name')
        ->setIsActive(true)
        ->setIsAnchor($categoryIndex++ % $anchorStep == 0)
        ->save();

    $categoryPath .=  '/' . $category->getName();
    $categories[] = ltrim($categoryPath, '/');

    if ($nestingLevel++ == $maxNestingLevel) {
        $nestingLevel = 1;
        $parentCategoryId = $defaultParentCategoryId;
        $nestingPath = '1';
        $categoryPath = '';
    } else {
        $parentCategoryId = $category->getId();
    }
    $nestingPath .= "/$parentCategoryId";
}

/**
 * Create products
 */
$pattern = array(
    '_attribute_set' => 'Default',
    '_type' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
    '_product_websites' => 'base',
    '_category' => function ($index) use ($categories, $categoriesNumber) {
        return $categories[$index % $categoriesNumber];
    },
    'name' => 'Product %s',
    'short_description' => 'Short desc %s',
    'weight' => 1,
    'description' => 'Description %s',
    'sku' => 'product_dynamic_%s',
    'price' => 10,
    'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
    'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
    'tax_class_id' => 0,

    // actually it saves without stock data, but by default system won't show on the frontend products out of stock
    'is_in_stock' => 1,
    'qty' => 100500,
    'use_config_min_qty' => '1',
    'use_config_backorders' => '1',
    'use_config_min_sale_qty' => '1',
    'use_config_max_sale_qty' => '1',
    'use_config_notify_stock_qty' => '1',
    'use_config_manage_stock' => '1',
    'use_config_qty_increments' => '1',
    'use_config_enable_qty_inc' => '1',
    'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
);
$generator = new Magento_ImportExport_Fixture_Generator($pattern, 80000);
$import = new Mage_ImportExport_Model_Import(array('entity' => 'catalog_product', 'behavior' => 'append'));
// it is not obvious, but the validateSource() will actually save import queue data to DB
$import->validateSource($generator);
// this converts import queue into actual entities
$import->importSource();
