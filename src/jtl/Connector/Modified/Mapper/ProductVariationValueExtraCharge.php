<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;
use jtl\Connector\Model\ProductVariationValueExtraCharge as ProductVariationValueExtraChargeModel;

class ProductVariationValueExtraCharge extends BaseMapper
{
    public function pull($data = null, $limit = null)
    {
        $return = [];

        if ($data['options_values_price'] != 0) {
            foreach ($this->getCustomerGroups() as $group) {
                $groupId = $group['customers_status_id'];

                if ($groupId == $this->shopConfig['settings']['DEFAULT_CUSTOMERS_STATUS_ID']) {
                    $extraCharge = new ProductVariationValueExtraChargeModel();
                    $extraCharge->setCustomerGroupId($this->identity($groupId['customers_status_id']));
                    $extraCharge->setProductVariationValueId($this->identity($data['options_values_id']));
                    $extraCharge->setExtraChargeNet(floatval($data['price_prefix'] == '-' ? $data['options_values_price'] * -1 : $data['options_values_price']));

                    $return[] = $extraCharge;
                }
            }
        }

        return $return;
    }
}
