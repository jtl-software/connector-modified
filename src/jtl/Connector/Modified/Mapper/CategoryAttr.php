<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;
use jtl\Connector\Model\CategoryAttr as CategoryAttrModel;
use jtl\Connector\Model\CategoryAttrI18n as CategoryAttrI18nModel;

class CategoryAttr extends BaseMapper
{
    public function pull($data = null, $limit = null) {
        $attr = new CategoryAttrModel();
        $attr->setId($this->identity(1));
        $attr->setCategoryId($this->identity($data['categories_id']));

        $attrI18n = new CategoryAttrI18nModel();
        $attrI18n->setCategoryAttrId($attr->getId());
        $attrI18n->setLanguageISO('ger');
        $attrI18n->setName('Aktiv');
        $attrI18n->setValue($data['categories_status']);

        $attr->setI18ns([$attrI18n]);

        return [$attr];
    }

    public function push($data, $dbObj = null) {
        $dbObj->categories_status = 1;

        foreach ($data->getAttributes() as $attr) {
            foreach ($attr->getI18ns() as $i18n) {
                if ($i18n->getName() == 'Aktiv' && $i18n->getValue() == '0') {
                    $dbObj->categories_status = 0;
                    break;
                }                    
            }            
        }

        return $data->getAttributes();
    }
}
