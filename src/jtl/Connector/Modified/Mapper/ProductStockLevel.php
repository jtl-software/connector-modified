<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;
use jtl\Connector\Model\ProductStockLevel as ProductStockLevelModel;

class ProductStockLevel extends BaseMapper
{
    public function pull($data)
    {
        $stockLevel = new ProductStockLevelModel();
        $stockLevel->setProductId($this->identity($data['products_id']));
        $stockLevel->setStockLevel(floatval($data['products_quantity']));

        return array($stockLevel);
    }
}
