<?php
require_once 'RGB.php'; //这里请看另一个 "PHP实现色彩空间转换"
/**
 * 图像信息处理
 * @author shizhuolin
 */
class Image {
    /**
     * 位图资源
     * @var resource
     */
    protected $_image;
    /**
     * 使用指定文件构造图像操作实例
     * @param string $filename
     */
    public function __construct($filename=null) {
        if ($filename)
            $this->open($filename);
    }
    /**
     * 读取指定文件明的图像
     * @param string $filename
     * @return Image
     */
    public function open($filename) {
        $handle = fopen($filename, "rb");
        $contents = stream_get_contents($handle);
        fclose($handle);
        $image = imagecreatefromstring($contents);
        $width = imagesx($image);
        $height = imagesy($image);
        $truecolorimage = imagecreatetruecolor($width, $height);
        imagefilledrectangle($truecolorimage, 0, 0, $width, $height, 0xFFFFFF);
        imagecopyresampled($truecolorimage, $image, 0, 0, 0, 0, $width, $height, $width, $height);
        imagedestroy($image);
        $this->setImage($truecolorimage);
        return $this;
    }
    /**
     * 读取指定资源的图像
     * @param resource $image
     * @return Image
     */
    public function load($image) {
        $width = imagesx($image);
        $height = imagesy($image);
        $truecolorimage = imagecreatetruecolor($width, $height);
        imagefilledrectangle($truecolorimage, 0, 0, $width, $height, 0xFFFFFF);
        imagecopyresampled($truecolorimage, $image, 0, 0, 0, 0, $width, $height, $width, $height);
        if ($this->getImage()) {
            imagedestroy($this->getImage());
        }
        $this->setImage($truecolorimage);
        return $this;
    }
    /**
     * 设置内部图像资源描述符
     * @param resource $image
     */
    protected function setImage($image) {
        $this->_image = $image;
    }
    /**
     * 获取内部操作图像资源
     * @return resource
     */
    public function getImage() {
        return $this->_image;
    }
    /**
     * 获取图像宽度
     * @return int
     */
    public function getWidth() {
        return imagesx($this->getImage());
    }
    /**
     * 获取图像高度
     * @return int
     */
    public function getHeight() {
        return imagesy($this->getImage());
    }
    /**
     * 获取指定坐标像素颜色
     * @param type $x
     * @param type $y
     * @return type
     */
    public function getColorAt($x, $y) {
        $rgb = imageColorAt($this->getImage(), $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        return array('red' => $r, 'green' => $g, 'blue' => $b);
    }
    /**
     * 高斯模糊(单纬度二次模糊)
     * @param float $radius 半径
     */
    public function gaussianBlur($radius=1.0) {
        $width = $this->getWidth();
        $height = $this->getHeight();
        $sigma = $radius / 3;
        $sigma2 = 2 * pow($sigma, 2);
        $matrix = array();
        $newimage = imagecreatetruecolor($width, $height);
        /* 生成高斯矩阵(单纬度矩阵) */
        for ($x = -$radius; $x <= $radius; $x++) {
            $x2 = pow($x, 2) + pow($x, 2);
            $matrix[] = exp(-$x2 / $sigma2) / ($sigma2 * M_PI);
        }
        /* 构建模糊图像 */
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $bright = $red = $green = $blue = 0;
                /* 垂直模糊 */
                for ($yy = -$radius; $yy <= $radius; $yy++) {
                    $yyy = $y + $yy;
                    if ($yyy >= 0 && $yyy < $height) {
                        $weight = $matrix[$yy + $radius];
                        $bright += $weight;
                        $color = $this->getColorAt($x, $yyy);
                        $red += ($color['red'] * $weight);
                        $green += ($color['green'] * $weight);
                        $blue += ($color['blue'] * $weight);
                    }
                }
                /* 水平模糊 */
                for ($xx = -$radius; $xx <= $radius; $xx++) {
                    $xxx = $x + $xx;
                    if ($xxx >= 0 && $xxx < $width && $xx != 0) {
                        $weight = $matrix[$xx + $radius];
                        $bright+=$weight;
                        $color = $this->getColorAt($xxx, $y);
                        $red += ($color['red'] * $weight);
                        $green += ($color['green'] * $weight);
                        $blue += ($color['blue'] * $weight);
                    }
                }
                $z = 1 / $bright;
                imagesetpixel($newimage, $x, $y, ($red * $z << 16) | ($green * $z << 8) | $blue * $z);
            }
        }
        imagedestroy($this->getImage());
        $this->setImage($newimage);
    }
    /**
     * 析构 销毁图像占用内存
     */
    public function __destruct() {
        if ($this->getImage()) {
            imagedestroy($this->getImage());
        }
    }
}