<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\ProductStockLevel as ProductStockLevelModel;

class ProductStockLevel extends AbstractMapper
{
    public function pull($data = null, $limit = null): array
    {
        $stockLevel = new ProductStockLevelModel();
        $stockLevel->setProductId($this->identity($data['products_id']));
        $stockLevel->setStockLevel(floatval($data['products_quantity']));

        return [$stockLevel];
    }

    /**
     * @param ProductStockLevelModel $model
     * @param \stdClass|null $dbObj
     * @return false|DataModel
     */
    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        $productId = $model->getProductId()->getEndpoint();
        $isVarCombi = Product::isVariationChild($productId);
        
        if (!empty($productId) && $isVarCombi == false) {
            $this->db->query('UPDATE products SET products_quantity='.round($model->getStockLevel()).' WHERE products_id='.$productId);
            
            return $model;
        } elseif (!empty($productId) && $isVarCombi == true) {
            $this->db->query('UPDATE products_attributes SET attributes_stock ='. round($model->getStockLevel()) .' WHERE options_values_id =' . Product::extractOptionValueId($productId));
            
            return $model;
        }
        
        return false;
    }
}
