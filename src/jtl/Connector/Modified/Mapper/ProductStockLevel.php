<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\ProductStockLevel as ProductStockLevelModel;

class ProductStockLevel extends AbstractMapper
{
    public function pull($data = null, $limit = null)
    {
        $stockLevel = new ProductStockLevelModel();
        $stockLevel->setProductId($this->identity($data['products_id']));
        $stockLevel->setStockLevel(floatval($data['products_quantity']));

        return [$stockLevel];
    }
    
    public function push(ProductStockLevelModel $stockLevel)
    {
        $productId = $stockLevel->getProductId()->getEndpoint();
        $isVarCombi = Product::isVariationChild($productId);
        
        if (!empty($productId) && $isVarCombi == false) {
            $this->db->query('UPDATE products SET products_quantity='.round($stockLevel->getStockLevel()).' WHERE products_id='.$productId);
            
            return $stockLevel;
        } elseif (!empty($productId) && $isVarCombi == true) {
            $this->db->query('UPDATE products_attributes SET attributes_stock ='. round($stockLevel->getStockLevel()) .' WHERE options_values_id =' . Product::extractOptionValueId($productId));
            
            return $stockLevel;
        }
        
        return false;
    }
}
