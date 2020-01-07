<?php

namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Drawing\ImageRelationType;
use \jtl\Connector\Model\Image as ImageModel;

class Image extends BaseMapper
{
    protected $mapperConfig = [
        "table"    => "products_images",
        "identity" => "getId",
        "mapPull"  => [
            "id"           => "image_id",
            "relationType" => "type",
            "foreignKey"   => "foreignKey",
            "remoteUrl"    => null,
            "sort"         => "image_nr",
        ],
    ];
    
    private $thumbConfig;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->thumbConfig = [
            'info'       => [
                $this->shopConfig['settings']['PRODUCT_IMAGE_INFO_WIDTH'],
                $this->shopConfig['settings']['PRODUCT_IMAGE_INFO_HEIGHT'],
            ],
            'popup'      => [
                $this->shopConfig['settings']['PRODUCT_IMAGE_POPUP_WIDTH'],
                $this->shopConfig['settings']['PRODUCT_IMAGE_POPUP_HEIGHT'],
            ],
            'thumbnails' => [
                $this->shopConfig['settings']['PRODUCT_IMAGE_THUMBNAIL_WIDTH'],
                $this->shopConfig['settings']['PRODUCT_IMAGE_THUMBNAIL_HEIGHT'],
            ],
        ];
    }
    
    public function pull($data = null, $limit = null)
    {
        $result = [];
        
        $query = 'SELECT p.image_id, p.image_name, p.products_id foreignKey, "product" type, (p.image_nr + 1) image_nr
            FROM products_images p
            LEFT JOIN jtl_connector_link_image l ON p.image_id = l.endpoint_id
            WHERE l.host_id IS NULL';
        $defaultQuery = 'SELECT CONCAT("pID_",p.products_id) image_id, p.products_image image_name, p.products_id foreignKey, 1 image_nr, "product" type
            FROM products p
            LEFT JOIN jtl_connector_link_image l ON CONCAT("pID_",p.products_id) = l.endpoint_id
            WHERE l.host_id IS NULL && p.products_image IS NOT NULL && p.products_image != ""';
        $categoriesQuery = 'SELECT CONCAT("cID_",p.categories_id) image_id, p.categories_image as image_name, p.categories_id foreignKey, "category" type, 1 image_nr
            FROM categories p
            LEFT JOIN jtl_connector_link_image l ON CONCAT("cID_",p.categories_id) = l.endpoint_id
            WHERE l.host_id IS NULL && p.categories_image IS NOT NULL && p.categories_image != ""';
        $manufacturersQuery = 'SELECT CONCAT("mID_",m.manufacturers_id) image_id, m.manufacturers_image as image_name, m.manufacturers_id foreignKey, "manufacturer" type, 1 image_nr
            FROM manufacturers m
            LEFT JOIN jtl_connector_link_image l ON CONCAT("mID_",m.manufacturers_id) = l.endpoint_id
            WHERE l.host_id IS NULL && m.manufacturers_image IS NOT NULL && m.manufacturers_image != ""';
        
        $dbResult = $this->db->query($query);
        $dbResultDefault = $this->db->query($defaultQuery);
        $dbResultCategories = $this->db->query($categoriesQuery);
        $dbResultManufacturers = $this->db->query($manufacturersQuery);
        
        $dbResult = array_merge($dbResult, $dbResultDefault, $dbResultCategories, $dbResultManufacturers);
        
        $current = array_slice($dbResult, 0, $limit);
        
        foreach ($current as $modelData) {
            $model = $this->generateModel($modelData);
            
            $result[] = $model;
        }
        
        return $result;
    }
    
    public function push($data, $dbObj = null)
    {
        if (get_class($data) === 'jtl\Connector\Model\Image') {
            switch ($data->getRelationType()) {
                case ImageRelationType::TYPE_CATEGORY:
                    $oldImage = $this->db->query('SELECT categories_image FROM categories WHERE categories_id = "' . $data->getForeignKey()->getEndpoint() . '"');
                    $oldImage = $oldImage[0]['categories_image'];
                    
                    if (isset($oldImage)) {
                        @unlink($this->shopConfig['shop']['path'] . 'images/categories/' . $oldImage);
                    }
                    
                    $imgFileName = $this->generateImageName($data);
                    
                    if (!rename($data->getFilename(), $this->shopConfig['shop']['path'] . 'images/categories/' . $imgFileName)) {
                        throw new \Exception('Cannot move uploaded image file');
                    }
                    
                    $categoryObj = new \stdClass();
                    $categoryObj->categories_image = $imgFileName;
                    
                    $this->db->updateRow($categoryObj, 'categories', 'categories_id', $data->getForeignKey()->getEndpoint());
                    
                    $data->getId()->setEndpoint('cID_' . $data->getForeignKey()->getEndpoint());
                    
                    break;
                
                case ImageRelationType::TYPE_MANUFACTURER:
                    $oldImage = $this->db->query('SELECT manufacturers_image FROM manufacturers WHERE manufacturers_id = "' . $data->getForeignKey()->getEndpoint() . '"');
                    $oldImage = $oldImage[0]['manufacturers_image'];
                    
                    if (isset($oldImage)) {
                        @unlink($this->shopConfig['shop']['path'] . 'images/' . $oldImage);
                    }
                    
                    $imgFileName = $this->generateImageName($data);
                    
                    if (!rename($data->getFilename(), $this->shopConfig['shop']['path'] . 'images/manufacturers/' . $imgFileName)) {
                        throw new \Exception('Cannot move uploaded image file');
                    }
                    
                    $manufacturersObj = new \stdClass();
                    $manufacturersObj->manufacturers_image = 'manufacturers/' . $imgFileName;
                    
                    $this->db->updateRow($manufacturersObj, 'manufacturers', 'manufacturers_id', $data->getForeignKey()->getEndpoint());
                    
                    $data->getId()->setEndpoint('mID_' . $data->getForeignKey()->getEndpoint());
                    
                    break;
                
                case ImageRelationType::TYPE_PRODUCT:
                    if ($data->getSort() == 1) {
                        $imgId = $data->getId()->getEndpoint();
                        
                        if (!empty($imgId)) {
                            $prevImgQuery = $this->db->query('SELECT image_name FROM products_images WHERE image_id = "' . $imgId . '"');
                            if (count($prevImgQuery) > 0) {
                                $prevImage = $prevImgQuery[0]['image_name'];
                            }
                            
                            if (!empty($prevImage)) {
                                @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $prevImage);
                                foreach ($this->thumbConfig as $folder => $sizes) {
                                    unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $prevImage);
                                }
                            }
                            
                            $this->db->query('DELETE FROM products_images WHERE image_id="' . $imgId . '"');
                        }
                        
                        $oldImage = $this->db->query('SELECT products_image FROM products WHERE products_id = "' . $data->getForeignKey()->getEndpoint() . '"');
                        $oldImage = $oldImage[0]['products_image'];
                        
                        if (!empty($oldImage)) {
                            @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $oldImage);
                        }
                        
                        $imgFileName = $this->generateImageName($data);
                        
                        if (!rename($data->getFilename(), $this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $imgFileName)) {
                            throw new \Exception('Cannot move uploaded image file');
                        }
                        
                        $this->generateThumbs($imgFileName, $oldImage);
                        
                        $productsObj = new \stdClass();
                        $productsObj->products_image = $imgFileName;
                        
                        $this->db->updateRow($productsObj, 'products', 'products_id', $data->getForeignKey()->getEndpoint());
                        
                        $data->getId()->setEndpoint('pID_' . $data->getForeignKey()->getEndpoint());
                        
                        $this->db->query('DELETE FROM jtl_connector_link_image WHERE endpoint_id="' . $data->getId()->getEndpoint() . '"');
                        $this->db->query('DELETE FROM jtl_connector_link_image WHERE host_id=' . $data->getId()->getHost());
                        $this->db->query('INSERT INTO jtl_connector_link_image SET host_id="' . $data->getId()->getHost() . '", endpoint_id="' . $data->getId()->getEndpoint() . '"');
                    } else {
                        $oldImage = null;
                        $imgObj = new \stdClass();
                        
                        $imgId = $data->getId()->getEndpoint();
                        
                        if (!empty($imgId)) {
                            $prevImgQuery = $this->db->query('SELECT image_name FROM products_images WHERE image_id = "' . $imgId . '"');
                            if (count($prevImgQuery) > 0) {
                                $prevImage = $prevImgQuery[0]['image_name'];
                            }
                            
                            if (!empty($prevImage)) {
                                @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $prevImage);
                                foreach ($this->thumbConfig as $folder => $sizes) {
                                    unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $prevImage);
                                }
                            }
                            
                            $this->db->query('DELETE FROM products_images WHERE image_id="' . $imgId . '"');
                        }
                        
                        $oldImageQuery = $this->db->query('SELECT image_name FROM products_images WHERE products_id = "' . $data->getForeignKey()->getEndpoint() . '" && image_nr=' . ($data->getSort() - 1));
                        if (count($oldImageQuery) > 0) {
                            $oldImage = $oldImageQuery[0]['image_name'];
                        }
                        
                        if (!empty($oldImage)) {
                            @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $oldImage);
                        }
                        
                        $imgObj->image_id = $data->getId()->getEndpoint();
                        
                        $imgFileName = $this->generateImageName($data);
                        
                        if (!rename($data->getFilename(), $this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $imgFileName)) {
                            throw new \Exception('Cannot move uploaded image file');
                        }
                        
                        $this->generateThumbs($imgFileName, $oldImage);
                        
                        $imgObj->products_id = $data->getForeignKey()->getEndpoint();
                        $imgObj->image_name = $imgFileName;
                        $imgObj->image_nr = ($data->getSort() - 1);
                        
                        $newIdQuery = $this->db->deleteInsertRow($imgObj, 'products_images', ['image_nr', 'products_id'], [$imgObj->image_nr, $imgObj->products_id]);
                        $newId = $newIdQuery->getKey();
                        
                        $this->db->query('DELETE FROM jtl_connector_link_image WHERE host_id=' . $data->getId()->getHost());
                        $this->db->query('INSERT INTO jtl_connector_link_image SET host_id="' . $data->getId()->getHost() . '", endpoint_id="' . $newId . '"');
                        
                        $data->getId()->setEndpoint($newId);
                    }
                    
                    break;
            }
            
            return $data;
        } else {
            throw new \Exception('Pushed data is not an image object');
        }
    }
    
    public function delete($data)
    {
        if (get_class($data) === 'jtl\Connector\Model\Image') {
            switch ($data->getRelationType()) {
                case ImageRelationType::TYPE_CATEGORY:
                    $oldImage = $this->db->query('SELECT categories_image FROM categories WHERE categories_id = "' . $data->getForeignKey()->getEndpoint() . '"');
                    $oldImage = $oldImage[0]['categories_image'];
                    
                    if (isset($oldImage)) {
                        @unlink($this->shopConfig['shop']['path'] . 'images/categories/' . $oldImage);
                    }
                    
                    $categoryObj = new \stdClass();
                    $categoryObj->categories_image = null;
                    
                    $this->db->updateRow($categoryObj, 'categories', 'categories_id', $data->getForeignKey()->getEndpoint());
                    
                    break;
                
                case ImageRelationType::TYPE_MANUFACTURER:
                    $oldImage = $this->db->query('SELECT manufacturers_image FROM manufacturers WHERE manufacturers_id = "' . $data->getForeignKey()->getEndpoint() . '"');
                    $oldImage = $oldImage[0]['manufacturers_image'];
                    
                    if (isset($oldImage)) {
                        @unlink($this->shopConfig['shop']['path'] . 'images/' . $oldImage);
                    }
                    
                    $manufacturersObj = new \stdClass();
                    $manufacturersObj->categories_image = null;
                    
                    $this->db->updateRow($manufacturersObj, 'manufacturers', 'manufacturers_id', $data->getForeignKey()->getEndpoint());
                    
                    break;
                
                case ImageRelationType::TYPE_PRODUCT:
                    if ($data->getSort() == 0) {
                        $oldImage = $this->db->query('SELECT products_image FROM products WHERE products_id = "' . $data->getForeignKey()->getEndpoint() . '"');
                        $oldImage = $oldImage[0]['products_image'];
                        
                        if (isset($oldImage)) {
                            @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $oldImage);
                            $this->db->query('UPDATE products SET products_image="" WHERE products_id="' . $data->getForeignKey()->getEndpoint() . '"');
                        }
                        
                        $additionalImages = $this->db->query('SELECT image_name FROM products_images WHERE products_id="' . $data->getForeignKey()->getEndpoint() . '"');
                        
                        foreach ($additionalImages as $image) {
                            if (!empty($image['image_name'])) {
                                @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $image['image_name']);
                                
                                foreach ($this->thumbConfig as $folder => $sizes) {
                                    @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $image['image_name']);
                                }
                            }
                        }
                        
                        $this->db->query('DELETE FROM products_images WHERE products_id="' . $data->getForeignKey()->getEndpoint() . '"');
                    } elseif ($data->getSort() == 1) {
                        $oldImage = $this->db->query('SELECT products_image FROM products WHERE products_id = "' . $data->getForeignKey()->getEndpoint() . '"');
                        $oldImage = $oldImage[0]['products_image'];
                        
                        if (isset($oldImage)) {
                            @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $oldImage);
                        }
                        
                        $productsObj = new \stdClass();
                        $productsObj->products_image = null;
                        
                        $this->db->updateRow($productsObj, 'products', 'products_id', $data->getForeignKey()->getEndpoint());
                    } else {
                        if ($data->getId()->getEndpoint() != '') {
                            $oldImageQuery = $this->db->query('SELECT image_name FROM products_images WHERE image_id = "' . $data->getId()->getEndpoint() . '"');
                            
                            if (count($oldImageQuery) > 0) {
                                $oldImage = $oldImageQuery[0]['image_name'];
                                @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $oldImage);
                            }
                            
                            $this->db->query('DELETE FROM products_images WHERE image_id="' . $data->getId()->getEndpoint() . '"');
                        }
                    }
                    
                    break;
            }
            
            foreach ($this->thumbConfig as $folder => $sizes) {
                if (!is_null($oldImage)) {
                    unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $oldImage);
                }
            }
            
            $this->db->query('DELETE FROM jtl_connector_link_image WHERE endpoint_id="' . $data->getId()->getEndpoint() . '"');
            
            return $data;
        } else {
            throw new \Exception('Pushed data is not an image object');
        }
    }
    
    public function statistic()
    {
        $totalImages = 0;
        
        $productQuery = $this->db->query("
            SELECT p.*
            FROM (
                SELECT CONCAT('pID_',p.products_id) as imgId
                FROM products p
                WHERE p.products_image IS NOT NULL && p.products_image != ''
            ) p
            LEFT JOIN jtl_connector_link_image l ON p.imgId = l.endpoint_id
            WHERE l.host_id IS NULL
        ");
        
        $categoryQuery = $this->db->query("
            SELECT c.*
            FROM (
                SELECT CONCAT('cID_',c.categories_id) as imgId
                FROM categories c
                WHERE c.categories_image IS NOT NULL && c.categories_image != ''
            ) c
            LEFT JOIN jtl_connector_link_image l ON c.imgId = l.endpoint_id
            WHERE l.host_id IS NULL
        ");
        
        $manufacturersQuery = $this->db->query("
            SELECT m.*
            FROM (
                SELECT CONCAT('mID_',m.manufacturers_id) as imgId
                FROM manufacturers m
                WHERE m.manufacturers_image IS NOT NULL && m.manufacturers_image != ''
            ) m
            LEFT JOIN jtl_connector_link_image l ON m.imgId = l.endpoint_id
            WHERE l.host_id IS NULL
        ");
        
        $imageQuery = $this->db->query("
            SELECT i.* FROM products_images i
            LEFT JOIN jtl_connector_link_image l ON i.image_id = l.endpoint_id
            WHERE l.host_id IS NULL
        ");
        
        $totalImages += count($productQuery);
        $totalImages += count($categoryQuery);
        $totalImages += count($manufacturersQuery);
        $totalImages += count($imageQuery);
        
        return $totalImages;
    }
    
    protected function remoteUrl($data)
    {
        if ($data['type'] == ImageRelationType::TYPE_CATEGORY) {
            return $this->shopConfig['shop']['fullUrl'] . 'images/categories/' . $data['image_name'];
        } elseif ($data['type'] == ImageRelationType::TYPE_MANUFACTURER) {
            return $this->shopConfig['shop']['fullUrl'] . 'images/' . $data['image_name'];
        } else {
            return $this->shopConfig['shop']['fullUrl'] . $this->shopConfig['img']['original'] . $data['image_name'];
        }
    }
    
    /**
     * @param $fileName
     * @param null $oldImage
     */
    private function generateThumbs($fileName, $oldImage = null)
    {
        $imgInfo = getimagesize($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $fileName);
        
        switch ($imgInfo[2]) {
            case 1:
                $image = imagecreatefromgif($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $fileName);
                break;
            case 2:
                $image = imagecreatefromjpeg($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $fileName);
                break;
            case 3:
                $image = imagecreatefrompng($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $fileName);
                break;
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        $original_aspect = $width / $height;
        
        foreach ($this->thumbConfig as $folder => $sizes) {
            if (!empty($oldImage)) {
                unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $oldImage);
            }
            
            $thumb_width = $sizes[0];
            $thumb_height = $sizes[1];
            
            $new_width = $thumb_width;
            $new_height = round($new_width * ($height / $width));
            $new_x = 0;
            $new_y = round(($thumb_height - $new_height) / 2);
            
            if ($this->connectorConfig->thumbs === 'fill') {
                $next = $new_height < $thumb_height;
            } else {
                $next = $new_height > $thumb_height;
            }
            
            if ($next) {
                $new_height = $thumb_height;
                $new_width = round($new_height * ($width / $height));
                $new_x = round(($thumb_width - $new_width) / 2);
                $new_y = 0;
            }
            
            $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
            imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
            
            if ($imgInfo[2] == 1 || $imgInfo[2] == 3) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefilledrectangle($thumb, 0, 0, $thumb_width, $thumb_height, $transparent);
            }
            
            imagecopyresampled(
                $thumb,
                $image,
                $new_x,
                $new_y,
                0,
                0,
                $new_width,
                $new_height,
                $width,
                $height
            );
            
            switch ($imgInfo[2]) {
                case 1:
                    imagegif($thumb, $this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $fileName);
                    break;
                case 2:
                    imagejpeg($thumb, $this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $fileName);
                    break;
                case 3:
                    imagepng($thumb, $this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $fileName);
                    break;
            }
        }
    }
    
    /**
     * @param \jtl\Connector\Model\Image $data
     * @return false|string
     */
    private function generateImageName(ImageModel $data)
    {
        $imgFileName = substr($data->getFilename(), strrpos($data->getFilename(), '/') + 1);
        
        if (!empty($data->getName())) {
            $fileEnding = substr($data->getFilename(), strrpos($data->getFilename(), '.'));
            $newFileName = strtolower(preg_replace("([^\w\s\d\-_~\(\).])", "", $data->getName()));
            $newFileName = preg_replace("([\s])", "-", $newFileName);
            $imgFileName = $newFileName . $fileEnding;
            
            $duplicates = $this->db->query(sprintf('SELECT image_name FROM products_images
                                    WHERE image_name LIKE "%s(%%)%s" OR image_name = "%s"
                                    ORDER BY image_name DESC', $newFileName, $fileEnding, $imgFileName));
            
            if (count($duplicates) > 0) {
                $highestDuplicateIndex = $imgFileName === $duplicates[0]['image_name'] ? 1 : 0;
                
                preg_match("/(\d+?)\)\.[a-zA-Z]{3}$/", $duplicates[$highestDuplicateIndex]['image_name'], $duplicateNumbers);
                $imgFileName = sprintf("%s(%s)%s", $newFileName, $duplicateNumbers[$highestDuplicateIndex] + 1, $fileEnding);
            }
        }
        
        return $imgFileName;
    }
}
