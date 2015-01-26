<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ManufacturerI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "mapPull" => array(
        	"manufacturerId" => "manufacturers_id",
        	"description" => "manufacturers_name",
            "localeName" => "locale"
        )
    );

    private $languages = [];

    public function __construct() {
        parent::__construct();

        $languages = $this->db->query("SELECT code FROM languages");

        foreach($languages as $language) {
            $this->languages[] = $this->fullLocale($language['code']);
        }
    }

    public function pull($data) {
        $return = [];

        foreach($this->languages as $iso) {
            $i18nData = array_merge($data,array("locale" => $iso));
            $return[] = $this->generateModel($i18nData);
        }

        return $return;
    }

    public function push($data,$dbObj) {
        foreach($data->getI18ns() as $language) {
            $dbObj->manufacturers_name = $language->getDescription();
        }
    }
}