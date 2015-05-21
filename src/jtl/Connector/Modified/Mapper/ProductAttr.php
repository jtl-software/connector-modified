<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;
use jtl\Connector\Model\ProductAttr as ProductAttrModel;
use jtl\Connector\Model\ProductAttrI18n as ProductAttrI18nModel;

class ProductAttr extends BaseMapper
{
    public function pull($data = null, $limit = null) {
        $attr = new ProductAttrModel();
        $attr->setId($this->identity(1));
        $attr->setProductId($this->identity($data['products_id']));

        $attrI18n = new ProductAttrI18nModel();
        $attrI18n->setProductAttrId($attr->getId());
        $attrI18n->setLanguageISO('ger');
        $attrI18n->setName('Aktiv');
        $attrI18n->setValue($data['products_status']);

        $attr->setI18ns([$attrI18n]);

        return [$attr];
    }

    public function push($data, $dbObj = null) {
        $dbObj->products_status = 1;

        foreach ($data->getAttributes() as $attr) {
            if ($attr->getId()->getEndpoint() == 1) {
                foreach ($attr->getI18ns() as $i18n) {
                    if ($i18n->getName() == 'Aktiv' && $i18n->getValue() == '0') {
                        $dbObj->products_status = 0;
                    }                    
                }                
            }
        }

        return $data->getAttributes();
    }
}
