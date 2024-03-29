<?php

namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Drawing\ImageRelationType;
use jtl\Connector\Model\DataModel;
use \jtl\Connector\Model\Image as ImageModel;
use Nette\Utils\Strings;

class Image extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "products_images",
        "identity" => "getId",
        "mapPull" => [
            "id" => "image_id",
            "relationType" => "type",
            "foreignKey" => "foreignKey",
            "remoteUrl" => null,
            "sort" => "image_nr",
        ],
    ];

    private $thumbConfig;
    
    public function __construct($db, $shopConfig, $connectorConfig)
    {
        parent::__construct($db, $shopConfig, $connectorConfig);
        
        $this->thumbConfig = [
            'info' => [
                $this->shopConfig['settings']['PRODUCT_IMAGE_INFO_WIDTH'],
                $this->shopConfig['settings']['PRODUCT_IMAGE_INFO_HEIGHT'],
            ],
            'popup' => [
                $this->shopConfig['settings']['PRODUCT_IMAGE_POPUP_WIDTH'],
                $this->shopConfig['settings']['PRODUCT_IMAGE_POPUP_HEIGHT'],
            ],
            'thumbnails' => [
                $this->shopConfig['settings']['PRODUCT_IMAGE_THUMBNAIL_WIDTH'],
                $this->shopConfig['settings']['PRODUCT_IMAGE_THUMBNAIL_HEIGHT'],
            ],
        ];
    }

    public function pull($data = null, $limit = null): array
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
                case ImageRelationType::TYPE_MANUFACTURER:

                    $indexMappings = [
                        ImageRelationType::TYPE_CATEGORY => 'categories',
                        ImageRelationType::TYPE_MANUFACTURER => 'manufacturers',
                    ];

                    $subject = $indexMappings[$data->getRelationType()];

                    $oldImage = null;
                    $oldImageResult = $this->db->query(sprintf('SELECT %s_image FROM %s WHERE %s_id = %d', $subject, $subject, $subject, $data->getForeignKey()->getEndpoint()));

                    $imageIndex = sprintf('%s_image', $subject);
                    if (isset($oldImageResult[0][$imageIndex]) && $oldImageResult[0][$imageIndex] !== '') {
                        $oldImage = $oldImageResult[0][$imageIndex];

                        $oldImageFilePath = $this->createImageFilePath($oldImage, $data->getRelationType());
                        if (file_exists($oldImageFilePath)) {
                            @unlink($oldImageFilePath);
                        }
                    }

                    $imgFileName = $this->generateImageName($data);
                    $imageFilePath = $this->createImageFilePath($imgFileName, $data->getRelationType());
                    if (!rename($data->getFilename(), $imageFilePath)) {
                        throw new \Exception('Cannot move uploaded image file');
                    }

                    $relatedObject = new \stdClass();
                    $relatedObject->{$imageIndex} = $imgFileName;
                    if ($data->getRelationType() === ImageRelationType::TYPE_MANUFACTURER) {
                        $relatedObject->{$imageIndex} = sprintf('%s/%s', $subject, $imgFileName);
                    }

                    $this->db->updateRow($relatedObject, $subject, sprintf('%s_id', $subject), $data->getForeignKey()->getEndpoint());

                    $endpoint = sprintf('%sID_%d', $subject[0], $data->getForeignKey()->getEndpoint());
                    $data->getId()->setEndpoint($endpoint);
                    break;
                case ImageRelationType::TYPE_PRODUCT:
                    if (!Product::isVariationChild($data->getForeignKey()->getEndpoint())) {
                        $oldImage = null;
                        $prevImage = null;
                        $imgId = $data->getId()->getEndpoint();
                        if (!empty($imgId)) {
                            $prevImgQuery = $this->db->query(sprintf('SELECT image_name FROM products_images WHERE image_id = "%s"', $imgId));
                            if (count($prevImgQuery) > 0) {
                                $prevImage = $prevImgQuery[0]['image_name'];
                            }

                            if (!empty($prevImage)) {
                                $prevImagePath = $this->createImageFilePath($prevImage, $data->getRelationType());
                                if (is_file($prevImagePath)) {
                                    @unlink($prevImagePath);
                                }

                                foreach ($this->thumbConfig as $folder => $sizes) {
                                    $thumbnailPath = $this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $prevImage;
                                    if (is_file($thumbnailPath)) {
                                        @unlink($thumbnailPath);
                                    }
                                }
                            }

                            $this->db->query(sprintf('DELETE FROM products_images WHERE image_id = "%s"', $imgId));
                        }

                        if ($data->getSort() == 1) {
                            $oldImageResult = $this->db->query(sprintf('SELECT products_image FROM products WHERE products_id = %s', $data->getForeignKey()->getEndpoint()));
                        } else {
                            $oldImageResult = $this->db->query(sprintf('SELECT image_name products_image FROM products_images WHERE products_id = %s AND image_nr = %d' , $data->getForeignKey()->getEndpoint(), ($data->getSort() - 1)));
                        }

                        if (isset($oldImageResult[0]['products_image'])) {
                            $oldImage = $oldImageResult[0]['products_image'];
                            $oldImageFilePath = $this->createImageFilePath($oldImage, $data->getRelationType());
                            if (is_file($oldImageFilePath)) {
                                @unlink($oldImageFilePath);
                            }
                        }

                        $imgFileName = $this->generateImageName($data);
                        $imageFilePath = $this->createImageFilePath($imgFileName, $data->getRelationType());
                        if (!rename($data->getFilename(), $imageFilePath)) {
                            throw new \Exception('Cannot move uploaded image file');
                        }

                        $this->generateThumbs($imgFileName, $oldImage);

                        if ($data->getSort() == 1) {
                            $productsObj = new \stdClass();
                            $productsObj->products_image = $imgFileName;

                            $this->db->updateRow($productsObj, 'products', 'products_id', $data->getForeignKey()->getEndpoint());
                            $data->getId()->setEndpoint('pID_' . $data->getForeignKey()->getEndpoint());

                            $this->db->query(sprintf('DELETE FROM jtl_connector_link_image WHERE endpoint_id = "%s"', $data->getId()->getEndpoint()));
                            $this->db->query(sprintf('DELETE FROM jtl_connector_link_image WHERE host_id = %d', $data->getId()->getHost()));
                            $this->db->query(sprintf('INSERT INTO jtl_connector_link_image SET host_id = %d, endpoint_id = "%s"', $data->getId()->getHost(), $data->getId()->getEndpoint()));
                        } else {
                            $imgObj = new \stdClass();
                            $imgObj->image_id = (int)$data->getId()->getEndpoint();
                            $imgObj->products_id = $data->getForeignKey()->getEndpoint();
                            $imgObj->image_name = $imgFileName;
                            $imgObj->image_nr = ($data->getSort() - 1);

                            $newIdQuery = $this->db->deleteInsertRow(
                                $imgObj,
                                'products_images',
                                ['image_nr', 'products_id'],
                                [$imgObj->image_nr, $imgObj->products_id]
                            );
                            $newId = $newIdQuery->getKey();

                            $this->db->query(sprintf('DELETE FROM jtl_connector_link_image WHERE host_id = %d', $data->getId()->getHost()));
                            $this->db->query(sprintf('INSERT INTO jtl_connector_link_image SET host_id = %d, endpoint_id = "%s"', $data->getId()->getHost(), $newId));

                            $data->getId()->setEndpoint($newId);
                        }
                    }

                    break;
            }

            return $data;
        } else {
            throw new \Exception('Pushed data is not an image object');
        }
    }

    /**
     * @param $data ImageModel
     * @return DataModel
     */
    public function delete($data)
    {
        $clearThumbnailsAndLinking = false;

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

                $clearThumbnailsAndLinking = true;
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

                $clearThumbnailsAndLinking = true;
                break;
            case ImageRelationType::TYPE_PRODUCT:
                if (Product::isVariationChild($data->getForeignKey()->getEndpoint()) === false) {
                    if ($data->getSort() === 0) {
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
                    } elseif ($data->getSort() === 1) {
                        $oldImage = $this->db->query('SELECT products_image FROM products WHERE products_id = "' . $data->getForeignKey()->getEndpoint() . '"');
                        $oldImage = $oldImage[0]['products_image'];

                        if (isset($oldImage)) {
                            @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $oldImage);
                        }

                        $productsObj = new \stdClass();
                        $productsObj->products_image = null;

                        $this->db->updateRow($productsObj, 'products', 'products_id', $data->getForeignKey()->getEndpoint());
                    } elseif ($data->getId()->getEndpoint() !== '') {
                        $oldImageQuery = $this->db->query('SELECT image_name FROM products_images WHERE image_id = "' . $data->getId()->getEndpoint() . '"');

                        if (count($oldImageQuery) > 0) {
                            $oldImage = $oldImageQuery[0]['image_name'];
                            @unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $oldImage);
                        }

                        $this->db->query('DELETE FROM products_images WHERE image_id="' . $data->getId()->getEndpoint() . '"');
                    }

                    $clearThumbnailsAndLinking = true;
                    break;
                }
        }

        if ($clearThumbnailsAndLinking === true) {
            foreach ($this->thumbConfig as $folder => $sizes) {
                if (!is_null($oldImage)) {
                    unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $oldImage);
                }
            }

            $this->db->query('DELETE FROM jtl_connector_link_image WHERE endpoint_id="' . $data->getId()->getEndpoint() . '"');
        }

        return $data;
    }

    public function statistic(): int
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
     * @param string $imageName
     * @param string $relationType
     * @return string
     */
    protected function createImageFilePath(string $imageName, string $relationType): string
    {
        $imagesPath = $this->shopConfig['img']['original'];
        switch ($relationType) {
            case ImageRelationType::TYPE_CATEGORY:
                $imagesPath = 'images/categories';
                break;
            case ImageRelationType::TYPE_MANUFACTURER:
                $imagesPath = 'images/manufacturers';
                break;
        }

        return sprintf('%s/%s/%s', rtrim($this->shopConfig['shop']['path'], '/'), trim($imagesPath, '/'), $imageName);
    }

    /**
     * @param ImageModel $jtlImage
     * @return string
     */
    protected function generateImageName(ImageModel $jtlImage)
    {
        $suffix = '';
        $i = 1;

        $info = pathinfo($jtlImage->getFilename());
        $extension = $info['extension'] ?? null;
        $filename = $info['filename'] ?? null;

        $name = !empty($jtlImage->getName()) ? $jtlImage->getName() : $filename;

        do {
            $imageName = sprintf('%s.%s', Strings::webalize(sprintf('%s%s', $name, $suffix)), $extension);
            $imageSavePath = $this->createImageFilePath($imageName, $jtlImage->getRelationType());
            $suffix = sprintf('-%s', $i++);
        } while (file_exists($imageSavePath));

        return $imageName;
    }
}
