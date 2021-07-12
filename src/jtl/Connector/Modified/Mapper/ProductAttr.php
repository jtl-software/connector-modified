<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\ProductAttr as ProductAttrModel;
use jtl\Connector\Model\ProductAttrI18n as ProductAttrI18nModel;

class ProductAttr extends BaseMapper
{
    private $additions = [
        'products_status' => 'Aktiv',
        'products_fsk18' => 'FSK 18',
        'product_template' => 'Produkt Vorlage',
        'options_template' => 'Optionen Vorlage'
    ];

    public function pull($data = null, $limit = null)
    {
        $attrs = [];

        foreach ($this->additions as $field => $name) {
            $attrs[] = $this->createAttr($field, $name, $data[$field], $data);
        }

        return $attrs;
    }

    public function push($data, $dbObj = null)
    {
        $dbObj->products_status = 1;
        $tableColumns = [];
        
        $columns = $this->db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N'products' AND TABLE_SCHEMA = '" . $this->db->name . "'");
        foreach ($columns as $column) {
            array_push($tableColumns, $column['COLUMN_NAME']);
        }
        
        foreach ($data->getAttributes() as $attr) {
            $i18ns = $attr->getI18ns();
            $i18n = reset($i18ns);
            $field = array_search($i18n->getName(), $this->additions);
            if ($field) {
                $dbObj->$field = $i18n->getValue();
            } elseif (in_array($i18n->getName(), $tableColumns)) {
                $fieldName = $i18n->getName();
                $dbObj->$fieldName = $i18n->getValue();
            }
        }

        return $data->getAttributes();
    }

    private function createAttr($id, $name, $value, $data)
    {
        $attr = new ProductAttrModel();
        $attr->setId($this->identity($id));
        $attr->setProductId($this->identity($data['products_id']));

        $attrI18n = new ProductAttrI18nModel();
        $attrI18n->setProductAttrId($attr->getId());
        $attrI18n->setLanguageISO('ger');
        $attrI18n->setName($name);
        $attrI18n->setValue($value);

        $attr->setI18ns([$attrI18n]);

        return $attr;
    }
}
