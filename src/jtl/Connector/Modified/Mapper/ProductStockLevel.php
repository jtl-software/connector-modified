<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\ProductStockLevel as ProductStockLevelModel;

class ProductStockLevel extends BaseMapper
{
    public function pull($data = null, $limit = null)
    {
        $stockLevel = new ProductStockLevelModel();
        $stockLevel->setProductId($this->identity($data['products_id']));
        $stockLevel->setStockLevel(floatval($data['products_quantity']));

        return array($stockLevel);
    }

    public function push(ProductStockLevelModel $stockLevel)
    {
        $productId = $stockLevel->getProductId()->getEndpoint();

        if (!empty($productId)) {
            $this->db->query('UPDATE products SET products_quantity='.round($stockLevel->getStockLevel()).' WHERE products_id='.$productId);

            return $stockLevel;
        }

        return false;
    }
}
