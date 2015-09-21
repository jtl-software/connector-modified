<?php
namespace jtl\Connector\Modified\Mapper;

class UnitI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "query" => "SELECT products_vpe.*,languages.code FROM products_vpe LEFT JOIN languages ON languages.languages_id=products_vpe.language_id WHERE products_vpe_id=[[products_vpe_id]]",
        "table" => "products_vpe",
        "getMethod" => "getI18ns",
        "mapPull" => array(
            "unitId" => "products_vpe_id",
            "languageISO" => null,
            "name" => "products_vpe_name"
        ),
        "mapPush" => array(
            "products_vpe_id" => "unitId",
            "language_id" => null,
            "products_vpe_name" => "name"  
        )
    );

    public function push($data, $dbObj = null)
    {
        $id = null;

        foreach ($data->getI18ns() as $i18n) {
            $language_id = $this->locale2id($i18n->getLanguageISO());
            
            $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id='.$language_id);

            if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                $sql = $this->db->query('SELECT products_vpe_id FROM products_vpe WHERE language_id='.$language_id.' && products_vpe_name="'.$i18n->getName().'"');
                if (count($sql) > 0) {
                    $id = $sql[0]['products_vpe_id'];
                }
            }
        }

        if (is_null($id)) {
            $nextId = $this->db->query('SELECT max(products_vpe_id) + 1 AS nextID FROM products_vpe');
            $id = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];
        } else {
            $this->db->query('DELETE FROM products_vpe WHERE products_vpe_id='.$id);
        }

        $data->getId()->setEndpoint($id);

        foreach ($data->getI18ns() as $i18n) {
            $i18n->getUnitId()->setEndpoint($id);

            $vpe = new \stdClass();
            $vpe->language_id = $this->locale2id($i18n->getLanguageISO());
            $vpe->products_vpe_id = $id;
            $vpe->products_vpe_name = $i18n->getName();

            $this->db->insertRow($vpe, 'products_vpe');
        }

        return $data->getI18ns();
    }

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }

    protected function language_id($data)
    {
        return $this->locale2id($data->getLanguageISO());
    }
}
